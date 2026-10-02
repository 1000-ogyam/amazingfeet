<?php
$pageTitle = 'Purchases';
$cp = '/purchases';
ob_start();

$pagination = $pagination ?? ['page' => 1, 'perPage' => 10, 'total' => count($orders), 'totalPages' => 1];
$page = (int)$pagination['page'];
$perPage = (int)$pagination['perPage'];
$total = (int)$pagination['total'];
$totalPages = (int)$pagination['totalPages'];
$from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to = min($page * $perPage, $total);
$qs = static function (array $extra = []) use ($filters): string {
    $params = array_filter(array_merge($filters, $extra), static fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
};

$statusBadge = static function (string $status): string {
    $map = [
        'draft'     => 'badge-ok',
        'ordered'   => 'badge-card',
        'partial'   => 'badge-momo',
        'received'  => 'badge-ok',
        'cancelled' => 'badge-low',
    ];
    $cls = $map[$status] ?? 'badge-ok';
    return '<span class="badge '.$cls.'">'.e(ucfirst($status)).'</span>';
};
?>
<div class="products-toolbar">
  <form method="GET" class="products-filters" id="purchasesFilterForm">
    <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search PO ref, supplier…">
    <select name="status">
      <option value="">All statuses</option>
      <?php foreach (['draft','ordered','partial','received','cancelled'] as $st): ?>
      <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="products-filter-actions">
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a href="<?= BASE_PATH ?>/purchases" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
    </div>
  </form>
  <a href="<?= BASE_PATH ?>/purchases/create" class="btn btn-primary products-add-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> New purchase</a>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i> Purchase orders</h3>
    <span class="text-muted text-sm"><?= $total ? "Showing {$from}–{$to} of {$total}" : 'None found' ?></span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>PO ref</th>
          <th>Supplier</th>
          <th>Status</th>
          <th>Lines</th>
          <th>Units</th>
          <th>Cost</th>
          <th>Created</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="8" class="products-empty">No purchase orders yet. Create one to restock.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="mono font-bold"><?= e($o['po_ref']) ?></td>
          <td><?= e($o['supplier'] ?: '—') ?></td>
          <td><?= $statusBadge($o['status']) ?></td>
          <td><?= (int)$o['line_count'] ?></td>
          <td>
            <strong><?= (int)$o['units_received'] ?></strong>
            <span class="text-muted text-sm">/ <?= (int)$o['units_ordered'] ?></span>
          </td>
          <td class="text-sm"><?= money($o['total_cost'] ?? 0) ?></td>
          <td class="text-muted text-sm">
            <?= date('d M Y', strtotime($o['created_at'])) ?>
            <div class="text-muted" style="font-size:.72rem"><?= e($o['created_by_name'] ?? '') ?></div>
          </td>
          <td class="products-actions">
            <a href="<?= BASE_PATH ?>/purchases/<?= (int)$o['id'] ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-eye" aria-hidden="true"></i> Open</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="products-pager">
    <div class="products-pager-info text-muted text-sm">Page <?= $page ?> of <?= $totalPages ?></div>
    <div class="products-pager-btns">
      <?php if ($page > 1): ?>
      <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/purchases?<?= e($qs(['page' => 1])) ?>" title="First"><i class="fa-solid fa-angles-left" aria-hidden="true"></i></a>
      <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/purchases?<?= e($qs(['page' => $page - 1])) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev</a>
      <?php else: ?>
      <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-angles-left" aria-hidden="true"></i></button>
      <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev</button>
      <?php endif; ?>
      <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<span class="products-pager-ellipsis">…</span>';
        for ($i = $start; $i <= $end; $i++):
          if ($i === $page):
      ?>
      <span class="btn btn-primary btn-sm products-page-current"><?= $i ?></span>
      <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/purchases?<?= e($qs(['page' => $i])) ?>"><?= $i ?></a>
      <?php
          endif;
        endfor;
        if ($end < $totalPages) echo '<span class="products-pager-ellipsis">…</span>';
      ?>
      <?php if ($page < $totalPages): ?>
      <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/purchases?<?= e($qs(['page' => $page + 1])) ?>">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
      <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/purchases?<?= e($qs(['page' => $totalPages])) ?>" title="Last"><i class="fa-solid fa-angles-right" aria-hidden="true"></i></a>
      <?php else: ?>
      <button type="button" class="btn btn-ghost btn-sm" disabled>Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
      <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-angles-right" aria-hidden="true"></i></button>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require APP_ROOT.'/Views/layouts/main.php';
