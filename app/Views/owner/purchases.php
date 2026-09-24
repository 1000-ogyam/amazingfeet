<?php
$pageTitle = 'Purchases';
$cp = '/purchases';
ob_start();

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
    <span class="text-muted text-sm"><?= count($orders) ?> shown</span>
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
</div>
<?php
$content = ob_get_clean();
require APP_ROOT.'/Views/layouts/main.php';
