<?php $pageTitle='Daily Report'; $cp='/reports/daily'; ob_start(); ?>
<div class="flex-center gap-2 mb-2">
  <form method="GET" class="flex-center gap-1">
    <label style="text-transform:none;letter-spacing:0;font-weight:400;font-size:.9rem">Date:</label>
    <input type="date" name="date" value="<?= e($date) ?>" style="width:auto">
    <button type="submit" class="btn btn-primary btn-sm">View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
  </form>
  <button type="button" onclick="window.print()" class="btn btn-ghost btn-sm ml-auto no-print"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
</div>

<!-- Summary -->
<div class="stats-grid">
  <div class="stat-card revenue">
    <div class="stat-label"><i class="fa-solid fa-coins" aria-hidden="true"></i> Total Revenue</div>
    <div class="stat-value"><?= money($sum['revenue']??0) ?></div>
  </div>
  <div class="stat-card units">
    <div class="stat-label"><i class="fa-solid fa-shoe-prints" aria-hidden="true"></i> Units Sold</div>
    <div class="stat-value"><?= $sum['units_sold']??0 ?></div>
  </div>
  <div class="stat-card revenue">
    <div class="stat-label"><i class="fa-solid fa-receipt" aria-hidden="true"></i> Num. Sales</div>
    <div class="stat-value"><?= $sum['num_sales']??0 ?></div>
  </div>
  <div class="stat-card stock">
    <div class="stat-label"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Gross Profit</div>
    <div class="stat-value"><?= money($sum['gross_profit']??0) ?></div>
    <div class="stat-sub">Owner only</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.25rem">

  <!-- By Category -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-tags" aria-hidden="true"></i> By Category</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Category</th><th>Units</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php if(empty($byCat)): ?><tr><td colspan="3" class="text-muted text-sm" style="padding:1rem">No sales</td></tr><?php endif; ?>
          <?php foreach($byCat as $r): ?>
          <tr>
            <td class="text-sm font-bold"><?= e($r['category']) ?></td>
            <td><?= $r['units'] ?></td>
            <td class="text-accent font-bold"><?= money($r['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- By Location -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i> By Location</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Location</th><th>Sales</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php if(empty($byLoc)): ?><tr><td colspan="3" class="text-muted text-sm" style="padding:1rem">No sales</td></tr><?php endif; ?>
          <?php foreach($byLoc as $r): ?>
          <tr>
            <td class="text-sm font-bold"><?= e($r['location']) ?></td>
            <td><?= $r['num_sales'] ?></td>
            <td class="text-accent font-bold"><?= money($r['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- By Staff -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-users" aria-hidden="true"></i> By Staff</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Staff</th><th>Sales</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php if(empty($byStaff)): ?><tr><td colspan="3" class="text-muted text-sm" style="padding:1rem">No sales</td></tr><?php endif; ?>
          <?php foreach($byStaff as $r): ?>
          <tr>
            <td class="text-sm font-bold"><?= e($r['staff_name']) ?></td>
            <td><?= $r['num_sales'] ?></td>
            <td class="text-accent font-bold"><?= money($r['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- All sales that day -->
<div class="card">
  <div class="card-header"><h3><i class="fa-solid fa-list" aria-hidden="true"></i> All Transactions — <?= date('d F Y', strtotime($date)) ?></h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ref</th><th>Time</th><th>Staff</th><th>Location</th><th>Payment</th><th>Total</th><th></th></tr></thead>
      <tbody>
        <?php if(empty($sales)): ?><tr><td colspan="7" style="text-align:center;padding:1.5rem;color:var(--muted)">No sales on this date.</td></tr><?php endif; ?>
        <?php foreach($sales as $s): ?>
        <tr>
          <td class="mono text-sm text-accent"><?= e($s['sale_ref']) ?></td>
          <td class="text-sm"><?= date('H:i', strtotime($s['created_at'])) ?></td>
          <td class="text-sm"><?= e($s['staff_name']) ?></td>
          <td class="text-sm"><?= e($s['location_name']) ?></td>
          <td><span class="badge badge-<?= $s['payment_method'] ?>"><?= strtoupper($s['payment_method']) ?></span></td>
          <td class="font-bold"><?= money($s['total']) ?></td>
          <td><a href="<?= BASE_PATH ?>/sales/<?= $s['id'] ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
