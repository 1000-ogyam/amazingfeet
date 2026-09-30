<?php
$pageTitle = 'New purchase';
$cp = '/purchases';
$backUrl = BASE_PATH.'/purchases';
$products = $products ?? [];
ob_start();

/** Group size variants into styles for bulk add */
$styles = [];
foreach ($products as $p) {
    $key = !empty($p['style_key'])
        ? 'sk:'.$p['style_key']
        : 'c:'.(int)$p['category_id'].'|'.strtolower(trim($p['name'])).'|'.($p['gender']??'').'|'.strtolower(trim((string)($p['design'] ?? '')));
    if (!isset($styles[$key])) {
        $styles[$key] = [
            'key'      => $key,
            'name'     => $p['name'],
            'gender'   => $p['gender'] ?? '',
            'design'   => $p['design'] ?? '',
            'category' => $p['category_name'] ?? '',
            'label'    => $p['name']
                . (!empty($p['design']) ? ' · '.$p['design'] : '')
                . ' · '.($p['gender'] ?? ''),
            'stock'    => 0,
            'search'   => '',
            'sizes'    => [],
        ];
    }
    $styles[$key]['stock'] += (int)$p['quantity'];
    $styles[$key]['search'] .= ' '.($p['sku'] ?? '').' '.($p['barcode'] ?? '');
    $styles[$key]['sizes'][] = [
        'id'    => (int)$p['id'],
        'size'  => (string)$p['size'],
        'sku'   => $p['sku'] ?? '',
        'cost'  => (float)$p['cost_price'],
        'stock' => (int)$p['quantity'],
        'low'   => (int)$p['quantity'] <= (int)($p['low_stock_threshold'] ?? LOW_STOCK_THRESHOLD),
        'label' => $p['name'].' · Sz '.$p['size']
            . (!empty($p['sku']) ? ' · '.$p['sku'] : '')
            . ' · stock '.(int)$p['quantity'],
    ];
}
foreach ($styles as &$st) {
    $st['search'] = mb_strtolower(implode(' ', [
        $st['name'], $st['design'], $st['gender'], $st['category'], $st['search'],
    ]));
}
unset($st);
// Natural-sort sizes within each style
foreach ($styles as &$st) {
    usort($st['sizes'], static function ($a, $b) {
        $na = is_numeric($a['size']) ? (float)$a['size'] : PHP_INT_MAX;
        $nb = is_numeric($b['size']) ? (float)$b['size'] : PHP_INT_MAX;
        if ($na !== $nb) return $na <=> $nb;
        return strnatcasecmp($a['size'], $b['size']);
    });
}
unset($st);
uasort($styles, static fn($a, $b) => strcasecmp($a['label'], $b['label']));
$stylesList = array_values($styles);
?>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-truck-ramp-box" aria-hidden="true"></i> New purchase / receive stock</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="<?= BASE_PATH ?>/purchases/create" id="purchaseForm">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Supplier</label>
          <?php $suppliers = $suppliers ?? []; ?>
          <select name="supplier_id" id="poSupplierSelect">
            <option value="">— Select supplier —</option>
            <?php foreach ($suppliers as $sup): ?>
            <option value="<?= (int)$sup['id'] ?>"><?= e($sup['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-muted text-sm" style="margin:.35rem 0 0">
            Or type a one-off name below.
            <a href="<?= BASE_PATH ?>/suppliers">Manage suppliers</a>
          </p>
        </div>
        <div class="form-group">
          <label>Supplier name <span class="text-muted">(optional override)</span></label>
          <input type="text" name="supplier" id="poSupplierName" placeholder="e.g. Accra Footwear Supply" list="supplierSuggestions">
          <datalist id="supplierSuggestions">
            <?php foreach ($suppliers as $sup): ?>
            <option value="<?= e($sup['name']) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <input type="text" name="notes" placeholder="Invoice #, delivery note…">
        </div>
      </div>
      <script>
      document.getElementById('poSupplierSelect')?.addEventListener('change', function () {
        var name = this.options[this.selectedIndex]?.text || '';
        if (this.value && name && name.indexOf('—') !== 0) {
          document.getElementById('poSupplierName').value = name;
        }
      });
      </script>

      <div class="apply-prices-box">
        <p class="text-muted text-sm" style="margin:0 0 .65rem">
          <strong>Find products</strong> — type to search, tick one or more styles, then load their sizes to enter quantities in bulk.
        </p>
        <div class="po-search-bar">
          <div class="po-search-input">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" id="poSearch" placeholder="Search name, design, category, SKU or barcode…" autocomplete="off">
          </div>
          <label class="po-low-toggle">
            <input type="checkbox" id="poLowOnly"> Low stock only
          </label>
        </div>
        <div class="po-search-meta" id="poSearchMeta" hidden>
          <label class="po-select-all">
            <input type="checkbox" id="poSelectAll">
            <span id="poSelectAllLabel">Select all</span>
          </label>
          <span class="flex-center gap-1">
            <span class="text-muted text-sm" id="poSearchCount"></span>
            <button type="button" class="btn btn-ghost btn-xs" id="poClearSel">Clear selection</button>
          </span>
        </div>
        <div class="po-results" id="poResults" role="listbox" aria-multiselectable="true" hidden></div>
        <button type="button" class="btn btn-primary btn-sm" id="poLoadSelected" disabled style="margin-top:.6rem">
          <i class="fa-solid fa-table-list" aria-hidden="true"></i> <span>Load sizes</span>
        </button>

        <div id="poBulkPanel" hidden style="margin-top:.85rem">
          <div class="flex-center gap-1" style="flex-wrap:wrap;margin-bottom:.55rem">
            <label class="text-sm" style="display:inline-flex;align-items:center;gap:.35rem;font-weight:500;text-transform:none;letter-spacing:0;margin:0">
              Same qty for all
              <input type="number" id="poBulkSameQty" min="0" value="0" style="width:4.5rem;margin:0">
            </label>
            <button type="button" class="btn btn-ghost btn-xs" id="poBulkApplyQty">Apply qty</button>
            <label class="text-sm" style="display:inline-flex;align-items:center;gap:.35rem;font-weight:500;text-transform:none;letter-spacing:0;margin:0">
              Same cost for all
              <input type="number" id="poBulkSameCost" step="0.01" min="0" placeholder="0.00" style="width:6rem;margin:0">
            </label>
            <button type="button" class="btn btn-ghost btn-xs" id="poBulkApplyCost">Apply cost</button>
            <button type="button" class="btn btn-ghost btn-xs" id="poBulkClearQty">Clear qtys</button>
          </div>
          <div class="po-filter-row">
            <div class="po-search-input">
              <i class="fa-solid fa-filter" aria-hidden="true"></i>
              <input type="search" id="poBulkFilter" placeholder="Filter loaded sizes by style, size or SKU…" autocomplete="off">
            </div>
            <span class="text-muted text-sm" id="poBulkFilterInfo"></span>
          </div>
          <div class="table-wrap" style="border:1px solid var(--border);border-radius:8px;max-height:380px;overflow:auto;margin-bottom:.65rem">
            <table id="poBulkSizeTable">
              <thead>
                <tr>
                  <th style="width:2.2rem"><input type="checkbox" id="poBulkCheckAll" checked title="Toggle all" style="width:auto"></th>
                  <th>Size</th>
                  <th>SKU</th>
                  <th>Stock</th>
                  <th>Qty</th>
                  <th>Unit cost</th>
                </tr>
              </thead>
              <tbody id="poBulkSizeBody"></tbody>
            </table>
          </div>
          <div class="flex-center gap-1" style="flex-wrap:wrap">
            <button type="button" class="btn btn-primary btn-sm" id="poBulkAddBtn">
              <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Add to order
            </button>
            <button type="button" class="btn btn-ghost btn-sm" id="poBulkClose">Close</button>
          </div>
        </div>
      </div>

      <div class="po-lines-apply" id="poLinesApply" hidden>
        <div class="po-filter-row" style="width:100%;margin:0 0 .35rem">
          <div class="po-search-input">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            <input type="search" id="poLinesFilter" placeholder="Find in this order by product, size or SKU…" autocomplete="off">
          </div>
          <span class="text-muted text-sm" id="poLinesFilterInfo"></span>
          <button type="button" class="btn btn-ghost btn-xs" id="poRemoveShown" hidden>
            <i class="fa-solid fa-trash" aria-hidden="true"></i> Remove shown
          </button>
        </div>
        <span class="po-lines-apply-title" id="poLinesApplyTitle">Apply to all lines</span>
        <label>
          Qty
          <input type="number" id="poAllQty" min="1" placeholder="e.g. 6">
        </label>
        <button type="button" class="btn btn-ghost btn-xs" id="poAllQtyBtn">Apply qty</button>
        <label>
          Unit cost
          <input type="number" id="poAllCost" step="0.01" min="0" placeholder="0.00">
        </label>
        <button type="button" class="btn btn-ghost btn-xs" id="poAllCostBtn">Apply cost</button>
        <button type="button" class="btn btn-primary btn-xs" id="poAllBothBtn">Apply both</button>
      </div>

      <div class="table-wrap" style="border:1px solid var(--border);border-radius:8px;margin:.85rem 0 1rem;overflow-x:auto">
        <table id="poLinesTable">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Unit cost</th>
              <th>Line</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="poLinesBody">
            <tr id="poEmptyRow"><td colspan="5" class="products-empty">No lines yet — add products above.</td></tr>
            <tr id="poNoMatchRow" hidden><td colspan="5" class="products-empty">No lines in this order match your filter.</td></tr>
          </tbody>
          <tfoot id="poLinesFoot" hidden>
            <tr>
              <th id="poTotalLines">0 lines</th>
              <th id="poTotalUnits">0</th>
              <th></th>
              <th id="poTotalCost">GHS 0.00</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <label style="display:flex;align-items:center;gap:.4rem;margin-bottom:1rem;font-weight:500;text-transform:none;letter-spacing:0;font-size:.84rem">
        <input type="checkbox" name="update_cost" value="1" style="width:auto">
        When receiving, update product cost price from unit cost on each line
      </label>

      <div class="flex gap-1 product-form-actions" style="flex-wrap:wrap">
        <button type="submit" name="action" value="receive" class="btn btn-primary" onclick="return confirmReceive()">
          <i class="fa-solid fa-box-open" aria-hidden="true"></i> Receive stock now
        </button>
        <button type="submit" name="action" value="order" class="btn btn-ghost">
          <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Save as ordered
        </button>
        <button type="submit" name="action" value="draft" class="btn btn-ghost">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save draft
        </button>
        <a href="<?= BASE_PATH ?>/purchases" class="btn btn-ghost"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const STYLES = <?= json_encode($stylesList, JSON_UNESCAPED_UNICODE) ?>;
  const MAX_RESULTS = 60;
  const body = document.getElementById('poLinesBody');
  const empty = document.getElementById('poEmptyRow');
  const bulkPanel = document.getElementById('poBulkPanel');
  const bulkBody = document.getElementById('poBulkSizeBody');
  const searchIn = document.getElementById('poSearch');
  const lowOnly = document.getElementById('poLowOnly');
  const resultsEl = document.getElementById('poResults');
  const countEl = document.getElementById('poSearchCount');
  const metaEl = document.getElementById('poSearchMeta');
  const loadBtn = document.getElementById('poLoadSelected');
  const selectAll = document.getElementById('poSelectAll');
  const selectAllLabel = document.getElementById('poSelectAllLabel');
  const selected = new Set();
  let shown = [];
  let matched = [];
  let idx = 0;
  const added = new Set();

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
  }

  function termsOf(value) {
    return value.trim().toLowerCase().split(/\s+/).filter(Boolean);
  }

  const linesFilter = document.getElementById('poLinesFilter');
  const noMatchRow = document.getElementById('poNoMatchRow');
  const removeShownBtn = document.getElementById('poRemoveShown');

  function visibleLines() {
    return [...body.querySelectorAll('tr.po-line')].filter(tr => !tr.hidden);
  }

  function filterLines() {
    const terms = termsOf(linesFilter.value);
    const rows = [...body.querySelectorAll('tr.po-line')];
    let shownN = 0;
    rows.forEach(tr => {
      const ok = terms.every(t => tr.dataset.search.indexOf(t) !== -1);
      tr.hidden = !ok;
      if (ok) shownN++;
    });
    const filtering = terms.length > 0;
    noMatchRow.hidden = !(filtering && rows.length && shownN === 0);
    removeShownBtn.hidden = !(filtering && shownN > 0);
    document.getElementById('poLinesFilterInfo').textContent = filtering
      ? 'Showing ' + shownN + ' of ' + rows.length + ' lines'
      : '';
    document.getElementById('poLinesApplyTitle').textContent = filtering
      ? 'Apply to ' + shownN + ' shown line' + (shownN === 1 ? '' : 's')
      : 'Apply to all lines';
  }

  function refreshEmpty() {
    const has = body.querySelectorAll('tr.po-line').length > 0;
    if (empty) empty.hidden = has;
    if (!has) linesFilter.value = '';
    document.getElementById('poLinesApply').hidden = !has;
    document.getElementById('poLinesFoot').hidden = !has;
    filterLines();
    refreshTotals();
  }

  linesFilter.addEventListener('input', filterLines);

  removeShownBtn.addEventListener('click', () => {
    const rows = visibleLines();
    if (!rows.length) return;
    if (!confirm('Remove ' + rows.length + ' line' + (rows.length === 1 ? '' : 's') + ' from this order?')) return;
    rows.forEach(tr => { added.delete(tr.dataset.pid); tr.remove(); });
    linesFilter.value = '';
    refreshEmpty();
  });

  function lineTotal(tr) {
    const q = parseFloat(tr.querySelector('.po-qty')?.value || 0);
    const c = parseFloat(tr.querySelector('.po-cost')?.value || 0);
    const el = tr.querySelector('.po-line-total');
    if (el) el.textContent = 'GHS ' + (q * c).toFixed(2);
    refreshTotals();
  }

  function refreshTotals() {
    let lines = 0, units = 0, cost = 0;
    body.querySelectorAll('tr.po-line').forEach(tr => {
      const q = parseInt(tr.querySelector('.po-qty')?.value || '0', 10) || 0;
      const c = parseFloat(tr.querySelector('.po-cost')?.value || '0') || 0;
      lines++;
      units += q;
      cost += q * c;
    });
    document.getElementById('poTotalLines').textContent = lines + ' line' + (lines === 1 ? '' : 's');
    document.getElementById('poTotalUnits').textContent = units;
    document.getElementById('poTotalCost').textContent = 'GHS ' + cost.toFixed(2);
  }

  function applyToAllLines(field) {
    const qIn = document.getElementById('poAllQty');
    const cIn = document.getElementById('poAllCost');
    const doQty = field === 'qty' || field === 'both';
    const doCost = field === 'cost' || field === 'both';
    const q = parseInt(qIn.value || '0', 10);
    if (doQty && !(q >= 1)) { alert('Enter a quantity of 1 or more.'); qIn.focus(); return; }
    if (doCost && cIn.value === '') { alert('Enter a unit cost first.'); cIn.focus(); return; }
    visibleLines().forEach(tr => {
      if (doQty) tr.querySelector('.po-qty').value = String(q);
      if (doCost) tr.querySelector('.po-cost').value = cIn.value;
      lineTotal(tr);
    });
  }

  document.getElementById('poAllQtyBtn').addEventListener('click', () => applyToAllLines('qty'));
  document.getElementById('poAllCostBtn').addEventListener('click', () => applyToAllLines('cost'));
  document.getElementById('poAllBothBtn').addEventListener('click', () => applyToAllLines('both'));
  document.getElementById('poLinesApply').addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || !e.target.matches('input')) return;
    e.preventDefault();
    if (e.target === linesFilter) return;
    applyToAllLines(e.target.id === 'poAllQty' ? 'qty' : 'cost');
  });

  function addLine(pid, label, qty, cost) {
    pid = String(pid);
    qty = Math.max(1, parseInt(qty || '1', 10));
    cost = cost !== '' && cost != null ? cost : '0';
    const existing = body.querySelector('tr.po-line[data-pid="' + pid + '"]');
    if (existing) {
      const qEl = existing.querySelector('.po-qty');
      qEl.value = String(parseInt(qEl.value || '0', 10) + qty);
      lineTotal(existing);
      return 'merged';
    }
    const tr = document.createElement('tr');
    tr.className = 'po-line';
    tr.dataset.pid = pid;
    tr.dataset.search = String(label).replace(/·\s*stock\s+-?\d+/i, '').toLowerCase();
    const i = idx++;
    tr.innerHTML =
      '<td><input type="hidden" name="items[' + i + '][product_id]" value="' + pid + '"><strong>' + esc(label) + '</strong></td>' +
      '<td><input type="number" class="po-qty" name="items[' + i + '][quantity]" min="1" value="' + qty + '" style="width:5rem"></td>' +
      '<td><input type="number" class="po-cost" name="items[' + i + '][unit_cost]" step="0.01" min="0" value="' + esc(cost) + '" style="width:7rem"></td>' +
      '<td class="po-line-total text-sm font-bold">GHS 0.00</td>' +
      '<td><button type="button" class="btn btn-ghost btn-xs po-remove" title="Remove"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></td>';
    body.appendChild(tr);
    added.add(pid);
    lineTotal(tr);
    refreshEmpty();
    return 'added';
  }

  function matches(style, terms) {
    if (lowOnly.checked && !style.sizes.some(sz => sz.low)) return false;
    return terms.every(t => style.search.indexOf(t) !== -1);
  }

  function renderResults() {
    const terms = searchIn.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
    const active = terms.length > 0 || lowOnly.checked;
    resultsEl.hidden = !active;
    metaEl.hidden = !active;
    if (!active) {
      shown = [];
      matched = [];
      resultsEl.innerHTML = '';
      updateLoadBtn();
      return;
    }
    const all = [];
    STYLES.forEach((st, i) => { if (matches(st, terms)) all.push(i); });
    matched = all;
    shown = all.slice(0, MAX_RESULTS);

    if (!all.length) {
      resultsEl.innerHTML = '<div class="po-results-empty">No products match “' + esc(searchIn.value.trim()) + '”.</div>';
    } else {
      resultsEl.innerHTML = shown.map(i => {
        const st = STYLES[i];
        const on = selected.has(i);
        const lowN = st.sizes.filter(sz => sz.low).length;
        return '<label class="po-result' + (on ? ' is-selected' : '') + '" role="option" aria-selected="' + on + '">' +
          '<input type="checkbox" class="po-result-check" data-i="' + i + '"' + (on ? ' checked' : '') + '>' +
          '<span class="po-result-main"><strong>' + esc(st.name) + '</strong>' +
            (st.design ? ' <span class="text-muted">· ' + esc(st.design) + '</span>' : '') +
            '<span class="po-result-sub">' + esc(st.gender) + (st.category ? ' · ' + esc(st.category) : '') + '</span></span>' +
          '<span class="po-result-meta">' + st.sizes.length + ' size' + (st.sizes.length === 1 ? '' : 's') +
            ' · stock ' + st.stock +
            (lowN ? ' <span class="badge badge-low">' + lowN + ' low</span>' : '') + '</span>' +
        '</label>';
      }).join('');
    }
    let txt = all.length + ' style' + (all.length === 1 ? '' : 's');
    if (all.length > shown.length) txt += ' · showing first ' + shown.length + ', refine your search';
    countEl.textContent = txt;
    updateLoadBtn();
  }

  function updateLoadBtn() {
    const n = selected.size;
    loadBtn.disabled = n === 0;
    loadBtn.querySelector('span').textContent = n
      ? 'Load sizes for ' + n + ' style' + (n === 1 ? '' : 's')
      : 'Load sizes';
    syncSelectAll();
  }

  function syncSelectAll() {
    const picked = matched.filter(i => selected.has(i)).length;
    selectAll.disabled = matched.length === 0;
    selectAll.checked = matched.length > 0 && picked === matched.length;
    selectAll.indeterminate = picked > 0 && picked < matched.length;
    selectAllLabel.textContent = matched.length > 1
      ? 'Select all ' + matched.length
      : 'Select all';
  }

  function renderBulkSizes(styleIdxs) {
    bulkBody.innerHTML = '';
    styleIdxs.forEach(i => {
      const style = STYLES[i];
      if (!style) return;
      const head = document.createElement('tr');
      head.className = 'po-bulk-group';
      head.innerHTML = '<td colspan="6"><strong>' + esc(style.label) + '</strong>' +
        ' <button type="button" class="btn btn-ghost btn-xs po-bulk-drop" data-i="' + i + '" title="Remove style">&times;</button></td>';
      bulkBody.appendChild(head);
      style.sizes.forEach(sz => {
        const tr = document.createElement('tr');
        tr.className = 'po-bulk-row';
        tr.dataset.style = i;
        tr.dataset.search = (style.label + ' sz ' + sz.size + ' ' + (sz.sku || '')).toLowerCase();
        tr.innerHTML =
          '<td><input type="checkbox" class="po-bulk-check" checked style="width:auto" data-id="' + sz.id + '"></td>' +
          '<td><strong>Sz ' + esc(sz.size) + '</strong></td>' +
          '<td class="mono text-sm">' + esc(sz.sku || '—') + '</td>' +
          '<td class="text-sm">' + (sz.low ? '<span class="badge badge-low">' + sz.stock + '</span>' : sz.stock) + '</td>' +
          '<td><input type="number" class="po-bulk-qty" min="0" value="0" style="width:4.5rem" data-id="' + sz.id + '" data-label="' + esc(sz.label) + '"></td>' +
          '<td><input type="number" class="po-bulk-cost" step="0.01" min="0" value="' + esc(String(sz.cost)) + '" style="width:6.5rem" data-id="' + sz.id + '"></td>';
        bulkBody.appendChild(tr);
      });
    });
    bulkFilter.value = '';
    filterBulk();
  }

  const bulkFilter = document.getElementById('poBulkFilter');
  const bulkCheckAll = document.getElementById('poBulkCheckAll');

  function visibleBulkRows() {
    return [...bulkBody.querySelectorAll('tr.po-bulk-row')].filter(tr => !tr.hidden);
  }

  function syncBulkCheckAll() {
    const rows = visibleBulkRows();
    const on = rows.filter(tr => tr.querySelector('.po-bulk-check').checked).length;
    bulkCheckAll.disabled = rows.length === 0;
    bulkCheckAll.checked = rows.length > 0 && on === rows.length;
    bulkCheckAll.indeterminate = on > 0 && on < rows.length;
  }

  function filterBulk() {
    const terms = termsOf(bulkFilter.value);
    const rows = [...bulkBody.querySelectorAll('tr.po-bulk-row')];
    let shownN = 0;
    rows.forEach(tr => {
      const ok = terms.every(t => tr.dataset.search.indexOf(t) !== -1);
      tr.hidden = !ok;
      if (ok) shownN++;
    });
    bulkBody.querySelectorAll('tr.po-bulk-group').forEach(head => {
      const i = head.querySelector('.po-bulk-drop').dataset.i;
      head.hidden = !rows.some(tr => tr.dataset.style === i && !tr.hidden);
    });
    const info = document.getElementById('poBulkFilterInfo');
    if (!terms.length) info.textContent = '';
    else if (!shownN) info.textContent = 'No sizes match — ticked sizes are still added';
    else info.textContent = 'Showing ' + shownN + ' of ' + rows.length + ' sizes';
    syncBulkCheckAll();
  }

  bulkFilter.addEventListener('input', filterBulk);
  bulkFilter.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });

  function loadSelected() {
    if (!selected.size) return;
    const idxs = STYLES.map((_, i) => i).filter(i => selected.has(i));
    renderBulkSizes(idxs);
    bulkPanel.hidden = false;
    bulkBody.querySelector('.po-bulk-qty')?.focus();
  }

  searchIn.addEventListener('input', renderResults);
  lowOnly.addEventListener('change', renderResults);
  searchIn.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    if (shown.length === 1) selected.add(shown[0]);
    if (selected.size) loadSelected();
  });

  resultsEl.addEventListener('change', (e) => {
    const cb = e.target.closest('.po-result-check');
    if (!cb) return;
    const i = parseInt(cb.dataset.i, 10);
    if (cb.checked) selected.add(i); else selected.delete(i);
    const row = cb.closest('.po-result');
    row.classList.toggle('is-selected', cb.checked);
    row.setAttribute('aria-selected', cb.checked);
    updateLoadBtn();
  });

  selectAll.addEventListener('change', () => {
    if (selectAll.checked) matched.forEach(i => selected.add(i));
    else matched.forEach(i => selected.delete(i));
    renderResults();
  });
  document.getElementById('poClearSel').addEventListener('click', () => {
    selected.clear();
    renderResults();
  });
  loadBtn.addEventListener('click', loadSelected);

  bulkBody.addEventListener('click', (e) => {
    const drop = e.target.closest('.po-bulk-drop');
    if (!drop) return;
    const i = parseInt(drop.dataset.i, 10);
    selected.delete(i);
    bulkBody.querySelectorAll('tr.po-bulk-row[data-style="' + i + '"]').forEach(tr => tr.remove());
    drop.closest('tr').remove();
    if (!bulkBody.querySelector('tr.po-bulk-row')) bulkPanel.hidden = true;
    filterBulk();
    renderResults();
  });

  bulkBody.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || !e.target.matches('input')) return;
    e.preventDefault();
    document.getElementById('poBulkAddBtn').click();
  });

  document.getElementById('poBulkClose').addEventListener('click', () => {
    bulkPanel.hidden = true;
    bulkBody.innerHTML = '';
  });

  bulkCheckAll.addEventListener('change', () => {
    visibleBulkRows().forEach(tr => { tr.querySelector('.po-bulk-check').checked = bulkCheckAll.checked; });
    syncBulkCheckAll();
  });

  bulkBody.addEventListener('change', (e) => {
    if (e.target.matches('.po-bulk-check')) syncBulkCheckAll();
  });

  document.getElementById('poBulkApplyQty').addEventListener('click', () => {
    const q = Math.max(0, parseInt(document.getElementById('poBulkSameQty').value || '0', 10));
    visibleBulkRows().forEach(tr => { tr.querySelector('.po-bulk-qty').value = String(q); });
  });

  document.getElementById('poBulkApplyCost').addEventListener('click', () => {
    const c = document.getElementById('poBulkSameCost').value;
    if (c === '') { alert('Enter a cost first.'); return; }
    visibleBulkRows().forEach(tr => { tr.querySelector('.po-bulk-cost').value = c; });
  });

  document.getElementById('poBulkClearQty').addEventListener('click', () => {
    visibleBulkRows().forEach(tr => { tr.querySelector('.po-bulk-qty').value = '0'; });
  });

  document.getElementById('poBulkAddBtn').addEventListener('click', () => {
    let addedN = 0, mergedN = 0;
    linesFilter.value = '';
    bulkBody.querySelectorAll('tr.po-bulk-row').forEach(tr => {
      const check = tr.querySelector('.po-bulk-check');
      const qtyEl = tr.querySelector('.po-bulk-qty');
      const costEl = tr.querySelector('.po-bulk-cost');
      if (!check || !check.checked) return;
      const qty = parseInt(qtyEl.value || '0', 10);
      if (qty < 1) return;
      const r = addLine(qtyEl.dataset.id, qtyEl.dataset.label, qty, costEl.value);
      if (r === 'merged') mergedN++; else addedN++;
    });
    filterLines();
    if (addedN + mergedN < 1) {
      alert('Enter a quantity (≥ 1) on at least one checked size.');
      return;
    }
    let msg = 'Added ' + addedN + ' size' + (addedN === 1 ? '' : 's');
    if (mergedN) msg += ', updated qty on ' + mergedN + ' existing line' + (mergedN === 1 ? '' : 's');
    bulkBody.querySelectorAll('.po-bulk-qty').forEach(inp => { inp.value = '0'; });
    selected.clear();
    searchIn.value = '';
    renderResults();
    const btn = document.getElementById('poBulkAddBtn');
    const prev = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> ' + msg;
    setTimeout(() => {
      btn.innerHTML = prev;
      bulkPanel.hidden = true;
      bulkBody.innerHTML = '';
      searchIn.focus();
    }, 1400);
  });

  body.addEventListener('input', (e) => {
    const tr = e.target.closest('tr.po-line');
    if (tr) lineTotal(tr);
  });
  body.addEventListener('click', (e) => {
    const btn = e.target.closest('.po-remove');
    if (!btn) return;
    const tr = btn.closest('tr.po-line');
    if (!tr) return;
    added.delete(tr.dataset.pid);
    tr.remove();
    refreshEmpty();
  });

  window.confirmReceive = function () {
    if (!body.querySelector('tr.po-line')) {
      alert('Add at least one product line.');
      return false;
    }
    return confirm('Receive these quantities into stock now?');
  };

  document.getElementById('purchaseForm').addEventListener('submit', (e) => {
    if (!body.querySelector('tr.po-line')) {
      e.preventDefault();
      alert('Add at least one product line.');
    }
  });

  renderResults();
})();
</script>
<?php
$content = ob_get_clean();
require APP_ROOT.'/Views/layouts/main.php';
