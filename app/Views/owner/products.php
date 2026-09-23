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
                     data-sizes="<?= $sizesAttr ?>" style="width:auto" aria-label="Select <?= e($p['name']) ?>">
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
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="bulkPriceTitle" style="max-width:480px">
      <h3 id="bulkPriceTitle" style="margin-bottom:.35rem"><i class="fa-solid fa-tags" aria-hidden="true"></i> Apply prices</h3>
      <p class="text-muted text-sm" style="margin-bottom:1rem">
        Set cost and/or sell price on <strong id="bulkPriceSelectedLbl">0</strong> selected product<span id="bulkPricePlural">s</span>.
        Leave a price blank to keep existing values.
      </p>

      <div class="form-group">
        <label>Apply to</label>
        <select name="scope" id="bulkScope">
          <option value="all">All sizes in selected products</option>
          <option value="size">One size only (same size across products)</option>
        </select>
      </div>

      <div class="form-group" id="bulkSizeWrap" hidden>
        <label>Size *</label>
        <input type="text" name="size" id="bulkSizeInput" list="bulkSizeList" placeholder="e.g. 32" autocomplete="off">
        <datalist id="bulkSizeList">
          <?php foreach (array_keys($sizeHints) as $sz): ?>
          <option value="<?= e($sz) ?>">
          <?php endforeach; ?>
        </datalist>
        <p class="text-muted text-sm" style="margin-top:.35rem">Example: set sell price for size 32 on products A, B, and C.</p>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Cost price (GHS)</label>
          <input type="number" name="cost_price" id="bulkCost" step="0.01" min="0" placeholder="Leave blank = no change">
        </div>
        <div class="form-group">
          <label>Selling price (GHS)</label>
          <input type="number" name="selling_price" id="bulkSell" step="0.01" min="0" placeholder="Leave blank = no change">
        </div>
      </div>

      <div class="flex gap-1" style="flex-wrap:wrap;margin-top:.5rem">
        <button type="submit" class="btn btn-primary" id="bulkPriceSubmit">
          <i class="fa-solid fa-check" aria-hidden="true"></i> Apply now
        </button>
        <button type="button" class="btn btn-ghost" id="closeBulkPriceModal">Cancel</button>
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
  const selectedLbl = document.getElementById('bulkPriceSelectedLbl');
  const plural = document.getElementById('bulkPricePlural');
  const sizeList = document.getElementById('bulkSizeList');

  const checks = () => [...form.querySelectorAll('.product-row-check')];

  function sync() {
    const list = checks();
    const n = list.filter(c => c.checked).length;
    countEl.textContent = n + ' selected';
    openBtn.disabled = n === 0;
    if (selectAll) {
      selectAll.checked = list.length > 0 && n === list.length;
      selectAll.indeterminate = n > 0 && n < list.length;
    }
    selectedLbl.textContent = String(n);
    plural.textContent = n === 1 ? '' : 's';
  }

  function refreshSizeHints() {
    const set = new Set();
    checks().filter(c => c.checked).forEach(c => {
      String(c.dataset.sizes || '').split(',').forEach(s => {
        const t = s.trim();
        if (t) set.add(t);
      });
    });
    const sorted = [...set].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
    sizeList.innerHTML = sorted.map(s => '<option value="' + s.replace(/"/g, '&quot;') + '">').join('');
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      checks().forEach(c => { c.checked = selectAll.checked; });
      sync();
    });
  }
  form.addEventListener('change', e => {
    if (e.target.classList.contains('product-row-check')) sync();
  });

  scope.addEventListener('change', () => {
    const one = scope.value === 'size';
    sizeWrap.hidden = !one;
    sizeInput.required = one;
  });

  openBtn.addEventListener('click', () => {
    refreshSizeHints();
    modal.classList.remove('hidden');
  });
  document.getElementById('closeBulkPriceModal').addEventListener('click', () => modal.classList.add('hidden'));
  modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });

  form.addEventListener('submit', e => {
    if (e.submitter && e.submitter.getAttribute('formaction')) return; // delete buttons
    const n = checks().filter(c => c.checked).length;
    if (!n) {
      e.preventDefault();
      alert('Select at least one product.');
      return;
    }
    const cost = document.getElementById('bulkCost').value.trim();
    const sell = document.getElementById('bulkSell').value.trim();
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
    const target = scope.value === 'size'
      ? ('size ' + sizeInput.value.trim() + ' on ' + n + ' product' + (n === 1 ? '' : 's'))
      : ('all sizes on ' + n + ' product' + (n === 1 ? '' : 's'));
    if (!confirm('Apply these prices to ' + target + '?')) e.preventDefault();
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
