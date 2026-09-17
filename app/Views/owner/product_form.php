<?php
$isEdit = $product !== null;
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
$cp = '/products';
$backUrl = BASE_PATH.'/products';
$variants = $variants ?? [];
ob_start();
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="product-form-layout<?= $isEdit ? ' product-form-layout--edit' : '' ?>">
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
            <label>SKU code</label>
            <input type="text" name="sku" value="<?= e($product['sku']??'') ?>" placeholder="e.g. AF-SCH-BLK-30" class="mono">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Barcode (optional)</label>
            <input type="text" name="barcode" value="<?= e($product['barcode']??'') ?>" placeholder="Scan / EAN barcode" class="mono">
          </div>
          <div class="form-group"></div>
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
          <label>Sizes, SKU codes &amp; prices *</label>
          <p class="text-muted text-sm" style="margin:-.25rem 0 .65rem">Each size can have its own <strong>SKU</strong>, cost, selling price, stock, and barcode. POS groups them and shows the SKU when you pick a size.</p>
          <div class="table-wrap" style="border:1px solid var(--border);border-radius:8px;overflow-x:auto">
            <table id="sizeTable" style="min-width:640px">
              <thead>
                <tr>
                  <th>Size *</th>
                  <th>SKU code</th>
                  <th>Cost (GHS)</th>
                  <th>Sell (GHS) *</th>
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
            <button type="button" class="btn btn-ghost btn-sm" onclick="applyDefaultPrices()"><i class="fa-solid fa-copy" aria-hidden="true"></i> Apply prices to empty</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="applyDefaultSku(true)"><i class="fa-solid fa-tags" aria-hidden="true"></i> Apply SKU to all sizes</button>
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
          <div class="form-row">
            <div class="form-group">
              <label>Default SKU (optional helper)</label>
              <input type="text" id="defaultSku" class="mono" placeholder="e.g. T266370">
              <p class="text-muted text-sm" style="margin:.35rem 0 0">Applies the <strong>same SKU</strong> to every size row — just like applying one price across sizes.</p>
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:.15rem">
              <label style="display:flex;align-items:center;gap:.4rem;font-weight:500;text-transform:none;letter-spacing:0;font-size:.85rem">
                <input type="checkbox" id="skuAppendSize" style="width:auto"> Append size to SKU (e.g. T266370-30)
              </label>
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
        </div>

        <div class="form-group img-upload">
          <label>Product Image</label>
          <p class="text-muted text-sm img-upload-hint">Shows on the POS terminal. Take a photo or choose from your gallery (JPG, PNG, WebP).</p>
          <div class="img-upload-box">
            <div class="img-upload-preview" id="imgPreview">
              <?php if ($isEdit && !empty($product['image'])): ?>
              <img src="<?= UPLOAD_URL.e($product['image']) ?>" alt="Product" id="imgPreviewImg">
              <?php else: ?>
              <div class="img-upload-placeholder" id="imgPlaceholder">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                <span>No image yet</span>
              </div>
              <?php endif; ?>
            </div>
            <div class="img-upload-actions">
              <label class="btn btn-ghost btn-sm img-upload-btn">
                <i class="fa-solid fa-images" aria-hidden="true"></i> Gallery
                <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp,image/*" class="img-upload-input">
              </label>
              <label class="btn btn-ghost btn-sm img-upload-btn">
                <i class="fa-solid fa-camera" aria-hidden="true"></i> Camera
                <input type="file" id="imageCamera" accept="image/*" capture="environment" class="img-upload-input">
              </label>
              <button type="button" class="btn btn-ghost btn-sm" id="imgClearBtn" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</button>
            </div>
            <p class="text-muted text-sm" id="imgStatus" hidden></p>
          </div>
        </div>

        <div class="flex gap-1 mt-2 product-form-actions">
          <button type="submit" class="btn btn-primary"><?= $isEdit ? '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes' : '<i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product' ?></button>
          <a href="<?= BASE_PATH ?>/products" class="btn btn-ghost"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <?php if ($isEdit): ?>
  <div class="product-form-side">
    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-box" aria-hidden="true"></i> Stock Adjustment</h3></div>
      <div class="card-body">
        <div class="stock-adj-current">
          <div class="text-muted text-sm">Current Stock (Sz <?= e($product['size']) ?>)</div>
          <div class="stock-adj-qty" style="color:<?= $product['quantity']<=$product['low_stock_threshold']?'var(--danger)':'var(--accent2)' ?>"><?= $product['quantity'] ?></div>
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
            <thead><tr><th>Size</th><th>SKU</th><th>Sell</th><th>Stock</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($variants as $v): ?>
              <tr style="<?= (int)$v['id']===(int)$product['id']?'background:rgba(200,83,42,.06)':'' ?>">
                <td><strong>Sz <?= e($v['size']) ?></strong></td>
                <td class="mono text-sm"><?= e($v['sku'] ?? '—') ?></td>
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

        <form method="POST" action="<?= BASE_PATH ?>/products/<?= (int)$product['id'] ?>/sizes" class="add-size-form">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <h4 class="add-size-title"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add another size</h4>
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
          <div class="form-row">
            <div class="form-group">
              <label>SKU</label>
              <input type="text" name="sku" class="mono" placeholder="e.g. AF-SCH-BLK-35">
            </div>
            <div class="form-group">
              <label>Barcode</label>
              <input type="text" name="barcode" class="mono" placeholder="Optional">
            </div>
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
    <td><input type="text" name="sizes[${i}][sku]" class="mono" value="${prefill.sku ?? ''}" placeholder="e.g. AF-30" style="min-width:8rem"></td>
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
  const defSku = (document.getElementById('defaultSku').value || '').trim();
  const appendSize = document.getElementById('skuAppendSize').checked;
  for (let s = from; s <= to; s++) {
    const size = String(s);
    const sku = !defSku ? '' : (appendSize ? buildSku(defSku, size) : defSku);
    addSizeRow({ size, cost: defC, sell: defS, qty: 0, sku });
  }
}
function buildSku(base, size) {
  const b = String(base || '').trim().replace(/[-\s]+$/, '');
  const sz = String(size || '').trim();
  if (!b) return '';
  if (!sz) return b;
  if (b.toLowerCase().endsWith('-' + sz.toLowerCase()) || b.toLowerCase().endsWith(sz.toLowerCase())) return b;
  return b + '-' + sz;
}
function applyDefaultSku(overwriteAll) {
  let base = (document.getElementById('defaultSku').value || '').trim();
  const appendSize = document.getElementById('skuAppendSize').checked;
  const skuInputs = [...document.querySelectorAll('#sizeRows [name*="[sku]"]')];
  if (!base) {
    const first = skuInputs.find(i => i.value.trim() !== '');
    base = first ? first.value.trim() : '';
  }
  if (!base) {
    alert('Enter a default SKU (or fill the first size SKU), then apply again.');
    return;
  }
  document.getElementById('defaultSku').value = base;
  document.querySelectorAll('#sizeRows tr').forEach(tr => {
    const size = tr.querySelector('[name*="[size]"]')?.value.trim() || '';
    const sku = tr.querySelector('[name*="[sku]"]');
    if (!sku) return;
    if (!overwriteAll && sku.value.trim()) return;
    sku.value = appendSize ? buildSku(base, size) : base;
  });
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
  // Same convenience for SKU empties
  applyDefaultSku(false);
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

(function initProductImageUpload() {
  const main = document.getElementById('imageInput');
  const camera = document.getElementById('imageCamera');
  const clearBtn = document.getElementById('imgClearBtn');
  const status = document.getElementById('imgStatus');
  const preview = document.getElementById('imgPreview');
  if (!main || !preview) return;

  const MAX_EDGE = 1400;
  const JPEG_Q = 0.82;

  function setStatus(msg, show) {
    if (!status) return;
    status.hidden = !show;
    status.textContent = msg || '';
  }

  function showPreview(url) {
    let img = document.getElementById('imgPreviewImg');
    const ph = document.getElementById('imgPlaceholder');
    if (ph) ph.remove();
    if (!img) {
      img = document.createElement('img');
      img.id = 'imgPreviewImg';
      img.alt = 'Product preview';
      preview.appendChild(img);
    }
    img.src = url;
    if (clearBtn) clearBtn.hidden = false;
  }

  function assignFile(file) {
    try {
      const dt = new DataTransfer();
      dt.items.add(file);
      main.files = dt.files;
    } catch (_) { /* older browsers keep original input */ }
  }

  function compressImage(file) {
    return new Promise((resolve, reject) => {
      if (!file || !file.type || !file.type.startsWith('image/')) {
        reject(new Error('Please choose a JPG, PNG, or WebP image.'));
        return;
      }
      // Skip compression for small files / webp already small
      if (file.size < 900 * 1024 && file.type !== 'image/heic' && file.type !== 'image/heif') {
        resolve(file);
        return;
      }
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => {
        URL.revokeObjectURL(url);
        let { width, height } = img;
        const scale = Math.min(1, MAX_EDGE / Math.max(width, height));
        width = Math.round(width * scale);
        height = Math.round(height * scale);
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        canvas.toBlob((blob) => {
          if (!blob) { resolve(file); return; }
          const name = (file.name || 'product').replace(/\.\w+$/, '') + '.jpg';
          resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', JPEG_Q);
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('Could not read that image. Try JPG or PNG.'));
      };
      img.src = url;
    });
  }

  async function onPick(file) {
    if (!file) return;
    setStatus('Preparing image…', true);
    try {
      const out = await compressImage(file);
      assignFile(out);
      showPreview(URL.createObjectURL(out));
      const kb = Math.round(out.size / 1024);
      setStatus('Ready to upload · ' + kb + ' KB (shows on POS after save)', true);
    } catch (err) {
      setStatus(err.message || 'Image failed', true);
      main.value = '';
    }
  }

  main.addEventListener('change', () => onPick(main.files && main.files[0]));
  if (camera) {
    camera.addEventListener('change', () => {
      const f = camera.files && camera.files[0];
      onPick(f);
      camera.value = '';
    });
  }
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      main.value = '';
      const img = document.getElementById('imgPreviewImg');
      if (img) img.remove();
      if (!document.getElementById('imgPlaceholder')) {
        preview.innerHTML = '<div class="img-upload-placeholder" id="imgPlaceholder"><i class="fa-solid fa-camera" aria-hidden="true"></i><span>No image yet</span></div>';
      }
      clearBtn.hidden = true;
      setStatus('', false);
    });
  }
})();
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
