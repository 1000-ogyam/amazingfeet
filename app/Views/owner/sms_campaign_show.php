<?php
$pageTitle = 'SMS Campaign #' . (int)$campaign['id'];
$cp = '/sms';
ob_start();

$statusBadge = static function (string $status): string {
    $map = [
        'queued'  => 'badge-card',
        'sending' => 'badge-momo',
        'sent'    => 'badge-ok',
        'partial' => 'badge-momo',
        'failed'  => 'badge-low',
        'pending' => 'badge-card',
        'skipped' => 'badge-card',
    ];
    $cls = $map[$status] ?? 'badge-ok';
    return '<span class="badge '.$cls.'">'.e(ucfirst($status)).'</span>';
};
?>
<div class="products-toolbar">
  <a href="<?= BASE_PATH ?>/sms" class="btn btn-ghost btn-sm">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All campaigns
  </a>
  <a href="<?= BASE_PATH ?>/sms/create" class="btn btn-primary btn-sm">
    <i class="fa-solid fa-plus" aria-hidden="true"></i> New campaign
  </a>
</div>

<div class="card mb-2">
  <div class="card-header">
    <h3><?= e($campaign['title'] ?: 'Campaign') ?></h3>
    <?= $statusBadge($campaign['status']) ?>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1rem">
      <div>
        <p class="text-muted text-sm" style="margin:0">Audience</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= e(str_replace('_', ' ', $campaign['audience'])) ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Recipients</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= (int)$campaign['recipient_count'] ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Sent</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= (int)$campaign['sent_count'] ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Failed</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= (int)$campaign['failed_count'] ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Sent by</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= e($campaign['created_by_name'] ?? '') ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">When</p>
        <p class="font-bold" style="margin:.15rem 0 0">
          <?= date('d M Y H:i', strtotime($campaign['sent_at'] ?? $campaign['created_at'])) ?>
        </p>
      </div>
    </div>
    <div>
      <p class="text-muted text-sm" style="margin:0 0 .35rem">Message</p>
      <div style="white-space:pre-wrap;background:var(--surface-2,rgba(0,0,0,.04));padding:.75rem 1rem;border-radius:8px">
<?= e($campaign['message']) ?></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i> Recipients</h3>
    <span class="text-muted text-sm"><?= count($recipients) ?></span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Phone</th>
          <th>Customer</th>
          <th>Status</th>
          <th>Error</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recipients)): ?>
        <tr><td colspan="4" class="products-empty">No recipients logged.</td></tr>
        <?php endif; ?>
        <?php foreach ($recipients as $r): ?>
        <tr>
          <td class="mono text-sm"><?= e($r['phone']) ?></td>
          <td><?= $r['customer_name'] ? e($r['customer_name']) : '<span class="text-muted">Custom</span>' ?></td>
          <td><?= $statusBadge($r['status']) ?></td>
          <td class="text-muted text-sm"><?= e($r['error_msg'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
