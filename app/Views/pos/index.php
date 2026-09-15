<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>POS Terminal — Amazing Feet</title>
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app-compact.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/pos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="icon" href="<?= e(af_favicon_data_uri()) ?>">
</head>
<body class="pos-body">

<div class="pos-topbar">
  <span class="pos-brand">
    <i class="fa-solid fa-shoe-prints" aria-hidden="true"></i>
    <span class="pos-brand-text">Amazing Feet POS</span>
  </span>
  <span class="pos-user"><?= e($_SESSION['user_name']??'') ?></span>
  <span class="pos-clock" id="clock"></span>
  <?php if (isOwner()): ?>
  <a href="<?= BASE_PATH ?>/dashboard" class="btn btn-ghost btn-sm pos-topbar-btn"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> <span>Dashboard</span></a>
  <a href="<?= BASE_PATH ?>/assessments" class="btn btn-ghost btn-sm pos-topbar-btn"><i class="fa-solid fa-clipboard-user" aria-hidden="true"></i> <span>Assessments</span></a>
  <?php else: ?>
  <a href="<?= BASE_PATH ?>/assessment" class="btn btn-ghost btn-sm pos-topbar-btn"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> <span>Daily Report</span></a>
  <?php endif; ?>
  <a href="<?= BASE_PATH ?>/help" class="btn btn-ghost btn-sm pos-topbar-btn"><i class="fa-solid fa-book-open" aria-hidden="true"></i> <span>Guide</span></a>
  <a href="<?= BASE_PATH ?>/logout" class="btn btn-ghost btn-sm pos-topbar-btn"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> <span>Logout</span></a>
</div>

<div class="pos-shell">
  <div class="pos-left">
    <div class="pos-toolbar">
      <div class="search-bar">
        <span class="search-bar-ic" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" id="searchInput" placeholder="Search name, size, SKU, barcode…" autocomplete="off" oninput="scheduleProductSearch(this.value)">
        <button type="button" class="barcode-btn" id="barcodeBtn" onclick="toggleBarcodeMode()" title="Scan SKU or barcode"><i class="fa-solid fa-barcode" aria-hidden="true"></i></button>
      </div>
      <div class="pos-view-toggle" role="group" aria-label="Product view">
        <button type="button" class="pos-view-btn active" id="viewGridBtn" onclick="setViewMode('grid')" title="Grid view"><i class="fa-solid fa-grip" aria-hidden="true"></i></button>
        <button type="button" class="pos-view-btn" id="viewListBtn" onclick="setViewMode('list')" title="List view"><i class="fa-solid fa-list" aria-hidden="true"></i></button>
      </div>
    </div>

    <div class="pos-filters">
      <button type="button" class="btn btn-sm btn-ghost cat-filter active" data-cat="" onclick="filterCat(this,'')">All</button>
      <?php foreach ($categories as $c): ?>
      <button type="button" class="btn btn-sm btn-ghost cat-filter" data-cat="<?= $c['id'] ?>" onclick="filterCat(this,'<?= $c['id'] ?>')"><?= e($c['name']) ?></button>
      <?php endforeach; ?>
      <span class="pos-filter-sep" aria-hidden="true"></span>
      <?php foreach (['Boys','Girls','Unisex','Ladies'] as $g): ?>
      <button type="button" class="btn btn-sm btn-ghost gender-filter" data-gender="<?= $g ?>" onclick="filterGender(this,'<?= $g ?>')"><?= $g ?></button>
      <?php endforeach; ?>
    </div>

    <div id="barcodeWrap" class="pos-barcode-wrap" hidden>
      <input type="text" id="barcodeInput" placeholder="Scan or type SKU / barcode…" autocomplete="off">
    </div>

    <div class="product-grid" id="productGrid" data-view="grid">
      <div class="pos-empty">Loading products…</div>
    </div>
    <div id="posPagination" class="pos-pagination" aria-label="Product pages"></div>
  </div>

  <div class="pos-right">
    <div class="cart-header">
      <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Cart
      <span id="cartCount" class="cart-count-lbl">(0 items)</span>
    </div>
    <div id="cartItems" class="cart-items">
      <div class="cart-empty">
        <span class="cart-empty-ic"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></span>
        <span>Cart is empty</span>
        <span class="cart-empty-hint">Add products from the left</span>
      </div>
    </div>
    <div class="cart-footer">
      <div class="cart-totals">
        <div class="totals-row"><span>Subtotal</span><span id="totSubtotal">GHS 0.00</span></div>
        <div class="totals-row">
          <span>Discount (GHS)</span>
          <input type="number" id="discountInput" value="0" min="0" step="0.01" oninput="updateTotals()">
        </div>
        <div class="totals-row grand"><span>TOTAL</span><span id="totTotal">GHS 0.00</span></div>
      </div>
      <button type="button" class="btn btn-primary pay-btn" id="checkoutBtn" onclick="openCheckout()" disabled>
        Proceed to Payment <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
      </button>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay hidden" id="payModal" role="presentation">
  <div class="modal-box pay-modal" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
    <h3 class="modal-title-pay" id="payModalTitle"><i class="fa-solid fa-wallet" aria-hidden="true"></i> Complete Payment</h3>
    <form method="POST" action="<?= BASE_PATH ?>/pos/sale" id="saleForm" class="pay-modal-form">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="cart_json" id="cartJson">
      <input type="hidden" name="ajax" value="1">
      <div class="pay-modal-body">
        <div class="form-row pay-modal-row">
          <div class="form-group">
            <label>Sale Location *</label>
            <select name="location_id" required>
              <?php foreach ($locations as $l): ?>
              <option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Payment Method *</label>
            <select name="payment_method" id="payMethod" onchange="onPayMethodChange(this.value)" required>
              <option value="cash">Cash</option>
              <option value="momo">Mobile Money</option>
              <option value="card">Card</option>
            </select>
          </div>
        </div>
        <div id="cashFields">
          <div class="form-row pay-modal-row">
            <div class="form-group">
              <label>Amount Tendered (GHS)</label>
              <input type="number" name="amount_tendered" id="tenderedInput" min="0" step="0.01" placeholder="0.00" oninput="calcChange()">
            </div>
            <div class="form-group">
              <label>Change Due</label>
              <input type="text" id="changeDisplay" class="pay-change-display" readonly value="GHS 0.00">
            </div>
          </div>
        </div>
        <div id="momoFields" class="pay-momo-fields" style="display:none">
          <div class="form-group">
            <label>MoMo Reference / Transaction ID</label>
            <input type="text" name="momo_ref" placeholder="e.g. GHY123456789">
          </div>
        </div>
        <div class="form-group">
          <label>Discount (GHS)</label>
          <input type="number" name="discount" id="discountModal" value="0" min="0" step="0.01" oninput="syncDiscount()">
        </div>
        <div class="pay-modal-summary">
          <div class="pay-modal-summary-row"><span>Subtotal</span><span id="modalSubtotal">GHS 0.00</span></div>
          <div class="pay-modal-summary-row"><span>Discount</span><span id="modalDiscount">GHS 0.00</span></div>
          <div class="pay-modal-summary-row pay-modal-summary-total"><span>TOTAL</span><span id="modalTotal">GHS 0.00</span></div>
        </div>
        <details class="pay-modal-details">
          <summary>Add customer (optional)</summary>
          <div class="form-row pay-modal-row">
            <div class="form-group">
              <label>Customer Name</label>
              <input type="text" name="customer_name" placeholder="e.g. Abena Mensah">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="tel" name="customer_phone" placeholder="+233…">
            </div>
          </div>
          <div class="form-group">
            <label>Shoe Size (for records)</label>
            <input type="text" name="customer_size" placeholder="e.g. 32">
          </div>
        </details>
        <div class="form-group pay-modal-notes">
          <label>Notes</label>
          <input type="text" name="notes" placeholder="Optional note for this sale">
        </div>
        <p id="saleError" class="pos-sale-error" hidden></p>
      </div>
      <div class="pay-modal-actions">
        <button type="submit" class="btn btn-primary pay-submit-btn" id="saleSubmitBtn"><i class="fa-solid fa-check" aria-hidden="true"></i> Complete Sale</button>
        <button type="button" class="btn btn-ghost pay-cancel-btn" onclick="closeCheckout()"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Size picker -->
<div class="modal-overlay hidden" id="sizeModal" role="presentation">
  <div class="modal-box size-modal" role="dialog" aria-modal="true" aria-labelledby="sizeModalTitle">
    <div class="size-modal-head">
      <div>
        <h3 class="modal-title-pay" id="sizeModalTitle">Select size</h3>
        <p class="size-modal-sub" id="sizeModalSub"></p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeSizeModal()" aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="size-modal-grid" id="sizeModalGrid"></div>
  </div>
</div>

<!-- In-frame receipt -->
<div class="pos-receipt-frame hidden" id="receiptFrame" aria-hidden="true">
  <div class="pos-receipt-panel">
    <div class="pos-receipt-toolbar no-print">
      <h3><i class="fa-solid fa-receipt" aria-hidden="true"></i> Sale complete</h3>
      <div class="pos-receipt-actions">
        <button type="button" class="btn btn-primary btn-sm" onclick="printReceipt()"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</button>
        <button type="button" class="btn btn-success btn-sm" onclick="newSale()"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> New Sale</button>
        <?php if (isOwner()): ?>
        <a href="<?= BASE_PATH ?>/sales" class="btn btn-ghost btn-sm"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Sales</a>
        <?php endif; ?>
      </div>
    </div>
    <iframe id="receiptIframe" class="pos-receipt-iframe" title="Receipt"></iframe>
  </div>
</div>

<script>
const BASE = '<?= BASE_PATH ?>';
const UPLOAD_BASE = <?= json_encode(rtrim(BASE_PATH, '/') . '/uploads/products/', JSON_UNESCAPED_SLASHES) ?>;
const POS_PER_PAGE = 10;
const ICON_WARN = '<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>';
const ICON_CART_EMPTY = '<i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>';
const ICON_SHOE = '<i class="fa-solid fa-shoe-prints" aria-hidden="true"></i>';

let cart = [];
let activeCategory = '';
let activeGender = '';
let barcodeMode = false;
let posPage = 1;
let searchTimer;
let viewMode = localStorage.getItem('af_pos_view') || 'grid';

setInterval(() => {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-GH', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
}, 1000);

function setViewMode(mode) {
  viewMode = mode === 'list' ? 'list' : 'grid';
  localStorage.setItem('af_pos_view', viewMode);
  document.getElementById('viewGridBtn').classList.toggle('active', viewMode === 'grid');
  document.getElementById('viewListBtn').classList.toggle('active', viewMode === 'list');
  const grid = document.getElementById('productGrid');
  grid.dataset.view = viewMode;
  grid.classList.toggle('product-grid--list', viewMode === 'list');
}

setViewMode(viewMode);

function scheduleProductSearch() {
  clearTimeout(searchTimer);
  posPage = 1;
  const q = document.getElementById('searchInput').value.trim();
  searchTimer = setTimeout(() => loadPosProducts(), q ? 250 : 0);
}

async function loadPosProducts(forcedPage) {
  if (forcedPage != null) posPage = forcedPage;
  const q = document.getElementById('searchInput').value.trim();
  const params = new URLSearchParams({ q, page: String(posPage), per_page: String(POS_PER_PAGE) });
  if (activeCategory) params.set('category_id', activeCategory);
  if (activeGender) params.set('gender', activeGender);

  const grid = document.getElementById('productGrid');
  grid.innerHTML = '<div class="pos-empty">Loading…</div>';

  try {
    const r = await fetch(BASE + '/pos/product/search?' + params.toString());
    const data = await r.json();
    const products = Array.isArray(data.items) ? data.items : [];
    posPage = data.page || posPage;
    renderProductGrid(products);
    renderPagination(data.total ?? 0, data.totalPages ?? 1, data.page ?? posPage);
  } catch (e) {
    grid.innerHTML = '<div class="pos-empty pos-empty--error">Could not load products.</div>';
    document.getElementById('posPagination').innerHTML = '';
  }
}

function renderPagination(total, totalPages, page) {
  const el = document.getElementById('posPagination');
  if (!total) { el.innerHTML = ''; return; }
  if (totalPages <= 1) {
    el.innerHTML = '<div class="pos-page-info">' + total + ' product' + (total !== 1 ? 's' : '') + '</div>';
    return;
  }
  el.innerHTML = `
    <div class="pos-page-bar">
      <button type="button" class="btn btn-sm btn-ghost" ${page <= 1 ? 'disabled' : ''} onclick="goPosPage(${page - 1})">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev
      </button>
      <span class="pos-page-info">Page ${page} / ${totalPages} · ${total} styles</span>
      <button type="button" class="btn btn-sm btn-ghost" ${page >= totalPages ? 'disabled' : ''} onclick="goPosPage(${page + 1})">
        Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </button>
    </div>`;
}

function goPosPage(p) { loadPosProducts(p); }

function productImageUrl(p) {
  const raw = p.image ?? '';
  const fn = raw != null && String(raw).trim() ? String(raw).trim().split(/[/\\]/).pop() : '';
  if (!fn) return '';
  const base = UPLOAD_BASE.endsWith('/') ? UPLOAD_BASE : UPLOAD_BASE + '/';
  return base + encodeURIComponent(fn);
}

function escHtml(s) { return String(s).replace(/'/g, "\\'").replace(/</g, '&lt;'); }
function escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }

function renderProductGrid(products) {
  const grid = document.getElementById('productGrid');
  grid.dataset.view = viewMode;
  grid.classList.toggle('product-grid--list', viewMode === 'list');

  if (!products.length) {
    grid.innerHTML = '<div class="pos-empty">No products found. Try another search or category.</div>';
    return;
  }

  grid.innerHTML = products.map((p, idx) => {
    const imgSrc = productImageUrl(p);
    const imgBlock = imgSrc
      ? `<img class="pt-img" src="${escAttr(imgSrc)}" alt="${escAttr(p.name)}" loading="lazy" decoding="async" onerror="this.onerror=null;this.outerHTML='<div class=&quot;pt-img pt-img--placeholder&quot; aria-hidden=&quot;true&quot;><i class=&quot;fa-solid fa-shoe-prints&quot; aria-hidden=&quot;true&quot;></i></div>'">`
      : `<div class="pt-img pt-img--placeholder" aria-hidden="true">${ICON_SHOE}</div>`;
    const stockLine = p.quantity <= 0
      ? `<span class="pt-stock-row">${ICON_WARN} Out of stock</span>`
      : `<span class="pt-stock-ok">${escHtml(String(p.quantity))} in stock</span>`;
    const design = escHtml(p.design ?? '');
    const pmin = parseFloat(p.price_min ?? p.selling_price ?? 0);
    const pmax = parseFloat(p.price_max ?? p.selling_price ?? 0);
    const priceLabel = (pmin === pmax)
      ? `GHS ${pmin.toFixed(2)}`
      : `GHS ${pmin.toFixed(2)} – ${pmax.toFixed(2)}`;
    const sizeCount = Number(p.size_count || (p.variants ? p.variants.length : 1) || 1);
    const sizeLabel = sizeCount > 1 ? `${sizeCount} sizes` : `Sz ${escHtml((p.variants && p.variants[0] && p.variants[0].size) || p.size || '—')}`;
    return `
    <div class="product-tile ${p.quantity<=0?'out-of-stock':''}" data-family-idx="${idx}" onclick="openFamilyPicker(${idx})">
      <div class="pt-img-wrap">${imgBlock}</div>
      <div class="pt-body">
        <div class="pt-cat">${escHtml(p.category_name)}</div>
        <div class="pt-name">${escHtml(p.name)}</div>
        <div class="pt-design">${design ? design + ' · ' : ''}${escHtml(p.gender)}</div>
        <div class="pt-meta">
          <span class="pt-size">${sizeLabel}</span>
          <span class="pt-price">${priceLabel}</span>
        </div>
        ${(() => {
          const skus = (p.variants || []).map(v => v.sku).filter(Boolean);
          if (!skus.length) return '';
          const label = skus.length === 1 ? skus[0] : (skus.length + ' SKUs');
          return `<div class="pt-sku mono">${escHtml(label)}</div>`;
        })()}
        <div class="pt-stock">${stockLine}</div>
      </div>
    </div>`;
  }).join('');

  window.__posFamilies = products;
}

function openFamilyPicker(idx) {
  const family = (window.__posFamilies || [])[idx];
  if (!family) return;
  const variants = Array.isArray(family.variants) ? family.variants : [];
  if (variants.length === 1) {
    const v = variants[0];
    addToCart(v.id, v.name, v.design ?? '', v.size, v.selling_price, v.cost_price, v.quantity, v.category_name, v.gender, v.sku ?? '');
    return;
  }
  window.__sizePickerVariants = variants;
  document.getElementById('sizeModalTitle').textContent = family.name;
  const design = family.design ? family.design + ' · ' : '';
  document.getElementById('sizeModalSub').textContent = design + (family.gender || '') + ' · pick a size';
  const grid = document.getElementById('sizeModalGrid');
  grid.innerHTML = variants.map((v, i) => {
    const oos = v.quantity <= 0;
    return `
      <button type="button" class="size-pick ${oos ? 'is-oos' : ''}" ${oos ? 'disabled' : ''}
        onclick="pickVariantAt(${i})">
        <span class="size-pick-sz">Sz ${escHtml(String(v.size))}</span>
        ${v.sku ? `<span class="size-pick-sku mono">${escHtml(String(v.sku))}</span>` : ''}
        <span class="size-pick-price">GHS ${parseFloat(v.selling_price).toFixed(2)}</span>
        <span class="size-pick-stock">${oos ? 'Out of stock' : (v.quantity + ' left')}</span>
      </button>`;
  }).join('');
  document.getElementById('sizeModal').classList.remove('hidden');
}

function pickVariantAt(i) {
  const v = (window.__sizePickerVariants || [])[i];
  if (!v) return;
  addToCart(v.id, v.name, v.design ?? '', v.size, v.selling_price, v.cost_price, v.quantity, v.category_name, v.gender, v.sku ?? '');
  closeSizeModal();
}

function closeSizeModal() {
  document.getElementById('sizeModal').classList.add('hidden');
}

document.getElementById('sizeModal').addEventListener('click', e => {
  if (e.target.id === 'sizeModal') closeSizeModal();
});

function filterCat(btn, catId) {
  document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  activeCategory = catId;
  loadPosProducts(1);
}
function filterGender(btn, gender) {
  const active = btn.classList.contains('active');
  document.querySelectorAll('.gender-filter').forEach(b => b.classList.remove('active'));
  if (!active) { btn.classList.add('active'); activeGender = gender; }
  else activeGender = '';
  loadPosProducts(1);
}

function toggleBarcodeMode() {
  barcodeMode = !barcodeMode;
  const wrap = document.getElementById('barcodeWrap');
  const input = document.getElementById('searchInput');
  wrap.hidden = !barcodeMode;
  input.style.display = barcodeMode ? 'none' : 'block';
  if (barcodeMode) document.getElementById('barcodeInput').focus();
}

document.getElementById('barcodeInput').addEventListener('keydown', async e => {
  if (e.key !== 'Enter') return;
  const bc = e.target.value.trim();
  if (!bc) return;
  const r = await fetch(BASE + '/pos/product/barcode/' + encodeURIComponent(bc));
  const d = await r.json();
  if (d.found) {
    const p = d.product;
    addToCart(p.id, p.name, p.design ?? '', p.size, p.selling_price, p.cost_price, p.quantity, p.category_name, p.gender, p.sku ?? '');
    e.target.value = '';
  } else {
    e.target.style.borderColor = 'var(--danger)';
    setTimeout(() => { e.target.style.borderColor = ''; }, 1000);
  }
});

function addToCart(id, name, design, size, price, cost, stock, category, gender, sku) {
  const existing = cart.find(i => i.product_id == id);
  if (existing) {
    if (existing.qty >= stock) return;
    existing.qty++;
  } else {
    cart.push({ product_id: id, name, design, size, sku: sku || '', price: parseFloat(price), cost: parseFloat(cost), stock: parseInt(stock), category, gender, qty: 1 });
  }
  renderCart();
}
function updateQty(id, delta) {
  const item = cart.find(i => i.product_id == id);
  if (!item) return;
  item.qty = Math.max(0, Math.min(item.stock, item.qty + delta));
  if (item.qty === 0) cart = cart.filter(i => i.product_id != id);
  renderCart();
}
function removeItem(id) {
  cart = cart.filter(i => i.product_id != id);
  renderCart();
}

function renderCart() {
  const container = document.getElementById('cartItems');
  const count = cart.reduce((a, i) => a + i.qty, 0);
  document.getElementById('cartCount').textContent = `(${count} item${count !== 1 ? 's' : ''})`;
  if (!cart.length) {
    container.innerHTML = `<div class="cart-empty"><span class="cart-empty-ic">${ICON_CART_EMPTY}</span><span>Cart is empty</span><span class="cart-empty-hint">Add products from the left</span></div>`;
    document.getElementById('checkoutBtn').disabled = true;
    updateTotals();
    return;
  }
  container.innerHTML = cart.map(item => `
    <div class="cart-item">
      <div class="ci-info">
        <div class="ci-name">${escHtml(item.name)}</div>
        <div class="ci-detail">Sz ${escHtml(item.size)}${item.sku ? ' · ' + escHtml(item.sku) : ''} · ${escHtml(item.gender)}${item.design ? ' · ' + escHtml(item.design) : ''}</div>
      </div>
      <div class="qty-control">
        <button type="button" class="qty-btn" onclick="updateQty(${item.product_id},-1)">−</button>
        <span class="qty-val">${item.qty}</span>
        <button type="button" class="qty-btn" onclick="updateQty(${item.product_id},1)">+</button>
      </div>
      <div class="ci-price">GHS ${(item.price * item.qty).toFixed(2)}</div>
      <button type="button" class="remove-btn" onclick="removeItem(${item.product_id})" aria-label="Remove"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>`).join('');
  document.getElementById('checkoutBtn').disabled = false;
  updateTotals();
}

function updateTotals() {
  const sub = cart.reduce((a, i) => a + (i.price * i.qty), 0);
  const disc = parseFloat(document.getElementById('discountInput').value) || 0;
  const tot = Math.max(0, sub - disc);
  document.getElementById('totSubtotal').textContent = 'GHS ' + sub.toFixed(2);
  document.getElementById('totTotal').textContent = 'GHS ' + tot.toFixed(2);
}

function openCheckout() {
  const sub = cart.reduce((a, i) => a + (i.price * i.qty), 0);
  const disc = parseFloat(document.getElementById('discountInput').value) || 0;
  const tot = Math.max(0, sub - disc);
  document.getElementById('cartJson').value = JSON.stringify(cart.map(i => ({product_id: i.product_id, qty: i.qty})));
  document.getElementById('modalSubtotal').textContent = 'GHS ' + sub.toFixed(2);
  document.getElementById('modalDiscount').textContent = 'GHS ' + disc.toFixed(2);
  document.getElementById('modalTotal').textContent = 'GHS ' + tot.toFixed(2);
  document.getElementById('discountModal').value = disc;
  document.getElementById('tenderedInput').value = tot.toFixed(2);
  document.getElementById('saleError').hidden = true;
  calcChange();
  document.getElementById('payModal').classList.remove('hidden');
}
function closeCheckout() { document.getElementById('payModal').classList.add('hidden'); }

function syncDiscount() {
  const d = document.getElementById('discountModal').value;
  document.getElementById('discountInput').value = d;
  updateTotals();
  const sub = cart.reduce((a, i) => a + (i.price * i.qty), 0);
  const tot = Math.max(0, sub - (parseFloat(d) || 0));
  document.getElementById('modalDiscount').textContent = 'GHS ' + (parseFloat(d) || 0).toFixed(2);
  document.getElementById('modalTotal').textContent = 'GHS ' + tot.toFixed(2);
  calcChange();
}
function calcChange() {
  const tot = parseFloat(document.getElementById('modalTotal').textContent.replace('GHS ', '')) || 0;
  const tendered = parseFloat(document.getElementById('tenderedInput').value) || 0;
  document.getElementById('changeDisplay').value = 'GHS ' + Math.max(0, tendered - tot).toFixed(2);
}
function onPayMethodChange(v) {
  document.getElementById('cashFields').style.display = v === 'cash' ? 'block' : 'none';
  document.getElementById('momoFields').style.display = v === 'momo' ? 'block' : 'none';
}

document.getElementById('payModal').addEventListener('click', e => {
  if (e.target === document.getElementById('payModal')) closeCheckout();
});

document.getElementById('saleForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('saleSubmitBtn');
  const err = document.getElementById('saleError');
  err.hidden = true;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Processing…';
  try {
    const fd = new FormData(e.target);
    const r = await fetch(BASE + '/pos/sale', {
      method: 'POST',
      body: fd,
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await r.json();
    if (!data.ok) {
      err.textContent = data.error || 'Sale failed.';
      err.hidden = false;
      return;
    }
    closeCheckout();
    showReceipt(data.receipt_url);
  } catch (ex) {
    err.textContent = 'Network error. Please try again.';
    err.hidden = false;
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Complete Sale';
  }
});

function showReceipt(url) {
  const frame = document.getElementById('receiptFrame');
  const iframe = document.getElementById('receiptIframe');
  iframe.src = url;
  frame.classList.remove('hidden');
  frame.setAttribute('aria-hidden', 'false');
}

function printReceipt() {
  const iframe = document.getElementById('receiptIframe');
  if (!iframe || !iframe.src || iframe.src === 'about:blank') return;
  // Print from the embedded receipt (same page / no new tab)
  const run = () => {
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (e) {
      console.warn('Print failed', e);
    }
  };
  // If still loading, wait for load once
  try {
    const doc = iframe.contentDocument;
    if (doc && doc.readyState === 'complete') run();
    else iframe.addEventListener('load', run, { once: true });
  } catch (e) {
    run();
  }
}

function newSale() {
  cart = [];
  renderCart();
  document.getElementById('discountInput').value = 0;
  updateTotals();
  document.getElementById('receiptFrame').classList.add('hidden');
  document.getElementById('receiptFrame').setAttribute('aria-hidden', 'true');
  document.getElementById('receiptIframe').src = 'about:blank';
  loadPosProducts(1);
}

loadPosProducts(1);
</script>
</body>
</html>
