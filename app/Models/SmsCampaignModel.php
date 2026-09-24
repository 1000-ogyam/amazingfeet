<?php
/**
 * SMS campaigns via Arkesel API (https://sms.arkesel.com/api/v2/sms/send).
 */
class SmsCampaignModel {
    private PDO $db;
    private const SEND_URL = 'https://sms.arkesel.com/api/v2/sms/send';
    private const BALANCE_URL = 'https://sms.arkesel.com/api/v2/clients/balance-details';
    /** Max recipients per Arkesel request (safe batch size). */
    private const BATCH_SIZE = 100;

    public function __construct() {
        $this->db = getDB();
    }

    public function all(int $limit = 50): array {
        $limit = max(1, min(200, $limit));
        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS created_by_name
            FROM sms_campaigns c
            JOIN users u ON c.created_by = u.id
            ORDER BY c.created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name AS created_by_name
            FROM sms_campaigns c
            JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getRecipients(int $campaignId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, cust.name AS customer_name
            FROM sms_campaign_recipients r
            LEFT JOIN customers cust ON r.customer_id = cust.id
            WHERE r.campaign_id = ?
            ORDER BY r.id
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    /**
     * Normalize Ghana / international numbers to digits-only E.164-ish (233…).
     */
    public static function normalizePhone(string $raw): ?string {
        $p = preg_replace('/[\s\-\.\(\)]+/', '', trim($raw)) ?? '';
        if ($p === '') return null;
        if (str_starts_with($p, '+')) {
            $p = substr($p, 1);
        }
        if (!ctype_digit($p)) return null;
        // Local Ghana: 0XXXXXXXXX → 233XXXXXXXXX
        if (strlen($p) === 10 && str_starts_with($p, '0')) {
            $p = '233' . substr($p, 1);
        }
        // Already without country: 9-digit mobile after leading 0 stripped somehow
        if (strlen($p) === 9 && preg_match('/^[235]/', $p)) {
            $p = '233' . $p;
        }
        if (strlen($p) < 10 || strlen($p) > 15) return null;
        return $p;
    }

    /**
     * Parse free-text numbers (comma / newline / semicolon separated).
     * @return list<string>
     */
    public static function parseCustomNumbers(string $text): array {
        $parts = preg_split('/[\s,;]+/', $text) ?: [];
        $out = [];
        $seen = [];
        foreach ($parts as $part) {
            $n = self::normalizePhone($part);
            if ($n === null || isset($seen[$n])) continue;
            $seen[$n] = true;
            $out[] = $n;
        }
        return $out;
    }

    /**
     * Create campaign, resolve recipients, send via Arkesel, update statuses.
     *
     * @param array{title?:string,message:string,include_customers:bool,custom_numbers:string} $data
     * @return array{id:int,sent:int,failed:int,total:int,status:string}
     */
    public function createAndSend(array $data, int $userId): array {
        $message = trim((string)($data['message'] ?? ''));
        if ($message === '') {
            throw new InvalidArgumentException('Message is required.');
        }
        if (mb_strlen($message) > 1000) {
            throw new InvalidArgumentException('Message is too long (max 1000 characters).');
        }
        if (!defined('ARKESEL_API_KEY') || ARKESEL_API_KEY === '' || ARKESEL_API_KEY === 'YOUR_ARKESEL_API_KEY') {
            throw new RuntimeException('Arkesel API key is not configured. Set ARKESEL_API_KEY in config/local.php.');
        }

        $includeCustomers = !empty($data['include_customers']);
        $custom = self::parseCustomNumbers((string)($data['custom_numbers'] ?? ''));

        /** @var array<string, array{phone:string,customer_id:?int}> $byPhone */
        $byPhone = [];

        if ($includeCustomers) {
            $cm = new CustomerModel();
            foreach ($cm->withPhones() as $c) {
                $n = self::normalizePhone((string)($c['phone'] ?? ''));
                if ($n === null) continue;
                if (!isset($byPhone[$n])) {
                    $byPhone[$n] = ['phone' => $n, 'customer_id' => (int)$c['id']];
                }
            }
        }

        foreach ($custom as $n) {
            if (!isset($byPhone[$n])) {
                $byPhone[$n] = ['phone' => $n, 'customer_id' => null];
            }
        }

        if (empty($byPhone)) {
            throw new InvalidArgumentException('No valid phone numbers to send to.');
        }

        $audience = 'custom';
        if ($includeCustomers && !empty($custom)) {
            $audience = 'mixed';
        } elseif ($includeCustomers) {
            $audience = 'all_customers';
        }

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $title = mb_substr($message, 0, 60) . (mb_strlen($message) > 60 ? '…' : '');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sms_campaigns
                    (title, message, audience, recipient_count, status, created_by)
                VALUES (?,?,?,?, 'sending', ?)
            ");
            $stmt->execute([$title, $message, $audience, count($byPhone), $userId]);
            $campaignId = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare("
                INSERT INTO sms_campaign_recipients (campaign_id, phone, customer_id, status)
                VALUES (?,?,?,'pending')
            ");
            foreach ($byPhone as $row) {
                $ins->execute([$campaignId, $row['phone'], $row['customer_id']]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $phones = array_keys($byPhone);
        $result = $this->sendBatches($phones, $message);

        $sent = $result['sent'];
        $failed = $result['failed'];
        $status = 'sent';
        if ($sent === 0 && $failed > 0) {
            $status = 'failed';
        } elseif ($failed > 0) {
            $status = 'partial';
        }

        // Mark recipient rows from batch results
        $updSent = $this->db->prepare("
            UPDATE sms_campaign_recipients SET status='sent', error_msg=NULL
            WHERE campaign_id=? AND phone=?
        ");
        $updFail = $this->db->prepare("
            UPDATE sms_campaign_recipients SET status='failed', error_msg=?
            WHERE campaign_id=? AND phone=?
        ");
        foreach ($result['ok'] as $phone) {
            $updSent->execute([$campaignId, $phone]);
        }
        foreach ($result['err'] as $phone => $err) {
            $updFail->execute([mb_substr((string)$err, 0, 250), $campaignId, $phone]);
        }

        $apiJson = json_encode($result['responses'], JSON_UNESCAPED_UNICODE);
        $this->db->prepare("
            UPDATE sms_campaigns
            SET sent_count=?, failed_count=?, status=?, api_response=?, sent_at=NOW()
            WHERE id=?
        ")->execute([$sent, $failed, $status, $apiJson, $campaignId]);

        return [
            'id'     => $campaignId,
            'sent'   => $sent,
            'failed' => $failed,
            'total'  => count($phones),
            'status' => $status,
        ];
    }

    /**
     * @param list<string> $phones
     * @return array{sent:int,failed:int,ok:list<string>,err:array<string,string>,responses:list<mixed>}
     */
    private function sendBatches(array $phones, string $message): array {
        $ok = [];
        $err = [];
        $responses = [];
        $sender = defined('ARKESEL_SENDER') && ARKESEL_SENDER !== ''
            ? ARKESEL_SENDER
            : 'AmazingFeet';

        foreach (array_chunk($phones, self::BATCH_SIZE) as $batch) {
            $payload = [
                'sender'     => $sender,
                'message'    => $message,
                'recipients' => $batch,
            ];
            $res = $this->httpPostJson(self::SEND_URL, $payload);
            $responses[] = $res['body'];

            $success = $res['http'] >= 200 && $res['http'] < 300
                && $this->responseLooksSuccessful($res['body']);

            if ($success) {
                foreach ($batch as $p) {
                    $ok[] = $p;
                }
            } else {
                $msg = $this->extractError($res);
                foreach ($batch as $p) {
                    $err[$p] = $msg;
                }
            }
        }

        return [
            'sent'      => count($ok),
            'failed'    => count($err),
            'ok'        => $ok,
            'err'       => $err,
            'responses' => $responses,
        ];
    }

    /** @param mixed $body */
    private function responseLooksSuccessful($body): bool {
        if (!is_array($body)) return false;
        $status = strtolower((string)($body['status'] ?? $body['code'] ?? ''));
        if (in_array($status, ['success', 'ok', '200', '100'], true)) return true;
        // Some responses nest under data
        if (isset($body['data']) && !isset($body['error'])) return true;
        if (($body['success'] ?? null) === true) return true;
        return false;
    }

    /** @param array{http:int,body:mixed,raw:string,error:?string} $res */
    private function extractError(array $res): string {
        if ($res['error']) return $res['error'];
        $body = $res['body'];
        if (is_array($body)) {
            foreach (['message', 'error', 'status'] as $k) {
                if (!empty($body[$k]) && is_string($body[$k])) {
                    return $body[$k];
                }
            }
            return 'Arkesel rejected the request (HTTP ' . $res['http'] . ').';
        }
        if ($res['http'] === 0) return 'Could not reach Arkesel API.';
        return 'Arkesel error (HTTP ' . $res['http'] . ').';
    }

    /**
     * Optional balance check for the compose UI.
     * @return array{ok:bool,balance:?string,raw?:mixed,error?:string}
     */
    public function checkBalance(): array {
        if (!defined('ARKESEL_API_KEY') || ARKESEL_API_KEY === '' || ARKESEL_API_KEY === 'YOUR_ARKESEL_API_KEY') {
            return ['ok' => false, 'balance' => null, 'error' => 'API key not configured'];
        }
        $res = $this->httpGet(self::BALANCE_URL);
        if ($res['http'] < 200 || $res['http'] >= 300) {
            return ['ok' => false, 'balance' => null, 'error' => $this->extractError($res), 'raw' => $res['body']];
        }
        $body = $res['body'];
        $bal = null;
        if (is_array($body)) {
            $bal = $body['data']['sms_balance']
                ?? $body['data']['balance']
                ?? $body['balance']
                ?? $body['sms_balance']
                ?? null;
            if (is_array($bal)) {
                $bal = $bal['sms_balance'] ?? $bal['balance'] ?? json_encode($bal);
            }
        }
        return ['ok' => true, 'balance' => $bal !== null ? (string)$bal : null, 'raw' => $body];
    }

    /** @return array{http:int,body:mixed,raw:string,error:?string} */
    private function httpPostJson(string $url, array $payload): array {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['http' => 0, 'body' => null, 'raw' => '', 'error' => 'Failed to encode JSON'];
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'api-key: ' . ARKESEL_API_KEY,
                ],
            ]);
            $raw = curl_exec($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                return ['http' => 0, 'body' => null, 'raw' => '', 'error' => $cerr ?: 'cURL failed'];
            }
            $decoded = json_decode($raw, true);
            return ['http' => $http, 'body' => $decoded ?? $raw, 'raw' => $raw, 'error' => null];
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAccept: application/json\r\napi-key: " . ARKESEL_API_KEY . "\r\n",
                'content' => $json,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        $http = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $http = (int)$m[1];
        }
        if ($raw === false) {
            return ['http' => 0, 'body' => null, 'raw' => '', 'error' => 'HTTP request failed'];
        }
        $decoded = json_decode($raw, true);
        return ['http' => $http, 'body' => $decoded ?? $raw, 'raw' => $raw, 'error' => null];
    }

    /** @return array{http:int,body:mixed,raw:string,error:?string} */
    private function httpGet(string $url): array {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'api-key: ' . ARKESEL_API_KEY,
                ],
            ]);
            $raw = curl_exec($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                return ['http' => 0, 'body' => null, 'raw' => '', 'error' => $cerr ?: 'cURL failed'];
            }
            $decoded = json_decode($raw, true);
            return ['http' => $http, 'body' => $decoded ?? $raw, 'raw' => $raw, 'error' => null];
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\napi-key: " . ARKESEL_API_KEY . "\r\n",
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        $http = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $http = (int)$m[1];
        }
        if ($raw === false) {
            return ['http' => 0, 'body' => null, 'raw' => '', 'error' => 'HTTP request failed'];
        }
        $decoded = json_decode($raw, true);
        return ['http' => $http, 'body' => $decoded ?? $raw, 'raw' => $raw, 'error' => null];
    }
}
