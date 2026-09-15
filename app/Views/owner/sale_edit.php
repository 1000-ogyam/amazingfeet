<?php
$pageTitle = 'Edit '.$sale['sale_ref'];
$cp = '/sales';
$backUrl = BASE_PATH.'/sales/'.$sale['id'];
ob_start();
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit Sale</h3>
      <span class="mono text-accent text-sm ml-auto"><?= e($sale['sale_ref']) ?></span>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/sales/<?= (int)$sale['id'] ?>/edit" id="saleEditForm">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <div class="form-row">
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
            <label>Payment Method *</label>
            <select name="payment_method" id="payMethod" required>
              <?php foreach (['cash','momo','card','split'] as $pm): ?>
              <option value="<?= $pm ?>" <?= $sale['payment_method']===$pm?'selected':'' ?>><?= strtoupper($pm) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Discount (GHS)</label>
            <input type="number" name="discount" id="discountInput" step="0.01" min="0"
                   value="<?= e($sale['discount']) ?>" oninput="recalcTotal()">
          </div>
        </div>

        <div class="form-row" id="cashFields">
          <div class="form-group">
            <label>Amount Tendered (GHS)</label>
            <input type="number" name="amount_tendered" id="tenderedInput" step="0.01" min="0"
                   value="<?= e($sale['amount_tendered'] ?? '') ?>" oninput="recalcTotal()">
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
      </form>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:1rem">
    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-calculator" aria-hidden="true"></i> Totals</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:.55rem;font-size:.9rem">
        <div class="flex-center"><span>Subtotal</span><span class="ml-auto" id="subtotalVal"><?= money($sale['subtotal']) ?></span></div>
        <div class="flex-center" style="color:var(--accent2)"><span>Discount</span><span class="ml-auto" id="discountVal">– <?= money($sale['discount']) ?></span></div>
        <div class="flex-center font-bold" style="font-size:1.05rem;border-top:1px solid var(--border);padding-top:.55rem">
          <span>TOTAL</span><span class="ml-auto text-accent" id="totalVal"><?= money($sale['total']) ?></span>
        </div>
        <p class="text-muted text-sm" style="margin:0">Line items are fixed. Change discount to recalculate total. Delete the sale to reverse stock.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-box" aria-hidden="true"></i> Items (<?= count($items) ?>)</h3></div>
      <div class="card-body" style="padding:0">
        <table>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td class="text-sm">
                <div class="font-bold"><?= e($item['name']) ?></div>
                <div class="text-muted" style="font-size:.72rem">Sz <?= e($item['size']) ?> · ×<?= (int)$item['quantity'] ?></div>
              </td>
              <td class="text-sm font-bold" style="text-align:right"><?= money($item['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const subtotal = <?= (float)$sale['subtotal'] ?>;
  const pay = document.getElementById('payMethod');
  const cashFields = document.getElementById('cashFields');
  const momoField = document.getElementById('momoField');

  function moneyFmt(n) {
    return 'GHS ' + Number(n).toFixed(2);
  }

  window.recalcTotal = function () {
    const disc = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
    const total = Math.max(0, subtotal - disc);
    document.getElementById('discountVal').textContent = '– ' + moneyFmt(disc);
    document.getElementById('totalVal').textContent = moneyFmt(total);
    const tendered = parseFloat(document.getElementById('tenderedInput').value);
    const change = (!isNaN(tendered) && pay.value === 'cash') ? Math.max(0, tendered - total) : 0;
    document.getElementById('changePreview').value = moneyFmt(change);
  };

  function togglePay() {
    cashFields.style.display = pay.value === 'cash' ? '' : 'none';
    momoField.style.display = (pay.value === 'momo' || pay.value === 'split') ? '' : 'none';
    recalcTotal();
  }

  pay.addEventListener('change', togglePay);
  togglePay();
})();
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
