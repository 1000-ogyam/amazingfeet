<?php $pageTitle='Weekly Report'; $cp='/reports/weekly'; ob_start(); ?>
<div class="flex-center gap-2 mb-2">
  <form method="GET" class="flex-center gap-1">
    <label style="text-transform:none;letter-spacing:0;font-weight:400;font-size:.9rem">Week of:</label>
    <input type="date" name="week_start" value="<?= e($weekStart) ?>" style="width:auto">
    <button type="submit" class="btn btn-primary btn-sm">View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
  </form>
  <button type="button" onclick="window.print()" class="btn btn-ghost btn-sm ml-auto no-print"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
</div>
<p class="text-muted text-sm mb-2"><?= date('d M Y', strtotime($weekStart)) ?> — <?= date('d M Y', strtotime($weekEnd)) ?></p>

<div class="stats-grid">
  <div class="stat-card revenue"><div class="stat-label"><i class="fa-solid fa-coins" aria-hidden="true"></i> Weekly Revenue</div><div class="stat-value"><?= money($sum['revenue']??0) ?></div></div>
  <div class="stat-card units"><div class="stat-label"><i class="fa-solid fa-shoe-prints" aria-hidden="true"></i> Units Sold</div><div class="stat-value"><?= $sum['units_sold']??0 ?></div></div>
  <div class="stat-card revenue"><div class="stat-label"><i class="fa-solid fa-receipt" aria-hidden="true"></i> Transactions</div><div class="stat-value"><?= $sum['num_sales']??0 ?></div></div>
  <div class="stat-card stock"><div class="stat-label"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Gross Profit</div><div class="stat-value"><?= money($sum['gross_profit']??0) ?></div><div class="stat-sub">Owner only</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-tags" aria-hidden="true"></i> By Category</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Category</th><th>Units</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach($byCat as $r): ?>
        <tr><td class="font-bold text-sm"><?= e($r['category']) ?></td><td><?= $r['units'] ?></td><td class="text-accent font-bold"><?= money($r['revenue']) ?></td></tr>
        <?php endforeach; ?>
        <?php if(empty($byCat)): ?><tr><td colspan="3" class="text-muted text-sm" style="padding:1rem">No data</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i> By Location</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Location</th><th>Sales</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach($byLoc as $r): ?>
        <tr><td class="font-bold text-sm"><?= e($r['location']) ?></td><td><?= $r['num_sales'] ?></td><td class="text-accent font-bold"><?= money($r['revenue']) ?></td></tr>
        <?php endforeach; ?>
        <?php if(empty($byLoc)): ?><tr><td colspan="3" class="text-muted text-sm" style="padding:1rem">No data</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-users" aria-hidden="true"></i> Staff Performance</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Staff</th><th>Sales</th><th>Units</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach($byStaff as $r): ?>
        <tr>
          <td class="font-bold text-sm"><?= e($r['staff_name']) ?></td>
          <td><?= $r['num_sales'] ?></td>
          <td><?= $r['units_sold'] ?></td>
          <td class="text-accent font-bold"><?= money($r['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($byStaff)): ?><tr><td colspan="4" class="text-muted text-sm" style="padding:1rem">No data</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-trophy" aria-hidden="true"></i> Top Selling Sizes</h3></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Product</th><th>Sz</th><th>Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach(array_slice($topSellers,0,8) as $p): ?>
        <tr>
          <td>
            <div class="text-sm font-bold"><?= e($p['name']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= e($p['gender']) ?> · <?= e($p['design']??'') ?></div>
          </td>
          <td><strong>Sz <?= e($p['size']) ?></strong></td>
          <td class="font-bold"><?= $p['units_sold'] ?></td>
          <td class="text-accent"><?= money($p['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($topSellers)): ?><tr><td colspan="4" class="text-muted text-sm" style="padding:1rem">No data</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
