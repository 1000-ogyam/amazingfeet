<?php
$pageTitle = 'Edit '.$sale['sale_ref'];
$cp = '/sales';
$backUrl = BASE_PATH.'/sales/'.$sale['id'];
$returnedQty = $returnedQty ?? [];
ob_start();

$lines = [];
foreach ($items as $it) {
    $lines[] = [
        'item_id'  => (int)$it['id'],
        'pid'      => (int)$it['product_id'],
        'label'    => $it['name'].' · Sz '.$it['size'].(!empty($it['sku']) ? ' · '.$it['sku'] : ''),
        'qty'      => (int)$it['quantity'],
        'price'    => (float)$it['unit_price'],
        'returned' => (int)($returnedQty[(int)$it['id']] ?? 0),
    ];
}
$catalog = [];
foreach ($products as $p) {
    $label = $p['name'].' · Sz '.$p['size'].(!empty($p['sku']) ? ' · '.$p['sku'] : '');
    $catalog[] = [
        'id'     => (int)$p['id'],
        'label'  => $label,
        'price'  => (float)$p['selling_price'],
        'stock'  => (int)$p['quantity'],
        'search' => mb_strtolower($label.' '.($p['design'] ?? '').' '.($p['gender'] ?? '').' '.($p['barcode'] ?? '')),
    ];
}
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="POST" action="<?= BASE_PATH ?>/sales/<?= (int)$sale['id'] ?>/edit" id="saleEditForm">
<input type="hidden" name="csrf" value="<?= csrf() ?>">
<div class="sale-edit-grid">
  <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit Sale</h3>
        <span class="mono text-accent text-sm ml-auto"><?= e($sale['sale_ref']) ?></span>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label for="saleWhen">Date &amp; time *</label>
            <input type="datetime-local" name="sale_datetime" id="saleWhen" required
                   value="<?= e(date('Y-m-d\TH:i', strtotime($sale['created_at']))) ?>"
                   max="<?= e(date('Y-m-d\TH:i', strtotime($now))) ?>">
          </div>
          <div class="form-group">
            <label>Location *</label>
            <select name="location_id" required>
              <?php foreach ($locations as $l): ?>
              <option value="<?= $l['id'] ?>" <?= (int)$sale['location_id']===(int)$l['id']?'selected':'' ?>><?= e($l['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Staff *</label>
            <select name="staff_id" required>
              <?php foreach ($staff as $u): ?>
              <option value="<?= $u['id'] ?>" <?= (int)$sale['staff_id']===(int)$u['id']?'selected':'' ?>><?= e($u['name']) ?> (<?= e($u['role']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="custName">Customer name</label>
            <input type="text" name="customer_name" id="custName" value="<?= e($sale['customer_name'] ?? '') ?>" placeholder="e.g. Abena Mensah" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="custPhone">Customer phone</label>
            <input type="tel" name="customer_phone" id="custPhone" value="<?= e($sale['customer_phone'] ?? '') ?>" placeholder="e.g. 0241234567" autocomplete="off">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-box" aria-hidden="true"></i> Items</h3>
        <span class="text-muted text-sm ml-auto">Stock adjusts automatically when you save</span>
      </div>
      <div class="card-body" style="padding-bottom:.5rem">
        <div class="po-search-input" style="margin-bottom:.5rem">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <input type="search" id="itemSearch" placeholder="Add a product — search name, size, SKU or barcode…" autocomplete="off">
        </div>
        <div class="po-results" id="itemResults" hidden></div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Line</th><th></th></tr></thead>
          <tbody id="itemBody"></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-wallet" aria-hidden="true"></i> Payment</h3></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label>Payment Method *</label>
            <select name="payment_method" id="payMethod" required>
              <?php foreach (['cash','momo','card','split'] as $pm): ?>
              <option value="<?= $pm ?>" <?= $sale['payment_method']===$pm?'selected':'' ?>><?= strtoupper($pm) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Discount (GHS)</label>
            <input type="number" name="discount" id="discountInput" step="0.01" min="0" value="<?= e($sale['discount']) ?>">
          </div>
        </div>
        <div class="form-row" id="cashFields">
          <div class="form-group">
            <label>Amount Tendered (GHS)</label>
            <input type="number" name="amount_tendered" id="tenderedInput" step="0.01" min="0" value="<?= e($sale['amount_tendered'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Change Due</label>
            <input type="text" id="changePreview" readonly value="<?= money($sale['change_due'] ?? 0) ?>" style="opacity:.85">
          </div>
        </div>
        <div class="form-group" id="momoField">
          <label>MoMo Reference</label>
          <input type="text" name="momo_ref" value="<?= e($sale['momo_ref'] ?? '') ?>" class="mono" placeholder="Transaction ID">
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea name="notes" rows="3" placeholder="Optional notes"><?= e($sale['notes'] ?? '') ?></textarea>
        </div>
        <div class="flex-center gap-1" style="margin-top:1rem">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes</button>
          <a href="<?= BASE_PATH ?>/sales/<?= (int)$sale['id'] ?>" class="btn btn-ghost">Cancel</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card sale-edit-totals">
    <div class="card-header"><h3><i class="fa-solid fa-calculator" aria-hidden="true"></i> Totals</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:.55rem;font-size:.9rem">
      <div class="flex-center"><span>Items</span><span class="ml-auto" id="unitsVal">0</span></div>
      <div class="flex-center"><span>Subtotal</span><span class="ml-auto" id="subtotalVal"><?= money($sale['subtotal']) ?></span></div>
      <div class="flex-center" style="color:var(--accent2)"><span>Discount</span><span class="ml-auto" id="discountVal">– <?= money($sale['discount']) ?></span></div>
      <div class="flex-center font-bold" style="font-size:1.05rem;border-top:1px solid var(--border);padding-top:.55rem">
        <span>TOTAL</span><span class="ml-auto text-accent" id="totalVal"><?= money($sale['total']) ?></span>
      </div>
      <p class="text-muted text-sm" style="margin:0">
        Was <?= money($sale['total']) ?>. Removing items or lowering quantities puts stock back; adding takes it out.
        Items with returns can't go below the returned quantity.
      </p>
    </div>
  </div>
</div>
</form>

<script>
(function () {
  const CATALOG = <?= json_encode($catalog, $jsonFlags) ?>;
  const body = document.getElementById('itemBody');
  const search = document.getElementById('itemSearch');
  const results = document.getElementById('itemResults');
  const pay = document.getElementById('payMethod');
  let idx = 0;

  const money = n => 'GHS ' + Number(n).toFixed(2);
  const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

  function addLine(l) {
    const existing = !l.item_id && body.querySelector('tr[data-pid="' + l.pid + '"]');
    if (existing) {
      const q = existing.querySelector('.si-qty');
      q.value = String((parseInt(q.value, 10) || 0) + 1);
      recalc();
      return;
    }
    const i = idx++;
    const ret = l.returned || 0;
    const tr = document.createElement('tr');
    tr.dataset.pid = l.pid;
    tr.innerHTML =
      '<td><input type="hidden" name="items[' + i + '][product_id]" value="' + l.pid + '">' +
        (l.item_id ? '<input type="hidden" name="items[' + i + '][item_id]" value="' + l.item_id + '">' : '') +
        '<strong>' + esc(l.label) + '</strong>' +
        (ret ? '<div class="text-muted text-sm">' + ret + ' returned</div>' : '') +
        (!l.item_id ? '<div class="text-muted text-sm">New · ' + l.stock + ' in stock</div>' : '') + '</td>' +
      '<td><input type="number" class="si-qty" name="items[' + i + '][quantity]" min="' + Math.max(1, ret) + '" value="' + l.qty + '" style="width:4.5rem" required></td>' +
      '<td><input type="number" class="si-price" name="items[' + i + '][unit_price]" min="0" step="0.01" value="' + Number(l.price).toFixed(2) + '" style="width:6.5rem" required></td>' +
      '<td class="si-line font-bold text-sm"></td>' +
      '<td>' + (ret
        ? '<button type="button" class="btn btn-ghost btn-xs" disabled title="Has returns — cannot remove"><i class="fa-solid fa-lock" aria-hidden="true"></i></button>'
        : '<button type="button" class="btn btn-ghost btn-xs si-remove" title="Remove"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>') + '</td>';
    body.appendChild(tr);
    recalc();
  }

  function recalc() {
    let sub = 0, units = 0;
    body.querySelectorAll('tr').forEach(tr => {
      const q = parseInt(tr.querySelector('.si-qty').value, 10) || 0;
      const p = parseFloat(tr.querySelector('.si-price').value) || 0;
      tr.querySelector('.si-line').textContent = money(q * p);
      sub += q * p;
      units += q;
    });
    const disc = Math.min(sub, Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0));
    const total = sub - disc;
    document.getElementById('unitsVal').textContent = units;
    document.getElementById('subtotalVal').textContent = money(sub);
    document.getElementById('discountVal').textContent = '– ' + money(disc);
    document.getElementById('totalVal').textContent = money(total);
    const tendered = parseFloat(document.getElementById('tenderedInput').value);
    document.getElementById('changePreview').value = money((!isNaN(tendered) && pay.value === 'cash') ? Math.max(0, tendered - total) : 0);
  }

  function renderResults() {
    const terms = search.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
    if (!terms.length) { results.hidden = true; results.innerHTML = ''; return; }
    const hits = CATALOG.filter(p => terms.every(t => p.search.indexOf(t) !== -1)).slice(0, 15);
    results.hidden = false;
    results.innerHTML = hits.length
      ? hits.map(p => '<button type="button" class="po-result" data-id="' + p.id + '" style="width:100%;text-align:left;border:0">' +
          '<span class="po-result-main"><strong>' + esc(p.label) + '</strong></span>' +
          '<span class="po-result-meta">' + money(p.price) + ' · stock ' + p.stock + '</span></button>').join('')
      : '<div class="po-results-empty">No products match.</div>';
  }

  search.addEventListener('input', renderResults);
  search.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
  results.addEventListener('click', e => {
    const b = e.target.closest('[data-id]');
    if (!b) return;
    const p = CATALOG.find(x => x.id === parseInt(b.dataset.id, 10));
    if (p) addLine({ pid: p.id, label: p.label, qty: 1, price: p.price, stock: p.stock });
    search.value = '';
    renderResults();
    search.focus();
  });

  body.addEventListener('input', recalc);
  body.addEventListener('click', e => {
    const b = e.target.closest('.si-remove');
    if (!b) return;
    if (body.querySelectorAll('tr').length === 1) { alert('A sale needs at least one item. Delete the sale instead.'); return; }
    b.closest('tr').remove();
    recalc();
  });
  document.getElementById('discountInput').addEventListener('input', recalc);
  document.getElementById('tenderedInput').addEventListener('input', recalc);

  function togglePay() {
    document.getElementById('cashFields').style.display = pay.value === 'cash' ? '' : 'none';
    document.getElementById('momoField').style.display = (pay.value === 'momo' || pay.value === 'split') ? '' : 'none';
    recalc();
  }
  pay.addEventListener('change', togglePay);

  <?= json_encode($lines, $jsonFlags) ?>.forEach(addLine);
  togglePay();
})();
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
