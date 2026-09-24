<?php
$pageTitle = 'Return '.$return['return_ref'];
$cp = '/returns';
ob_start();
?>
<div class="products-toolbar">
  <a href="<?= BASE_PATH ?>/returns" class="btn btn-ghost btn-sm">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All returns
  </a>
  <a href="<?= BASE_PATH ?>/sales/<?= (int)$return['sale_id'] ?>" class="btn btn-ghost btn-sm">
    Original sale <?= e($return['sale_ref']) ?>
  </a>
  <?php if (!empty($return['exchange_sale_id'])): ?>
  <a href="<?= BASE_PATH ?>/sales/<?= (int)$return['exchange_sale_id'] ?>" class="btn btn-ghost btn-sm">
    Exchange sale
  </a>
  <?php endif; ?>
</div>

<div class="card mb-2">
  <div class="card-header">
    <h3><?= e($return['return_ref']) ?></h3>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1rem">
      <div>
        <p class="text-muted text-sm" style="margin:0">Original sale</p>
        <p class="font-bold" style="margin:.15rem 0 0">
          <a href="<?= BASE_PATH ?>/sales/<?= (int)$return['sale_id'] ?>"><?= e($return['sale_ref']) ?></a>
        </p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Refund</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= money($return['refund_amount']) ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Exchange value</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= money($return['exchange_amount']) ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Method</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= e(str_replace('_', ' ', $return['refund_method'])) ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">Processed by</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= e($return['processed_by_name']) ?></p>
      </div>
      <div>
        <p class="text-muted text-sm" style="margin:0">When</p>
        <p class="font-bold" style="margin:.15rem 0 0"><?= date('d M Y H:i', strtotime($return['created_at'])) ?></p>
      </div>
    </div>
    <?php if ($return['reason']): ?>
    <p class="text-sm"><span class="text-muted">Reason:</span> <?= e($return['reason']) ?></p>
    <?php endif; ?>
    <?php if ($return['notes']): ?>
    <p class="text-sm"><span class="text-muted">Notes:</span> <?= e($return['notes']) ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-2">
  <div class="card-header"><h3>Returned items (stock restored)</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Product</th><th>Size</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td class="font-bold"><?= e($it['name']) ?></td>
          <td>Sz <?= e($it['size']) ?></td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= money($it['unit_price']) ?></td>
          <td class="font-bold"><?= money($it['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($exchanges)): ?>
<div class="card">
  <div class="card-header"><h3>Exchange items (stock deducted)</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Product</th><th>Size</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($exchanges as $it): ?>
        <tr>
          <td class="font-bold"><?= e($it['name']) ?></td>
          <td>Sz <?= e($it['size']) ?></td>
          <td><?= (int)$it['quantity'] ?></td>
          <td><?= money($it['unit_price']) ?></td>
          <td class="font-bold"><?= money($it['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
