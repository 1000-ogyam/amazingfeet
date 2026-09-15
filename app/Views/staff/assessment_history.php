<?php
$pageTitle = 'My Daily Reports';
$cp = '/assessment';
$backUrl = BASE_PATH.'/assessment';
ob_start();
?>
<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> My submitted reports</h3>
    <a href="<?= BASE_PATH ?>/assessment" class="btn btn-primary btn-sm ml-auto"><i class="fa-solid fa-plus" aria-hidden="true"></i> Today’s report</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Date</th><th>Pairs (day)</th><th>Month</th><th>Commission</th><th>Contacts</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No reports yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['report_date'])) ?></td>
          <td class="font-bold"><?= (int)$r['pairs_day'] ?></td>
          <td><?= (int)$r['pairs_month'] ?> / <?= (int)$r['month_target'] ?></td>
          <td class="text-sm"><?= e($r['commission_level']) ?></td>
          <td class="text-sm"><?= (int)$r['contacts_collected'] ?> / <?= (int)$r['customers_today'] ?></td>
          <td><a href="<?= BASE_PATH ?>/assessment?date=<?= e($r['report_date']) ?>" class="btn btn-ghost btn-xs">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
