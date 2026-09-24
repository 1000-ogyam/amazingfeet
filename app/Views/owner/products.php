<?php
$pageTitle = 'Products';
$cp = '/products';
$pagination = $pagination ?? ['page'=>1,'perPage'=>10,'total'=>0,'totalPages'=>1];
$styleCount = (int)($styleCount ?? count($products));
$page = (int)$pagination['page'];
$perPage = (int)$pagination['perPage'];
$total = (int)$pagination['total'];
$totalPages = (int)$pagination['totalPages'];
$from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to = min($page * $perPage, $total);

$qs = static function (array $extra = []) use ($filters, $perPage): string {
    $params = array_merge($filters, ['per_page' => $perPage], $extra);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
};

$sizeHints = [];
foreach ($products as $p) {
    foreach (preg_split('/\s*,\s*/', (string)($p['sizes'] ?? '')) as $sz) {
        $sz = trim($sz);
        if ($sz !== '') $sizeHints[$sz] = true;
    }
}
ksort($sizeHints, SORT_NATURAL);

ob_start();
?>
<div class="products-stats">
  <div class="products-stat">
    <div class="text-muted">Products</div><div class="font-bold"><?= $styleCount ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Size variants</div><div class="font-bold"><?= (int)($variantCount ?? 0) ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Retail Value</div><div class="font-bold text-accent"><?= money($stockValue['retail_value']??0) ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Cost Value</div><div class="font-bold"><?= money($stockValue['cost_value']??0) ?></div>
  </div>
</div>

<div class="products-toolbar">
  <form method="GET" class="products-filters" id="productsFilterForm">
    <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">
    <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search name, SKU, barcode…">
    <select name="category_id">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($filters['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="gender">
      <option value="">All Gender</option>
      <?php foreach(['Boys','Girls','Unisex','Ladies'] as $g): ?>
      <option value="<?= $g ?>" <?= ($filters['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
      <?php endforeach; ?>
    </select>
    <label class="products-low-stock">
      <input type="checkbox" name="low_stock" value="1" <?= !empty($filters['low_stock'])?'checked':'' ?>> Low Stock
    </label>
    <div class="products-filter-actions">
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a href="<?= BASE_PATH ?>/products" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
    </div>
  </form>
  <a href="<?= BASE_PATH ?>/products/create" class="btn btn-primary products-add-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product</a>
</div>

<form method="POST" action="<?= BASE_PATH ?>/products/bulk-prices" id="productsBulkForm">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <input type="hidden" name="return_search" value="<?= e($filters['search'] ?? '') ?>">
  <input type="hidden" name="return_category_id" value="<?= e((string)($filters['category_id'] ?? '')) ?>">
  <input type="hidden" name="return_gender" value="<?= e($filters['gender'] ?? '') ?>">
  <input type="hidden" name="return_low_stock" value="<?= e($filters['low_stock'] ?? '') ?>">
  <input type="hidden" name="return_page" value="<?= (int)$page ?>">
  <input type="hidden" name="return_per_page" value="<?= (int)$perPage ?>">

  <div class="card products-card">
    <div class="card-header products-card-header">
      <h3><i class="fa-solid fa-box" aria-hidden="true"></i> Products</h3>
      <span class="text-muted text-sm products-range">
        <?php if ($total > 0): ?>
          Showing <?= $from ?>–<?= $to ?> of <?= $total ?>
        <?php else: ?>
          No products
        <?php endif; ?>
      </span>
      <div class="products-bulk-bar">
        <span class="text-sm text-muted" id="productsBulkCount">0 selected</span>
        <button type="button" class="btn btn-ghost btn-sm" id="openBulkPricesBtn" disabled>
          <i class="fa-solid fa-tags" aria-hidden="true"></i> Apply prices
        </button>
      </div>
      <label class="products-per-page">
        <span class="text-muted">Per page</span>
        <select id="productsPerPage" aria-label="Rows per page">
          <?php foreach ([10, 20, 50, 100] as $n): ?>
          <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="table-wrap products-table-wrap">
      <table class="products-table">
        <thead>
          <tr>
            <th class="col-check">
              <input type="checkbox" id="selectAllProducts" aria-label="Select all products" style="width:auto">
            </th>
            <th class="col-product">Product</th>
            <th class="col-sku">SKU</th>
            <th class="col-cat">Category</th>
            <th class="col-gender">Gender</th>
            <th class="col-design">Design</th>
            <th class="col-sizes">Sizes</th>
            <th class="col-price">Price</th>
            <th class="col-stock">Stock</th>
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
          <tr><td colspan="10" class="products-empty">No products found.</td></tr>
          <?php endif; ?>
          <?php foreach ($products as $p):
            $sizeCount = (int)($p['size_count'] ?? 1);
            $isLow = (int)($p['low_size_count'] ?? 0) > 0;
            $pmin = (float)($p['price_min'] ?? 0);
            $pmax = (float)($p['price_max'] ?? 0);
            $priceLabel = ($pmin === $pmax) ? money($pmin) : money($pmin).' – '.money($pmax);
            $catSlug = str_contains(strtolower($p['category_name']),'school')?'school':(str_contains(strtolower($p['category_name']),'ladies')?'ladies':'preloved');
            $sizesAttr = e((string)($p['sizes'] ?? ''));
          ?>
          <tr>
            <td class="col-check">
              <input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" class="product-row-check"
                     data-sizes="<?= $sizesAttr ?>" data-name="<?= e($p['name']) ?>"
                     style="width:auto" aria-label="Select <?= e($p['name']) ?>">
            </td>
            <td class="products-name-cell" title="<?= e($p['name']) ?>"><?= e($p['name']) ?></td>
            <td class="mono text-sm col-sku"><?= e($p['sku'] ?? '—') ?></td>
            <td><span class="badge badge-<?= $catSlug ?>"><?= e($p['category_name']) ?></span></td>
            <td><span class="badge badge-<?= strtolower($p['gender']) ?>"><?= e($p['gender']) ?></span></td>
            <td class="text-sm text-muted col-design" title="<?= e($p['design'] ?? '') ?>"><?= e(($p['design'] ?? '') !== '' ? $p['design'] : '—') ?></td>
            <td class="col-sizes" title="<?= $sizesAttr ?>">
              <strong><?= $sizeCount ?></strong>
              <span class="text-muted text-sm">sz</span>
            </td>
            <td class="font-bold text-sm col-price"><?= $priceLabel ?></td>
            <td class="col-stock">
              <span class="badge <?= $isLow?'badge-low':'badge-ok' ?>"><?= (int)$p['quantity'] ?></span>
              <?php if ($isLow): ?><span class="products-low-hint"><?= (int)$p['low_size_count'] ?> low</span><?php endif; ?>
            </td>
            <td class="products-actions">
              <a href="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/edit" class="btn btn-ghost btn-xs"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
              <button type="submit" formaction="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/duplicate" formmethod="POST"
                      class="btn btn-ghost btn-xs" title="Duplicate this product and all sizes"
                      onclick="return confirm('Duplicate “<?= e($p['name']) ?>” with all <?= $sizeCount ?> size<?= $sizeCount===1?'':'s' ?>? Stock on the copy will start at 0.')">
                <i class="fa-solid fa-copy" aria-hidden="true"></i>
              </button>
              <button type="submit" formaction="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/delete" formmethod="POST"
                      class="btn btn-danger btn-xs" title="Delete"
                      onclick="return confirm('Remove this product and all <?= $sizeCount ?> size<?= $sizeCount===1?'':'s' ?>?')">
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1 || $total > 0): ?>
    <div class="products-pager">
      <div class="products-pager-info text-muted text-sm">
        Page <?= $page ?> of <?= $totalPages ?>
      </div>
      <div class="products-pager-btns">
        <?php if ($page > 1): ?>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/products?<?= e($qs(['page' => 1])) ?>" title="First"><i class="fa-solid fa-angles-left" aria-hidden="true"></i></a>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/products?<?= e($qs(['page' => $page - 1])) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev</a>
        <?php else: ?>
        <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-angles-left" aria-hidden="true"></i></button>
        <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev</button>
        <?php endif; ?>

        <?php
          $window = 2;
          $start = max(1, $page - $window);
          $end = min($totalPages, $page + $window);
          if ($start > 1) echo '<span class="products-pager-ellipsis">…</span>';
          for ($i = $start; $i <= $end; $i++):
            if ($i === $page):
        ?>
        <span class="btn btn-primary btn-sm products-page-current"><?= $i ?></span>
        <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/products?<?= e($qs(['page' => $i])) ?>"><?= $i ?></a>
        <?php
            endif;
          endfor;
          if ($end < $totalPages) echo '<span class="products-pager-ellipsis">…</span>';
        ?>

        <?php if ($page < $totalPages): ?>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/products?<?= e($qs(['page' => $page + 1])) ?>">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
        <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/products?<?= e($qs(['page' => $totalPages])) ?>" title="Last"><i class="fa-solid fa-angles-right" aria-hidden="true"></i></a>
        <?php else: ?>
        <button type="button" class="btn btn-ghost btn-sm" disabled>Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-angles-right" aria-hidden="true"></i></button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bulk price modal -->
  <div class="modal-overlay hidden" id="bulkPriceModal" role="presentation">
    <div class="modal-box bulk-price-modal" role="dialog" aria-modal="true" aria-labelledby="bulkPriceTitle">
      <div class="bulk-price-head">
        <h3 id="bulkPriceTitle"><i class="fa-solid fa-tags" aria-hidden="true"></i> Apply prices</h3>
        <button type="button" class="btn btn-ghost btn-sm" id="closeBulkPriceModal" aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
      <p class="text-muted text-sm bulk-price-lead">
        Choose which products to include, then set cost and/or sell. Leave a price blank to skip that field.
      </p>

      <div class="bulk-price-grid">
        <div class="bulk-price-main">
          <div class="form-group">
            <label>1. Apply to</label>
            <select name="scope" id="bulkScope">
              <option value="all">All sizes in included products</option>
              <option value="size">One size only (same size across products)</option>
            </select>
          </div>

          <div class="form-group" id="bulkSizeWrap" hidden>
            <label>Size *</label>
            <input type="text" name="size" id="bulkSizeInput" list="bulkSizeList" placeholder="e.g. 32" autocomplete="off">
            <datalist id="bulkSizeList"></datalist>
            <label class="bulk-opt-check" id="bulkAutoExcludeWrap" style="margin-top:.45rem">
              <input type="checkbox" id="bulkAutoExclude" checked style="width:auto">
              Auto-exclude products that don’t have this size
            </label>
          </div>

          <div class="form-group">
            <label>2. Price change</label>
            <select name="price_mode" id="bulkPriceMode">
              <option value="set">Set to this price</option>
              <option value="adjust">Adjust by amount (+ / −)</option>
            </select>
            <p class="text-muted text-sm" id="bulkModeHint" style="margin-top:.35rem">Example: set sell to 180.00 for every included size.</p>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label id="bulkCostLbl">Cost (GHS)</label>
              <input type="number" name="cost_price" id="bulkCost" step="0.01" placeholder="Blank = skip">
            </div>
            <div class="form-group">
              <label id="bulkSellLbl">Sell (GHS)</label>
              <input type="number" name="selling_price" id="bulkSell" step="0.01" placeholder="Blank = skip">
            </div>
          </div>

          <label class="bulk-opt-check">
            <input type="checkbox" name="only_empty" id="bulkOnlyEmpty" value="1" style="width:auto">
            Only fill empty / zero prices (don’t overwrite existing)
          </label>
        </div>

        <div class="bulk-price-side">
          <div class="bulk-include-head">
            <strong>3. Products to include</strong>
            <span class="text-muted text-sm" id="bulkIncludeCount">0</span>
          </div>
          <div class="bulk-include-actions">
            <button type="button" class="btn btn-ghost btn-xs" id="bulkIncludeAll">All</button>
            <button type="button" class="btn btn-ghost btn-xs" id="bulkIncludeNone">None</button>
            <button type="button" class="btn btn-ghost btn-xs" id="bulkIncludeHasSize" hidden>Only with size</button>
          </div>
          <div class="bulk-include-list" id="bulkIncludeList" aria-label="Included products"></div>
          <p class="text-muted text-sm" style="margin-top:.45rem">Uncheck any product to exclude it from this update.</p>
        </div>
      </div>

      <div class="bulk-price-summary" id="bulkPriceSummary">Select products to see a preview.</div>

      <div class="bulk-price-actions">
        <button type="submit" class="btn btn-primary" id="bulkPriceSubmit">
          <i class="fa-solid fa-check" aria-hidden="true"></i> Apply now
        </button>
        <button type="button" class="btn btn-ghost" id="closeBulkPriceModal2">Cancel</button>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  const form = document.getElementById('productsBulkForm');
  const selectAll = document.getElementById('selectAllProducts');
  const countEl = document.getElementById('productsBulkCount');
  const openBtn = document.getElementById('openBulkPricesBtn');
  const modal = document.getElementById('bulkPriceModal');
  const scope = document.getElementById('bulkScope');
  const sizeWrap = document.getElementById('bulkSizeWrap');
  const sizeInput = document.getElementById('bulkSizeInput');
  const sizeList = document.getElementById('bulkSizeList');
  const includeList = document.getElementById('bulkIncludeList');
  const includeCount = document.getElementById('bulkIncludeCount');
  const autoExclude = document.getElementById('bulkAutoExclude');
  const hasSizeBtn = document.getElementById('bulkIncludeHasSize');
  const priceMode = document.getElementById('bulkPriceMode');
  const modeHint = document.getElementById('bulkModeHint');
  const onlyEmpty = document.getElementById('bulkOnlyEmpty');
  const summary = document.getElementById('bulkPriceSummary');
  const costInput = document.getElementById('bulkCost');
  const sellInput = document.getElementById('bulkSell');

  const checks = () => [...form.querySelectorAll('.product-row-check')];
  const includeChecks = () => [...includeList.querySelectorAll('.bulk-include-check')];

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
  }
  function parseSizes(raw) {
    return String(raw || '').split(',').map(s => s.trim()).filter(Boolean);
  }
  function hasSize(sizes, size) {
    const t = String(size || '').trim().toLowerCase();
    if (!t) return true;
    return sizes.some(s => s.toLowerCase() === t);
  }

  function sync() {
    const list = checks();
    const n = list.filter(c => c.checked).length;
    countEl.textContent = n + ' selected';
    openBtn.disabled = n === 0;
    if (selectAll) {
      selectAll.checked = list.length > 0 && n === list.length;
      selectAll.indeterminate = n > 0 && n < list.length;
    }
  }

  function refreshSizeHints(fromInclude) {
    const set = new Set();
    const source = fromInclude
      ? includeChecks().filter(c => c.checked)
      : checks().filter(c => c.checked);
    source.forEach(c => parseSizes(c.dataset.sizes).forEach(s => set.add(s)));
    const sorted = [...set].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
    sizeList.innerHTML = sorted.map(s => '<option value="' + esc(s) + '">').join('');
  }

  function buildIncludeList() {
    const selected = checks().filter(c => c.checked);
    includeList.innerHTML = selected.map(c => {
      const id = c.value;
      const name = c.dataset.name || ('Product #' + id);
      const sizes = parseSizes(c.dataset.sizes);
      return `<label class="bulk-include-item" data-id="${esc(id)}">
        <input type="checkbox" class="bulk-include-check" name="include_ids[]" value="${esc(id)}"
               data-sizes="${esc(sizes.join(', '))}" data-name="${esc(name)}" checked style="width:auto">
        <span class="bulk-include-meta">
          <span class="bulk-include-name">${esc(name)}</span>
          <span class="bulk-include-sizes text-muted">${sizes.length ? ('Sizes: ' + esc(sizes.join(', '))) : 'No sizes listed'}</span>
          <span class="bulk-include-status"></span>
        </span>
      </label>`;
    }).join('') || '<p class="text-muted text-sm">No products selected.</p>';
    updateIncludeUI();
  }

  function updateIncludeUI() {
    const sizeMode = scope.value === 'size';
    const size = sizeInput.value.trim();
    sizeWrap.hidden = !sizeMode;
    sizeInput.required = sizeMode;
    hasSizeBtn.hidden = !sizeMode;
    document.getElementById('bulkAutoExcludeWrap').style.display = sizeMode ? '' : 'none';

    let included = 0;
    let missing = 0;
    includeChecks().forEach(c => {
      const item = c.closest('.bulk-include-item');
      const status = item.querySelector('.bulk-include-status');
      const sizes = parseSizes(c.dataset.sizes);
      const match = !sizeMode || !size || hasSize(sizes, size);
      item.classList.toggle('is-missing-size', sizeMode && !!size && !match);
      if (sizeMode && size && autoExclude.checked && !match) {
        c.checked = false;
      }
      item.classList.toggle('is-excluded', !c.checked);
      if (sizeMode && size) {
        status.textContent = match ? ('Has size ' + size) : ('No size ' + size);
        status.className = 'bulk-include-status ' + (match ? 'ok' : 'warn');
      } else {
        status.textContent = '';
        status.className = 'bulk-include-status';
      }
      if (c.checked) {
        included++;
        if (sizeMode && size && !match) missing++;
      }
    });

    includeCount.textContent = included + ' included';
    refreshSizeHints(true);

    const cost = costInput.value.trim();
    const sell = sellInput.value.trim();
    const mode = priceMode.value;
    let line = included + ' product' + (included === 1 ? '' : 's') + ' included';
    if (sizeMode && size) {
      line += ' · targeting size ' + size;
      if (missing > 0) line += ' · ' + missing + ' without that size still checked';
    } else if (!sizeMode) {
      line += ' · all their sizes';
    }
    if (cost || sell) {
      const bits = [];
      if (cost) bits.push((mode === 'adjust' ? ((Number(cost) >= 0 ? '+' : '') + cost) : cost) + ' cost');
      if (sell) bits.push((mode === 'adjust' ? ((Number(sell) >= 0 ? '+' : '') + sell) : sell) + ' sell');
      line += ' · ' + (mode === 'adjust' ? 'adjust ' : 'set ') + bits.join(' & ');
    }
    if (onlyEmpty.checked && mode === 'set') line += ' · empty prices only';
    summary.textContent = line;
    summary.classList.toggle('is-ready', included > 0 && (!!cost || !!sell) && (!sizeMode || !!size));
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      checks().forEach(c => { c.checked = selectAll.checked; });
      sync();
    });
  }
  form.addEventListener('change', e => {
    if (e.target.classList.contains('product-row-check')) sync();
    if (e.target.classList.contains('bulk-include-check') || e.target === autoExclude || e.target === onlyEmpty || e.target === priceMode || e.target === scope) {
      updateIncludeUI();
    }
  });
  sizeInput.addEventListener('input', updateIncludeUI);
  costInput.addEventListener('input', updateIncludeUI);
  sellInput.addEventListener('input', updateIncludeUI);

  priceMode.addEventListener('change', () => {
    const adj = priceMode.value === 'adjust';
    document.getElementById('bulkCostLbl').textContent = adj ? 'Cost change (GHS)' : 'Cost (GHS)';
    document.getElementById('bulkSellLbl').textContent = adj ? 'Sell change (GHS)' : 'Sell (GHS)';
    costInput.placeholder = adj ? 'e.g. 5 or -5' : 'Blank = skip';
    sellInput.placeholder = adj ? 'e.g. 10 or -10' : 'Blank = skip';
    if (adj) { costInput.removeAttribute('min'); sellInput.removeAttribute('min'); }
    else { costInput.min = '0'; sellInput.min = '0'; }
    modeHint.textContent = adj
      ? 'Example: enter 10 to add GHS 10, or -5 to reduce by GHS 5.'
      : 'Example: set sell to 180.00 for every included size.';
    onlyEmpty.disabled = adj;
    if (adj) onlyEmpty.checked = false;
    updateIncludeUI();
  });

  document.getElementById('bulkIncludeAll').addEventListener('click', () => {
    includeChecks().forEach(c => { c.checked = true; });
    updateIncludeUI();
  });
  document.getElementById('bulkIncludeNone').addEventListener('click', () => {
    includeChecks().forEach(c => { c.checked = false; });
    updateIncludeUI();
  });
  hasSizeBtn.addEventListener('click', () => {
    const size = sizeInput.value.trim();
    includeChecks().forEach(c => {
      c.checked = hasSize(parseSizes(c.dataset.sizes), size);
    });
    updateIncludeUI();
  });

  function openModal() {
    buildIncludeList();
    refreshSizeHints(false);
    modal.classList.remove('hidden');
    updateIncludeUI();
  }
  function closeModal() { modal.classList.add('hidden'); }

  openBtn.addEventListener('click', openModal);
  document.getElementById('closeBulkPriceModal').addEventListener('click', closeModal);
  document.getElementById('closeBulkPriceModal2').addEventListener('click', closeModal);
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  form.addEventListener('submit', e => {
    if (e.submitter && e.submitter.getAttribute('formaction')) return;
    const included = includeChecks().filter(c => c.checked);
    if (!included.length) {
      e.preventDefault();
      alert('Include at least one product (uncheck excludes it).');
      return;
    }
    const cost = costInput.value.trim();
    const sell = sellInput.value.trim();
    if (!cost && !sell) {
      e.preventDefault();
      alert('Enter a cost and/or selling price.');
      return;
    }
    if (scope.value === 'size' && !sizeInput.value.trim()) {
      e.preventDefault();
      alert('Enter the size to update.');
      return;
    }
    const n = included.length;
    const target = scope.value === 'size'
      ? ('size ' + sizeInput.value.trim() + ' on ' + n + ' product' + (n === 1 ? '' : 's'))
      : ('all sizes on ' + n + ' product' + (n === 1 ? '' : 's'));
    const modeLabel = priceMode.value === 'adjust' ? 'Adjust prices for ' : 'Set prices for ';
    if (!confirm(modeLabel + target + '?')) e.preventDefault();
  });

  const sel = document.getElementById('productsPerPage');
  if (sel) {
    sel.addEventListener('change', function () {
      const u = new URL(window.location.href);
      u.searchParams.set('per_page', this.value);
      u.searchParams.set('page', '1');
      window.location.href = u.pathname + '?' + u.searchParams.toString();
    });
  }

  sync();
})();
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
