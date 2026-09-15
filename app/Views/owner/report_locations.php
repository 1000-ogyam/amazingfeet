<?php $pageTitle='Location Report'; $cp='/reports/locations'; ob_start(); ?>
<div class="flex-center gap-2 mb-2">
  <form method="GET" class="flex-center gap-1" style="flex-wrap:wrap">
    <label style="text-transform:none;letter-spacing:0;font-weight:400;font-size:.9rem">From:</label>
    <input type="date" name="start" value="<?= e($start) ?>" style="width:auto">
    <label style="text-transform:none;letter-spacing:0;font-weight:400;font-size:.9rem">To:</label>
    <input type="date" name="end" value="<?= e($end) ?>" style="width:auto">
    <button type="submit" class="btn btn-primary btn-sm">View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
  </form>
  <button type="button" onclick="window.print()" class="btn btn-ghost btn-sm ml-auto no-print"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
</div>

<div class="card">
  <div class="card-header"><h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sales by Location — <?= date('d M Y', strtotime($start)) ?> to <?= date('d M Y', strtotime($end)) ?></h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Location</th><th>Type</th><th>Transactions</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php if(empty($data)): ?>
        <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--muted)">No sales data for this period.</td></tr>
        <?php endif; ?>
        <?php foreach($data as $r): ?>
        <tr>
          <td class="font-bold"><?= e($r['location']) ?></td>
          <td><span class="badge badge-<?= $r['type']==='shop'?'ok':'school' ?>"><?= ucfirst($r['type']) ?></span></td>
          <td><?= $r['num_sales'] ?></td>
          <td><?= $r['units'] ?></td>
          <td class="font-bold text-accent"><?= money($r['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Visual breakdown -->
<?php if(!empty($data)): ?>
<div class="card" style="margin-top:1.25rem">
  <div class="card-header"><h3><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Revenue Share by Location</h3></div>
  <div class="card-body">
    <?php
    $totalRev = array_sum(array_column($data,'revenue'));
    foreach($data as $r):
      $pct = $totalRev>0 ? round($r['revenue']/$totalRev*100) : 0;
    ?>
    <div style="margin-bottom:1rem">
      <div class="flex-center gap-1 mb-1">
        <span class="font-bold text-sm"><?= e($r['location']) ?></span>
        <span class="text-muted text-sm ml-auto"><?= money($r['revenue']) ?> (<?= $pct ?>%)</span>
      </div>
      <div class="progress-wrap">
        <div class="progress-bar green" style="width:<?= $pct ?>%"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
