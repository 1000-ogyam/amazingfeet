<?php
$pageTitle = 'SMS Campaigns';
$cp = '/sms';
ob_start();

$statusBadge = static function (string $status): string {
    $map = [
        'queued'  => 'badge-card',
        'sending' => 'badge-momo',
        'sent'    => 'badge-ok',
        'partial' => 'badge-momo',
        'failed'  => 'badge-low',
    ];
    $cls = $map[$status] ?? 'badge-ok';
    return '<span class="badge '.$cls.'">'.e(ucfirst($status)).'</span>';
};
?>
<div class="products-toolbar">
  <p class="text-muted text-sm" style="margin:0">
    Send bulk SMS via Arkesel to customers or custom numbers.
    <?= (int)$customerPhoneCount ?> customers have a phone on file.
  </p>
  <a href="<?= BASE_PATH ?>/sms/create" class="btn btn-primary products-add-btn">
    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> New campaign
  </a>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-comment-sms" aria-hidden="true"></i> Campaign history</h3>
    <span class="text-muted text-sm"><?= count($campaigns) ?> shown</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Audience</th>
          <th>Recipients</th>
          <th>Sent / Failed</th>
          <th>Status</th>
          <th>By</th>
          <th>When</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($campaigns)): ?>
        <tr><td colspan="8" class="products-empty">No SMS campaigns yet. Create one to message customers.</td></tr>
        <?php endif; ?>
        <?php foreach ($campaigns as $c): ?>
        <tr>
          <td class="font-bold"><?= e($c['title'] ?: '—') ?></td>
          <td class="text-sm"><?= e(str_replace('_', ' ', $c['audience'])) ?></td>
          <td><?= (int)$c['recipient_count'] ?></td>
          <td class="mono text-sm"><?= (int)$c['sent_count'] ?> / <?= (int)$c['failed_count'] ?></td>
          <td><?= $statusBadge($c['status']) ?></td>
          <td class="text-sm"><?= e($c['created_by_name'] ?? '') ?></td>
          <td class="text-muted text-sm"><?= date('d M Y H:i', strtotime($c['sent_at'] ?? $c['created_at'])) ?></td>
          <td><a href="<?= BASE_PATH ?>/sms/<?= (int)$c['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
