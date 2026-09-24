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
            'key'    => $key,
            'name'   => $p['name'],
            'gender' => $p['gender'] ?? '',
            'design' => $p['design'] ?? '',
            'label'  => $p['name']
                . (!empty($p['design']) ? ' · '.$p['design'] : '')
                . ' · '.($p['gender'] ?? ''),
            'sizes'  => [],
        ];
    }
    $styles[$key]['sizes'][] = [
        'id'    => (int)$p['id'],
        'size'  => (string)$p['size'],
        'sku'   => $p['sku'] ?? '',
        'cost'  => (float)$p['cost_price'],
        'stock' => (int)$p['quantity'],
        'label' => $p['name'].' · Sz '.$p['size']
            . (!empty($p['sku']) ? ' · '.$p['sku'] : '')
            . ' · stock '.(int)$p['quantity'],
    ];
}
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

      <!-- Bulk: pick style → fill many sizes -->
      <div class="apply-prices-box">
        <p class="text-muted text-sm" style="margin:0 0 .65rem">
          <strong>Bulk add by product</strong> — choose a style, enter qty for each size, then add them all at once.
        </p>
        <div class="form-group" style="margin-bottom:.65rem">
          <label>Product style</label>
          <select id="poStyleSelect">
            <option value="">— Select product style —</option>
            <?php foreach ($stylesList as $i => $st): ?>
            <option value="<?= (int)$i ?>"><?= e($st['label']) ?> (<?= count($st['sizes']) ?> sizes)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="poBulkPanel" hidden>
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
          <div class="table-wrap" style="border:1px solid var(--border);border-radius:8px;max-height:280px;overflow:auto;margin-bottom:.65rem">
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
          <button type="button" class="btn btn-primary btn-sm" id="poBulkAddBtn">
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Add selected sizes to order
          </button>
        </div>
      </div>

      <!-- Single line (optional) -->
      <details class="po-single-add" style="margin:.85rem 0">
        <summary class="text-sm text-muted" style="cursor:pointer;font-weight:600">Or add one size at a time</summary>
        <div class="form-row" style="margin-top:.65rem;align-items:end">
          <div class="form-group" style="margin-bottom:0;flex:2">
            <label>Product / size</label>
            <select id="poProductSelect">
              <option value="">— Select size variant —</option>
              <?php foreach ($stylesList as $st): foreach ($st['sizes'] as $sz): ?>
              <option value="<?= (int)$sz['id'] ?>"
                      data-cost="<?= e((string)$sz['cost']) ?>"
                      data-label="<?= e($sz['label']) ?>">
                <?= e($sz['label']) ?>
              </option>
              <?php endforeach; endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Qty</label>
            <input type="number" id="poQty" min="1" value="1" style="width:5.5rem">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>Unit cost</label>
            <input type="number" id="poCost" step="0.01" min="0" placeholder="0.00" style="width:7rem">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <button type="button" class="btn btn-ghost btn-sm" id="poAddLine"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add line</button>
          </div>
        </div>
      </details>

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
          </tbody>
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
  const body = document.getElementById('poLinesBody');
  const empty = document.getElementById('poEmptyRow');
  const sel = document.getElementById('poProductSelect');
  const qtyIn = document.getElementById('poQty');
  const costIn = document.getElementById('poCost');
  const styleSel = document.getElementById('poStyleSelect');
  const bulkPanel = document.getElementById('poBulkPanel');
  const bulkBody = document.getElementById('poBulkSizeBody');
  let idx = 0;
  const added = new Set();

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
  }

  function refreshEmpty() {
    const has = body.querySelectorAll('tr.po-line').length > 0;
    if (empty) empty.hidden = has;
  }

  function lineTotal(tr) {
    const q = parseFloat(tr.querySelector('.po-qty')?.value || 0);
    const c = parseFloat(tr.querySelector('.po-cost')?.value || 0);
    const el = tr.querySelector('.po-line-total');
    if (el) el.textContent = 'GHS ' + (q * c).toFixed(2);
  }

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

  function renderBulkSizes(style) {
    bulkBody.innerHTML = '';
    (style.sizes || []).forEach(sz => {
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="checkbox" class="po-bulk-check" checked style="width:auto" data-id="' + sz.id + '"></td>' +
        '<td><strong>Sz ' + esc(sz.size) + '</strong></td>' +
        '<td class="mono text-sm">' + esc(sz.sku || '—') + '</td>' +
        '<td class="text-sm">' + sz.stock + '</td>' +
        '<td><input type="number" class="po-bulk-qty" min="0" value="0" style="width:4.5rem" data-id="' + sz.id + '" data-label="' + esc(sz.label) + '"></td>' +
        '<td><input type="number" class="po-bulk-cost" step="0.01" min="0" value="' + esc(String(sz.cost)) + '" style="width:6.5rem" data-id="' + sz.id + '"></td>';
      bulkBody.appendChild(tr);
    });
    document.getElementById('poBulkCheckAll').checked = true;
  }

  styleSel.addEventListener('change', () => {
    const i = styleSel.value;
    if (i === '') {
      bulkPanel.hidden = true;
      bulkBody.innerHTML = '';
      return;
    }
    const style = STYLES[parseInt(i, 10)];
    if (!style) return;
    renderBulkSizes(style);
    bulkPanel.hidden = false;
  });

  document.getElementById('poBulkCheckAll').addEventListener('change', (e) => {
    bulkBody.querySelectorAll('.po-bulk-check').forEach(c => { c.checked = e.target.checked; });
  });

  document.getElementById('poBulkApplyQty').addEventListener('click', () => {
    const q = Math.max(0, parseInt(document.getElementById('poBulkSameQty').value || '0', 10));
    bulkBody.querySelectorAll('.po-bulk-qty').forEach(inp => { inp.value = String(q); });
  });

  document.getElementById('poBulkApplyCost').addEventListener('click', () => {
    const c = document.getElementById('poBulkSameCost').value;
    if (c === '') { alert('Enter a cost first.'); return; }
    bulkBody.querySelectorAll('.po-bulk-cost').forEach(inp => { inp.value = c; });
  });

  document.getElementById('poBulkClearQty').addEventListener('click', () => {
    bulkBody.querySelectorAll('.po-bulk-qty').forEach(inp => { inp.value = '0'; });
  });

  document.getElementById('poBulkAddBtn').addEventListener('click', () => {
    let addedN = 0, mergedN = 0, skipped = 0;
    bulkBody.querySelectorAll('tr').forEach(tr => {
      const check = tr.querySelector('.po-bulk-check');
      const qtyEl = tr.querySelector('.po-bulk-qty');
      const costEl = tr.querySelector('.po-bulk-cost');
      if (!check || !check.checked) { skipped++; return; }
      const qty = parseInt(qtyEl.value || '0', 10);
      if (qty < 1) return;
      const r = addLine(qtyEl.dataset.id, qtyEl.dataset.label, qty, costEl.value);
      if (r === 'merged') mergedN++; else addedN++;
    });
    if (addedN + mergedN < 1) {
      alert('Enter a quantity (≥ 1) on at least one checked size.');
      return;
    }
    let msg = 'Added ' + addedN + ' size' + (addedN === 1 ? '' : 's');
    if (mergedN) msg += ', updated qty on ' + mergedN + ' existing line' + (mergedN === 1 ? '' : 's');
    // brief non-blocking feedback
    const btn = document.getElementById('poBulkAddBtn');
    const prev = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> ' + msg;
    setTimeout(() => { btn.innerHTML = prev; }, 1800);
  });

  sel.addEventListener('change', () => {
    const opt = sel.selectedOptions[0];
    if (!opt || !opt.value) return;
    costIn.value = opt.dataset.cost || '';
  });

  document.getElementById('poAddLine').addEventListener('click', () => {
    const opt = sel.selectedOptions[0];
    const pid = opt?.value;
    if (!pid) { alert('Select a product size first.'); return; }
    const qty = Math.max(1, parseInt(qtyIn.value || '1', 10));
    const cost = costIn.value !== '' ? costIn.value : (opt.dataset.cost || '0');
    const label = opt.dataset.label || opt.textContent.trim();
    addLine(pid, label, qty, cost);
    sel.value = '';
    qtyIn.value = '1';
    costIn.value = '';
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
})();
</script>
<?php
$content = ob_get_clean();
require APP_ROOT.'/Views/layouts/main.php';
