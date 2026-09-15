<?php
$pageTitle = 'Dashboard'; $cp = '/dashboard';
ob_start();
?>
<!-- Stats row -->
<div class="stats-grid">
  <div class="stat-card revenue">
    <div class="stat-label"><i class="fa-solid fa-coins" aria-hidden="true"></i> Today's Revenue</div>
    <div class="stat-value"><?= money($today['revenue']??0) ?></div>
    <div class="stat-sub"><?= $today['num_sales']??0 ?> sales · <?= $today['units_sold']??0 ?> units</div>
  </div>
  <div class="stat-card revenue">
    <div class="stat-label"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i> This Week Revenue</div>
    <div class="stat-value"><?= money($week['revenue']??0) ?></div>
    <div class="stat-sub"><?= $week['units_sold']??0 ?> units sold</div>
  </div>
  <div class="stat-card stock">
    <div class="stat-label"><i class="fa-solid fa-box" aria-hidden="true"></i> Stock Retail Value</div>
    <div class="stat-value"><?= money($stockValue['retail_value']??0) ?></div>
    <div class="stat-sub"><?= number_format($stockValue['total_units']??0) ?> units in stock</div>
  </div>
  <div class="stat-card units">
    <div class="stat-label"><i class="fa-solid fa-tags" aria-hidden="true"></i> Stock Cost Value</div>
    <div class="stat-value"><?= money($stockValue['cost_value']??0) ?></div>
    <div class="stat-sub">Owner view only</div>
  </div>
</div>

<?php if (!empty($lowStock)): ?>
<div class="alert-bar">
  <span class="alert-bar-ic"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
  <span class="ab-count"><?= count($lowStock) ?></span>
  <span>products are low on stock</span>
  <a href="<?= BASE_PATH ?>/products?low_stock=1" class="btn btn-sm btn-ghost ml-auto">View All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
</div>
<?php endif; ?>

<div class="dash-grid-2">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Weekly Targets</h3>
      <a href="<?= BASE_PATH ?>/targets" class="btn btn-ghost btn-sm">Manage <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="card-body">
      <?php if (empty($targets)): ?>
      <p class="text-muted text-sm">No targets set for this week. <a href="<?= BASE_PATH ?>/targets" class="dash-link">Set one <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
      <?php else: ?>
      <?php foreach ($targets as $t): ?>
      <div class="dash-target-block">
        <div class="flex-center gap-1 mb-1">
          <span class="dash-target-name"><?= e($t['category_name'] ?? 'Overall') ?></span>
          <span class="text-muted text-sm ml-auto"><?= $t['actual_units'] ?> / <?= $t['target_units'] ?> units</span>
          <span class="dash-target-pct"><?= $t['pct_units'] ?>%</span>
        </div>
        <div class="progress-wrap">
          <div class="progress-bar <?= $t['pct_units']>=100?'green':($t['pct_units']>=60?'amber':'red') ?>"
               style="width:<?= min(100, (float)$t['pct_units']) ?>%"></div>
        </div>
        <div class="text-muted text-sm mt-1">Revenue: <?= money($t['actual_revenue']) ?> / <?= money($t['target_revenue']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-trophy" aria-hidden="true"></i> Top Sellers This Week</h3></div>
    <?php if (empty($topSizes)): ?>
    <div class="card-body text-muted text-sm">No sales this week yet.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Product</th><th>Size</th><th>Sold</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($topSizes,0,6) as $p): ?>
        <tr>
          <td>
            <div class="dash-prod-name"><?= e($p['name']) ?></div>
            <div class="text-muted text-sm"><?= e($p['gender']) ?> <?= $p['design']?'· '.e($p['design']):'' ?></div>
          </td>
          <td><span class="badge badge-school">Sz <?= e($p['size']) ?></span></td>
          <td class="font-bold"><?= $p['units_sold'] ?></td>
          <td class="text-success font-bold"><?= money($p['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="dash-grid-chart">
  <div class="chart-box">
    <div class="flex-center gap-1 mb-2">
      <h3 class="dash-chart-title"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Revenue — Last 30 Days</h3>
    </div>
    <canvas id="revenueChart" height="90"></canvas>
  </div>
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-clock" aria-hidden="true"></i> Recent Sales</h3>
      <a href="<?= BASE_PATH ?>/sales" class="btn btn-ghost btn-sm">All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="dash-recent-list">
      <?php if (empty($recentSales)): ?>
      <div class="card-body text-muted text-sm">No recent sales.</div>
      <?php else: ?>
      <?php foreach (array_slice($recentSales,0,8) as $s): ?>
      <a href="<?= BASE_PATH ?>/sales/<?= $s['id'] ?>" class="dash-recent-item">
        <div class="dash-recent-meta">
          <div class="dash-recent-ref"><?= e($s['sale_ref']) ?></div>
          <div class="text-muted text-sm"><?= e($s['staff_name']) ?> · <?= e($s['location_name']) ?></div>
        </div>
        <div class="dash-recent-total"><?= money($s['total']) ?></div>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels  = <?= json_encode(array_column($chartData,'day')) ?>;
const revenue = <?= json_encode(array_map(fn($r)=>(float)$r['revenue'], $chartData)) ?>;
new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Revenue (GHS)',
      data: revenue,
      backgroundColor: 'rgba(200,83,42,.2)',
      borderColor: '#c8532a',
      borderWidth: 2,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
      y: { grid: { color: '#e8e2d9' }, ticks: { callback: v=>'GHS '+v.toLocaleString() } }
    }
  }
});
</script>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
