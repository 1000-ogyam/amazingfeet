<?php
$isEdit = $product !== null;
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
$cp = '/products';
$backUrl = BASE_PATH.'/products';
$variants = $variants ?? [];
ob_start();
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header"><h3><?= $isEdit ? '<i class="fa-solid fa-pen" aria-hidden="true"></i> Edit' : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Add' ?> Product</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/products/<?= $isEdit?$product['id'].'/edit':'create' ?>" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <div class="form-row">
          <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" required value="<?= e($product['name']??'') ?>" placeholder="e.g. Amazing Feet School Shoe">
          </div>
          <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
              <option value="">— Select —</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($product['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Gender *</label>
            <select name="gender" required>
              <?php foreach(['Boys','Girls','Unisex','Ladies'] as $g): ?>
              <option value="<?= $g ?>" <?= ($product['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Design / Style</label>
            <input type="text" name="design" value="<?= e($product['design']??'') ?>" placeholder="e.g. Classic Black, Patent Finish">
          </div>
        </div>

        <?php if ($isEdit): ?>
        <div class="form-row">
          <div class="form-group">
            <label>Size *</label>
            <input type="text" name="size" required value="<?= e($product['size']??'') ?>" placeholder="e.g. 30">
          </div>
          <div class="form-group">
            <label>Barcode (optional)</label>
            <input type="text" name="barcode" value="<?= e($product['barcode']??'') ?>" placeholder="e.g. AF-B-CB-30" class="mono">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Cost Price (GHS) *</label>
            <input type="number" name="cost_price" required step="0.01" min="0" value="<?= e($product['cost_price']??'') ?>" placeholder="0.00">
          </div>
          <div class="form-group">
            <label>Selling Price (GHS) *</label>
            <input type="number" name="selling_price" required step="0.01" min="0" value="<?= e($product['selling_price']??'') ?>" placeholder="0.00" oninput="calcMargin()">
          </div>
        </div>

        <div id="marginDisplay" style="background:var(--bg3);border-radius:7px;padding:.65rem .9rem;margin-bottom:1rem;font-size:.85rem;display:none">
          Profit Margin: <strong id="marginVal" style="color:var(--accent2)">—</strong>
          &nbsp;|&nbsp; Profit per unit: <strong id="profitVal">—</strong>
        </div>
        <?php else: ?>
        <div class="form-group">
          <label>Sizes &amp; prices *</label>
          <p class="text-muted text-sm" style="margin:-.25rem 0 .65rem">Same product, different sizes can each have their own cost, selling price, and stock. POS groups them and lets staff pick a size.</p>
          <div class="table-wrap" style="border:1px solid var(--border);border-radius:8px">
            <table id="sizeTable">
              <thead>
                <tr>
                  <th>Size</th>
                  <th>Cost (GHS)</th>
                  <th>Sell (GHS)</th>
                  <th>Qty</th>
                  <th>Barcode</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="sizeRows"></tbody>
            </table>
          </div>
          <div class="flex-center gap-1" style="margin-top:.65rem;flex-wrap:wrap">
            <button type="button" class="btn btn-ghost btn-sm" onclick="addSizeRow()"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add size row</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="fillSizeRange(28,40)"><i class="fa-solid fa-list-ol" aria-hidden="true"></i> Prefill 28–40</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="applyDefaultPrices()"><i class="fa-solid fa-copy" aria-hidden="true"></i> Copy first price to empty</button>
          </div>
          <div class="form-row" style="margin-top:.85rem">
            <div class="form-group">
              <label>Default cost (optional helper)</label>
              <input type="number" id="defaultCost" step="0.01" min="0" placeholder="Fill into empty cost cells">
            </div>
            <div class="form-group">
              <label>Default sell price (optional helper)</label>
              <input type="number" id="defaultSell" step="0.01" min="0" placeholder="Fill into empty sell cells">
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="form-row">
          <?php if ($isEdit): ?>
          <div class="form-group">
            <label>Low Stock Alert Threshold</label>
            <input type="number" name="low_stock_threshold" min="1" value="<?= e($product['low_stock_threshold']??LOW_STOCK_THRESHOLD) ?>">
          </div>
          <?php else: ?>
          <div class="form-group">
            <label>Low Stock Alert Threshold</label>
            <input type="number" name="low_stock_threshold" min="1" value="<?= e(LOW_STOCK_THRESHOLD) ?>">
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label>Product Image (optional)</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" style="padding:.4rem">
            <?php if ($isEdit && !empty($product['image'])): ?>
            <img src="<?= UPLOAD_URL.e($product['image']) ?>" style="width:80px;border-radius:7px;margin-top:.5rem;border:1px solid var(--border)" alt="">
            <?php endif; ?>
          </div>
        </div>

        <div class="flex gap-1 mt-2">
          <button type="submit" class="btn btn-primary"><?= $isEdit ? '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes' : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product' ?></button>
          <a href="<?= BASE_PATH ?>/products" class="btn btn-ghost"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php if ($isEdit): ?>
  <div style="display:flex;flex-direction:column;gap:1rem">
    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-box" aria-hidden="true"></i> Stock Adjustment</h3></div>
      <div class="card-body">
        <div style="margin-bottom:1rem;text-align:center">
          <div class="text-muted text-sm">Current Stock (Sz <?= e($product['size']) ?>)</div>
          <div style="font-size:2.5rem;font-weight:700;font-family:var(--font);color:<?= $product['quantity']<=$product['low_stock_threshold']?'var(--danger)':'var(--accent2)' ?>"><?= $product['quantity'] ?></div>
          <div class="text-muted text-sm">units (threshold: <?= $product['low_stock_threshold'] ?>)</div>
        </div>
        <form method="POST" action="<?= BASE_PATH ?>/products/<?= $product['id'] ?>/stock">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <div class="form-group">
            <label>Adjustment Type</label>
            <select name="type">
              <option value="addition">+ Addition (restock)</option>
              <option value="return">+ Return from customer</option>
              <option value="correction">Correction</option>
              <option value="damaged">− Damaged / lost</option>
            </select>
          </div>
          <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" required min="1" placeholder="Units to add/remove">
          </div>
          <div class="form-group">
            <label>Note</label>
            <input type="text" name="note" placeholder="Reason for adjustment">
          </div>
          <button type="submit" class="btn btn-success w-full"><i class="fa-solid fa-check" aria-hidden="true"></i> Apply Adjustment</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i> All sizes</h3></div>
      <div class="card-body" style="padding-top:.35rem">
        <p class="text-muted text-sm">Each size keeps its own price and stock. Editing name/category/design updates the whole style.</p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Size</th><th>Sell</th><th>Stock</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($variants as $v): ?>
              <tr style="<?= (int)$v['id']===(int)$product['id']?'background:rgba(200,83,42,.06)':'' ?>">
                <td><strong>Sz <?= e($v['size']) ?></strong></td>
                <td class="text-sm"><?= money($v['selling_price']) ?></td>
                <td><span class="badge <?= $v['quantity']<=$v['low_stock_threshold']?'badge-low':'badge-ok' ?>"><?= (int)$v['quantity'] ?></span></td>
                <td>
                  <?php if ((int)$v['id']!==(int)$product['id']): ?>
                  <a href="<?= BASE_PATH ?>/products/<?= (int)$v['id'] ?>/edit" class="btn btn-ghost btn-xs">Edit</a>
                  <?php else: ?>
                  <span class="text-muted text-sm">Editing</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <form method="POST" action="<?= BASE_PATH ?>/products/<?= (int)$product['id'] ?>/sizes" style="margin-top:1rem;border-top:1px solid var(--border);padding-top:1rem">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <h4 style="font-size:.9rem;margin-bottom:.65rem"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add another size</h4>
          <div class="form-row">
            <div class="form-group">
              <label>Size *</label>
              <input type="text" name="size" required placeholder="e.g. 35">
            </div>
            <div class="form-group">
              <label>Sell (GHS) *</label>
              <input type="number" name="selling_price" required step="0.01" min="0" value="<?= e($product['selling_price']) ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Cost (GHS)</label>
              <input type="number" name="cost_price" step="0.01" min="0" value="<?= e($product['cost_price']) ?>">
            </div>
            <div class="form-group">
              <label>Qty</label>
              <input type="number" name="quantity" min="0" value="0">
            </div>
          </div>
          <div class="form-group">
            <label>Barcode</label>
            <input type="text" name="barcode" class="mono" placeholder="Optional">
          </div>
          <button type="submit" class="btn btn-primary btn-sm w-full"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add size</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
<?php if ($isEdit): ?>
function calcMargin() {
  const cost    = parseFloat(document.querySelector('[name=cost_price]').value)||0;
  const selling = parseFloat(document.querySelector('[name=selling_price]').value)||0;
  const disp    = document.getElementById('marginDisplay');
  if (selling>0 && cost>0) {
    const margin = ((selling-cost)/selling*100).toFixed(1);
    const profit = (selling-cost).toFixed(2);
    document.getElementById('marginVal').textContent  = margin+'%';
    document.getElementById('profitVal').textContent  = 'GHS '+profit;
    document.getElementById('marginVal').style.color  = margin>=40?'var(--accent2)':margin>=20?'var(--warning)':'var(--danger)';
    disp.style.display = 'block';
  } else disp.style.display='none';
}
document.querySelector('[name=cost_price]').addEventListener('input', calcMargin);
calcMargin();
<?php else: ?>
let sizeRowIndex = 0;
function addSizeRow(prefill = {}) {
  const i = sizeRowIndex++;
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="sizes[${i}][size]" required placeholder="30" value="${prefill.size ?? ''}" style="width:4.2rem"></td>
    <td><input type="number" name="sizes[${i}][cost_price]" step="0.01" min="0" value="${prefill.cost ?? ''}" placeholder="0.00" style="width:6rem"></td>
    <td><input type="number" name="sizes[${i}][selling_price]" step="0.01" min="0" required value="${prefill.sell ?? ''}" placeholder="0.00" style="width:6rem"></td>
    <td><input type="number" name="sizes[${i}][quantity]" min="0" value="${prefill.qty ?? 0}" style="width:4.5rem"></td>
    <td><input type="text" name="sizes[${i}][barcode]" class="mono" value="${prefill.barcode ?? ''}" placeholder="—" style="min-width:7rem"></td>
    <td><button type="button" class="btn btn-ghost btn-xs" onclick="this.closest('tr').remove()" title="Remove"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></td>`;
  document.getElementById('sizeRows').appendChild(tr);
}
function fillSizeRange(from, to) {
  const body = document.getElementById('sizeRows');
  if (body.children.length && !confirm('Replace current size rows with '+from+'–'+to+'?')) return;
  body.innerHTML = '';
  sizeRowIndex = 0;
  const defC = document.getElementById('defaultCost').value;
  const defS = document.getElementById('defaultSell').value;
  for (let s = from; s <= to; s++) addSizeRow({ size: String(s), cost: defC, sell: defS, qty: 0 });
}
function applyDefaultPrices() {
  const defC = document.getElementById('defaultCost').value;
  const defS = document.getElementById('defaultSell').value;
  document.querySelectorAll('#sizeRows tr').forEach(tr => {
    const cost = tr.querySelector('[name*="[cost_price]"]');
    const sell = tr.querySelector('[name*="[selling_price]"]');
    if (defC !== '' && cost && !cost.value) cost.value = defC;
    if (defS !== '' && sell && !sell.value) sell.value = defS;
  });
  // Also copy first filled sell/cost across empties if defaults blank
  const costs = [...document.querySelectorAll('#sizeRows [name*="[cost_price]"]')];
  const sells = [...document.querySelectorAll('#sizeRows [name*="[selling_price]"]')];
  const firstCost = costs.find(i => i.value !== '')?.value;
  const firstSell = sells.find(i => i.value !== '')?.value;
  costs.forEach(i => { if (!i.value && firstCost) i.value = firstCost; });
  sells.forEach(i => { if (!i.value && firstSell) i.value = firstSell; });
}
document.getElementById('productForm').addEventListener('submit', e => {
  const rows = [...document.querySelectorAll('#sizeRows tr')];
  const ok = rows.some(tr => {
    const size = tr.querySelector('[name*="[size]"]')?.value.trim();
    const sell = parseFloat(tr.querySelector('[name*="[selling_price]"]')?.value || '');
    return size && !Number.isNaN(sell) && sell >= 0;
  });
  if (!ok) {
    e.preventDefault();
    alert('Add at least one size with a selling price.');
  }
});
addSizeRow();
addSizeRow();
addSizeRow();
<?php endif; ?>
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
