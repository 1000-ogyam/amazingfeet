<?php
$pageTitle = 'PO ' . ($order['po_ref'] ?? '');
$cp = '/purchases';
$backUrl = BASE_PATH.'/purchases';
ob_start();

$canReceive = in_array($order['status'], ['draft', 'ordered', 'partial'], true);
$canCancel = in_array($order['status'], ['draft', 'ordered'], true);
$canOrder = $order['status'] === 'draft';

$statusClass = [
    'draft' => 'badge-ok',
    'ordered' => 'badge-card',
    'partial' => 'badge-momo',
    'received' => 'badge-ok',
    'cancelled' => 'badge-low',
][$order['status']] ?? 'badge-ok';

$totalOrdered = 0;
$totalReceived = 0;
$totalCost = 0.0;
foreach ($items as $it) {
    $totalOrdered += (int)$it['quantity_ordered'];
    $totalReceived += (int)$it['quantity_received'];
    $totalCost += (int)$it['quantity_ordered'] * (float)$it['unit_cost'];
}
?>
<div class="flex-center gap-1 mb-2" style="flex-wrap:wrap">
  <div style="flex:1;min-width:12rem">
    <h2 style="margin:0;letter-spacing:-.02em"><?= e($order['po_ref']) ?></h2>
    <div class="text-muted text-sm" style="margin-top:.25rem">
      <?= e($order['supplier'] ?: 'No supplier') ?>
      · <span class="badge <?= $statusClass ?>"><?= e(ucfirst($order['status'])) ?></span>
    </div>
  </div>
  <?php if ($canOrder): ?>
  <form method="POST" action="<?= BASE_PATH ?>/purchases/<?= (int)$order['id'] ?>/order">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Mark ordered</button>
  </form>
  <?php endif; ?>
  <?php if ($canCancel): ?>
  <form method="POST" action="<?= BASE_PATH ?>/purchases/<?= (int)$order['id'] ?>/cancel" onsubmit="return confirm('Cancel this purchase order?')">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-ban" aria-hidden="true"></i> Cancel</button>
  </form>
  <?php endif; ?>
</div>

<div class="products-stats" style="margin-bottom:1rem">
  <div class="products-stat">
    <div class="text-muted">Ordered</div><div class="font-bold"><?= $totalOrdered ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Received</div><div class="font-bold text-accent"><?= $totalReceived ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Order cost</div><div class="font-bold"><?= money($totalCost) ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Created</div>
    <div class="font-bold text-sm"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
    <div class="text-muted" style="font-size:.72rem"><?= e($order['created_by_name'] ?? '') ?></div>
  </div>
</div>

<?php if (!empty($order['notes'])): ?>
<div class="alert alert-info" style="margin-bottom:1rem"><?= e($order['notes']) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_PATH ?>/purchases/<?= (int)$order['id'] ?>/receive" id="receiveForm">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-list" aria-hidden="true"></i> Lines</h3>
      <?php if ($canReceive): ?>
      <button type="button" class="btn btn-ghost btn-sm" id="fillRemainingBtn"><i class="fa-solid fa-check-double" aria-hidden="true"></i> Fill remaining</button>
      <?php endif; ?>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Stock now</th>
            <th>Ordered</th>
            <th>Received</th>
            <th>Unit cost</th>
            <?php if ($canReceive): ?><th>Receive now</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
          <tr><td colspan="7" class="products-empty">No lines.</td></tr>
          <?php endif; ?>
          <?php foreach ($items as $it):
            $remaining = max(0, (int)$it['quantity_ordered'] - (int)$it['quantity_received']);
          ?>
          <tr>
            <td>
              <strong><?= e($it['name']) ?></strong>
              <div class="text-muted text-sm">Sz <?= e($it['size']) ?><?= $it['design'] ? ' · '.e($it['design']) : '' ?> · <?= e($it['gender']) ?></div>
            </td>
            <td class="mono text-sm"><?= e($it['sku'] ?: '—') ?></td>
            <td><span class="badge badge-ok"><?= (int)$it['stock_qty'] ?></span></td>
            <td><?= (int)$it['quantity_ordered'] ?></td>
            <td><?= (int)$it['quantity_received'] ?></td>
            <td class="text-sm"><?= money($it['unit_cost']) ?></td>
            <?php if ($canReceive): ?>
            <td>
              <?php if ($remaining > 0): ?>
              <input type="number" class="recv-qty" name="recv[<?= (int)$it['id'] ?>]"
                     min="0" max="<?= $remaining ?>" value="0"
                     data-remaining="<?= $remaining ?>"
                     style="width:5rem">
              <span class="text-muted text-sm">/ <?= $remaining ?></span>
              <?php else: ?>
              <span class="text-muted text-sm">Done</span>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($canReceive): ?>
    <div class="card-body" style="border-top:1px solid var(--border)">
      <label style="display:flex;align-items:center;gap:.4rem;margin-bottom:.85rem;font-weight:500;text-transform:none;letter-spacing:0;font-size:.84rem">
        <input type="checkbox" name="update_cost" value="1" style="width:auto">
        Update product cost prices from unit costs on received lines
      </label>
      <button type="submit" class="btn btn-primary" onclick="return confirm('Receive the entered quantities into stock?')">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i> Receive stock
      </button>
    </div>
    <?php endif; ?>
  </div>
</form>

<script>
document.getElementById('fillRemainingBtn')?.addEventListener('click', () => {
  document.querySelectorAll('.recv-qty').forEach(inp => {
    inp.value = inp.dataset.remaining || '0';
  });
});
</script>
<?php
$content = ob_get_clean();
require APP_ROOT.'/Views/layouts/main.php';
