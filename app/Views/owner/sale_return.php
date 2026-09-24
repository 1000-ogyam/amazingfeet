<?php
$pageTitle = 'Return / Exchange '.$sale['sale_ref'];
$cp = '/sales';
$backUrl = BASE_PATH.'/sales/'.$sale['id'];
$returnedQty = $returnedQty ?? [];
$siblings = $siblings ?? [];
$allProducts = $allProducts ?? [];
ob_start();

$anyReturnable = false;
foreach ($items as $it) {
    $left = (int)$it['quantity'] - (int)($returnedQty[(int)$it['id']] ?? 0);
    if ($left > 0) { $anyReturnable = true; break; }
}
?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="products-toolbar">
  <a href="<?= BASE_PATH ?>/sales/<?= (int)$sale['id'] ?>" class="btn btn-ghost btn-sm">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to sale
  </a>
</div>

<?php if (!$anyReturnable): ?>
<div class="alert alert-error">All items on this sale have already been returned.</div>
<?php else: ?>
<form method="POST" action="<?= BASE_PATH ?>/sales/<?= (int)$sale['id'] ?>/return" id="returnForm"
      onsubmit="return confirm('Process this return/exchange? Stock will be updated.');">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">

  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Items to return</h3>
      <span class="text-muted text-sm"><?= e($sale['sale_ref']) ?></span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>Sold</th>
            <th>Already returned</th>
            <th>Return qty</th>
            <th>Exchange for (optional)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it):
            $siId = (int)$it['id'];
            $pid = (int)$it['product_id'];
            $already = (int)($returnedQty[$siId] ?? 0);
            $left = max(0, (int)$it['quantity'] - $already);
            $sibs = $siblings[$pid] ?? [];
          ?>
          <tr>
            <td>
              <div class="font-bold text-sm"><?= e($it['name']) ?></div>
              <div class="text-muted" style="font-size:.72rem">
                Sz <?= e($it['size']) ?> · <?= money($it['unit_price']) ?>
              </div>
            </td>
            <td><?= (int)$it['quantity'] ?></td>
            <td><?= $already ?></td>
            <td>
              <?php if ($left < 1): ?>
              <span class="text-muted text-sm">—</span>
              <input type="hidden" name="qty[<?= $siId ?>]" value="0">
              <?php else: ?>
              <input type="number" name="qty[<?= $siId ?>]" class="return-qty" data-price="<?= (float)$it['unit_price'] ?>"
                     min="0" max="<?= $left ?>" value="0" style="width:4.5rem">
              <span class="text-muted text-sm">/ <?= $left ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($left > 0 && count($sibs) > 1): ?>
              <select name="exchange_product[<?= $siId ?>]" style="max-width:220px">
                <option value="">— Same / no exchange —</option>
                <?php foreach ($sibs as $s):
                  if ((int)$s['id'] === $pid) continue;
                ?>
                <option value="<?= (int)$s['id'] ?>" data-price="<?= (float)$s['selling_price'] ?>">
                  Sz <?= e($s['size']) ?> · stock <?= (int)$s['quantity'] ?> · <?= money($s['selling_price']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php else: ?>
              <span class="text-muted text-sm">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa-solid fa-right-left" aria-hidden="true"></i> Extra exchange items</h3>
      <span class="text-muted text-sm">Different style / product</span>
    </div>
    <div class="card-body">
      <div id="extraExchangeRows"></div>
      <button type="button" class="btn btn-ghost btn-sm" id="addExchangeRow">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Add product
      </button>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-header"><h3>Refund &amp; notes</h3></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label>Reason</label>
          <input type="text" name="reason" placeholder="Wrong size, damaged, customer changed mind…">
        </div>
        <div class="form-group">
          <label>Refund method</label>
          <select name="refund_method">
            <option value="cash">Cash</option>
            <option value="momo">MoMo</option>
            <option value="card">Card</option>
            <option value="store_credit">Store credit</option>
            <option value="none">None (even exchange)</option>
          </select>
        </div>
        <div class="form-group">
          <label>If customer pays difference</label>
          <select name="exchange_payment">
            <option value="cash">Cash</option>
            <option value="momo">MoMo</option>
            <option value="card">Card</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <input type="text" name="notes" placeholder="Optional">
      </div>
      <p class="text-muted text-sm" id="returnSummary">Enter return quantities to see refund estimate.</p>
      <div class="flex-center gap-2" style="margin-top:1rem;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-check" aria-hidden="true"></i> Process return
        </button>
        <a href="<?= BASE_PATH ?>/sales/<?= (int)$sale['id'] ?>" class="btn btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  var products = <?= json_encode(array_map(static function ($p) {
      return [
          'id' => (int)$p['id'],
          'label' => $p['name'].' · Sz '.$p['size']
              .(!empty($p['design']) ? ' · '.$p['design'] : '')
              .' · '.number_format((float)$p['selling_price'], 2)
              .' (stock '.(int)$p['quantity'].')',
          'price' => (float)$p['selling_price'],
      ];
  }, $allProducts), JSON_UNESCAPED_UNICODE) ?>;

  var extraIdx = 0;
  var extraBox = document.getElementById('extraExchangeRows');
  document.getElementById('addExchangeRow').addEventListener('click', function () {
    var i = extraIdx++;
    var wrap = document.createElement('div');
    wrap.className = 'form-row';
    wrap.style.marginBottom = '.5rem';
    var opts = products.map(function (p) {
      return '<option value="'+p.id+'" data-price="'+p.price+'">'+p.label.replace(/</g,'&lt;')+'</option>';
    }).join('');
    wrap.innerHTML =
      '<div class="form-group" style="flex:2"><select name="extra_exchange['+i+'][product_id]"><option value="">— Product —</option>'+opts+'</select></div>' +
      '<div class="form-group" style="flex:0 0 6rem"><input type="number" name="extra_exchange['+i+'][quantity]" min="1" value="1" placeholder="Qty"></div>' +
      '<button type="button" class="btn btn-ghost btn-sm" style="align-self:end;margin-bottom:.65rem" onclick="this.parentNode.remove();updateSummary()">&times;</button>';
    extraBox.appendChild(wrap);
    wrap.querySelectorAll('select,input').forEach(function (el) {
      el.addEventListener('input', updateSummary);
      el.addEventListener('change', updateSummary);
    });
    updateSummary();
  });

  function updateSummary() {
    var ret = 0;
    document.querySelectorAll('.return-qty').forEach(function (inp) {
      var q = parseInt(inp.value, 10) || 0;
      var price = parseFloat(inp.getAttribute('data-price')) || 0;
      ret += q * price;
    });
    var ex = 0;
    extraBox.querySelectorAll('select').forEach(function (sel) {
      var opt = sel.options[sel.selectedIndex];
      if (!opt || !sel.value) return;
      var price = parseFloat(opt.getAttribute('data-price')) || 0;
      var qtyInp = sel.closest('.form-row').querySelector('input[type=number]');
      var q = qtyInp ? (parseInt(qtyInp.value, 10) || 0) : 0;
      ex += q * price;
    });
    // Sibling exchanges use same qty as return line — approximate with return lines that have exchange selected
    document.querySelectorAll('select[name^="exchange_product"]').forEach(function (sel) {
      if (!sel.value) return;
      var tr = sel.closest('tr');
      var qtyInp = tr ? tr.querySelector('.return-qty') : null;
      var q = qtyInp ? (parseInt(qtyInp.value, 10) || 0) : 0;
      var opt = sel.options[sel.selectedIndex];
      var price = opt ? (parseFloat(opt.getAttribute('data-price')) || 0) : 0;
      ex += q * price;
    });
    var refund = Math.max(0, ret - ex);
    var due = Math.max(0, ex - ret);
    var el = document.getElementById('returnSummary');
    el.textContent = 'Return value ≈ GHS ' + ret.toFixed(2)
      + ' · Exchange ≈ GHS ' + ex.toFixed(2)
      + (refund > 0 ? ' · Refund ≈ GHS ' + refund.toFixed(2) : '')
      + (due > 0 ? ' · Customer pays ≈ GHS ' + due.toFixed(2) : '');
  }

  document.querySelectorAll('.return-qty, select[name^="exchange_product"]').forEach(function (el) {
    el.addEventListener('input', updateSummary);
    el.addEventListener('change', updateSummary);
  });
  updateSummary();
})();
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
