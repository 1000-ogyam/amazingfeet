<?php
$pageTitle = 'Stock History';
$cp = '/stock-history';
ob_start();

$sourceBadge = static function (string $source): string {
    $map = [
        'purchase' => 'badge-ok',
        'return'   => 'badge-momo',
        'manual'   => 'badge-card',
    ];
    $cls = $map[$source] ?? 'badge-ok';
    return '<span class="badge '.$cls.'">'.e(ucfirst($source)).'</span>';
};
?>
<div class="products-toolbar">
  <form method="GET" class="products-filters" id="stockHistoryFilter">
    <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Product, note, user…">
    <select name="source">
      <option value="">All sources</option>
      <option value="purchase" <?= ($filters['source'] ?? '') === 'purchase' ? 'selected' : '' ?>>Purchase receive</option>
      <option value="return" <?= ($filters['source'] ?? '') === 'return' ? 'selected' : '' ?>>Return</option>
      <option value="manual" <?= ($filters['source'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
    </select>
    <select name="type">
      <option value="">All types</option>
      <?php foreach (['addition','return','correction','damaged'] as $t): ?>
      <option value="<?= $t ?>" <?= ($filters['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" title="From">
    <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" title="To">
    <div class="products-filter-actions">
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a href="<?= BASE_PATH ?>/stock-history" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Stock movements</h3>
    <span class="text-muted text-sm"><?= count($history) ?> shown</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>When</th>
          <th>Product</th>
          <th>Source</th>
          <th>Type</th>
          <th>Qty</th>
          <th>Note</th>
          <th>By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($history)): ?>
        <tr><td colspan="7" class="products-empty">No stock adjustments yet. Receives, returns, and manual changes appear here.</td></tr>
        <?php endif; ?>
        <?php foreach ($history as $h): ?>
        <tr>
          <td class="text-muted text-sm"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></td>
          <td>
            <a href="<?= BASE_PATH ?>/products/<?= (int)$h['product_id'] ?>/edit" class="font-bold text-sm">
              <?= e($h['product_name']) ?>
            </a>
            <div class="text-muted" style="font-size:.72rem">
              Sz <?= e($h['size']) ?>
              <?= !empty($h['design']) ? ' · '.e($h['design']) : '' ?>
              <?= !empty($h['sku']) ? ' · '.e($h['sku']) : '' ?>
            </div>
          </td>
          <td><?= $sourceBadge($h['source'] ?? 'manual') ?></td>
          <td class="text-sm"><?= e($h['type']) ?></td>
          <td class="mono font-bold" style="color:<?= (int)$h['quantity'] >= 0 ? 'var(--accent2)' : 'var(--danger)' ?>">
            <?= (int)$h['quantity'] >= 0 ? '+' : '' ?><?= (int)$h['quantity'] ?>
          </td>
          <td class="text-sm"><?= e($h['note'] ?? '') ?></td>
          <td class="text-sm"><?= e($h['user_name']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
