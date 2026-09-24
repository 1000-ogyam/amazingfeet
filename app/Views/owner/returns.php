<?php
$pageTitle = 'Returns';
$cp = '/returns';
ob_start();
?>
<div class="products-toolbar">
  <form method="GET" class="products-filters">
    <input type="date" name="date" value="<?= e($filters['date'] ?? '') ?>">
    <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Return ref, sale ref…">
    <div class="products-filter-actions">
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a href="<?= BASE_PATH ?>/returns" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Returns &amp; exchanges</h3>
    <span class="text-muted text-sm"><?= count($returns) ?> shown</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Return ref</th>
          <th>Sale</th>
          <th>Refund</th>
          <th>Exchange</th>
          <th>Method</th>
          <th>By</th>
          <th>When</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($returns)): ?>
        <tr><td colspan="8" class="products-empty">No returns yet. Open a sale and choose Return / Exchange.</td></tr>
        <?php endif; ?>
        <?php foreach ($returns as $r): ?>
        <tr>
          <td class="mono font-bold"><?= e($r['return_ref']) ?></td>
          <td><a href="<?= BASE_PATH ?>/sales/<?= (int)$r['sale_id'] ?>"><?= e($r['sale_ref']) ?></a></td>
          <td><?= money($r['refund_amount']) ?></td>
          <td><?= money($r['exchange_amount']) ?></td>
          <td class="text-sm"><?= e(str_replace('_', ' ', $r['refund_method'])) ?></td>
          <td class="text-sm"><?= e($r['processed_by_name']) ?></td>
          <td class="text-muted text-sm"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
          <td><a href="<?= BASE_PATH ?>/returns/<?= (int)$r['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
