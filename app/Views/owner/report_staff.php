<?php $pageTitle='Staff Performance Report'; $cp='/reports/staff'; ob_start(); ?>
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
  <div class="card-header"><h3><i class="fa-solid fa-user" aria-hidden="true"></i> Staff Sales Performance — <?= date('d M Y', strtotime($start)) ?> to <?= date('d M Y', strtotime($end)) ?></h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Staff Member</th><th>Transactions</th><th>Units Sold</th><th>Revenue</th><th>Avg. Sale Value</th></tr></thead>
      <tbody>
        <?php if(empty($data)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No sales data for this period.</td></tr>
        <?php endif; ?>
        <?php foreach($data as $i => $r):
          $avg = $r['num_sales']>0 ? $r['revenue']/$r['num_sales'] : 0;
        ?>
        <tr>
          <td style="color:var(--muted);font-weight:700">#<?= $i+1 ?></td>
          <td>
            <div class="font-bold"><?= e($r['staff_name']) ?></div>
          </td>
          <td><?= $r['num_sales'] ?></td>
          <td><?= $r['units_sold'] ?></td>
          <td class="font-bold text-accent"><?= money($r['revenue']) ?></td>
          <td class="text-muted text-sm"><?= money($avg) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
