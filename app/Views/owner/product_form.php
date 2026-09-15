<?php
$isEdit = $product !== null;
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
$cp = '/products';
$backUrl = BASE_PATH.'/products';
ob_start();
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header"><h3><?= $isEdit ? '<i class="fa-solid fa-pen" aria-hidden="true"></i> Edit' : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Add' ?> Product</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/products/<?= $isEdit?$product['id'].'/edit':'create' ?>" enctype="multipart/form-data">
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

        <div class="form-row">
          <div class="form-group">
            <label>Size *</label>
            <input type="text" name="size" required value="<?= e($product['size']??'') ?>" placeholder="e.g. 30, 31, 32…">
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

        <div class="form-row">
          <div class="form-group">
            <label>Quantity in Stock *</label>
            <input type="number" name="quantity" required min="0" value="<?= e($product['quantity']??0) ?>">
          </div>
          <div class="form-group">
            <label>Low Stock Alert Threshold</label>
            <input type="number" name="low_stock_threshold" min="1" value="<?= e($product['low_stock_threshold']??LOW_STOCK_THRESHOLD) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Product Image (optional)</label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" style="padding:.4rem">
          <?php if ($isEdit && $product['image']): ?>
          <img src="<?= UPLOAD_URL.e($product['image']) ?>" style="width:80px;border-radius:7px;margin-top:.5rem;border:1px solid var(--border)">
          <?php endif; ?>
        </div>

        <div class="flex gap-1 mt-2">
          <button type="submit" class="btn btn-primary"><?= $isEdit ? '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes' : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product' ?></button>
          <a href="<?= BASE_PATH ?>/products" class="btn btn-ghost"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php if ($isEdit): ?>
  <!-- Stock Adjustment -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-box" aria-hidden="true"></i> Stock Adjustment</h3></div>
    <div class="card-body">
      <div style="margin-bottom:1rem;text-align:center">
        <div class="text-muted text-sm">Current Stock</div>
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
  <?php endif; ?>
</div>

<script>
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
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
