<?php
$pageTitle = 'Sale '.$sale['sale_ref'];
$cp = '/sales'; $backUrl = BASE_PATH.'/sales';
ob_start();
$totalCost   = array_sum(array_map(fn($i)=>$i['cost_price']*$i['quantity'], $items));
$grossProfit = $sale['total'] - $totalCost;
$margin      = $sale['total']>0 ? round($grossProfit/$sale['total']*100) : 0;
?>
<div style="display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start">

  <div style="display:flex;flex-direction:column;gap:1.25rem">
    <div class="card">
      <div class="card-header" style="flex-wrap:wrap;gap:.5rem">
        <h3><i class="fa-solid fa-receipt" aria-hidden="true"></i> <?= e($sale['sale_ref']) ?></h3>
        <span class="badge badge-<?= $sale['payment_method'] ?>"><?= strtoupper($sale['payment_method']) ?></span>
        <div class="ml-auto flex-center gap-1 no-print">
          <a href="<?= BASE_PATH ?>/sales/<?= $sale['id'] ?>/edit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
          <a href="<?= BASE_PATH ?>/pos/receipt/<?= $sale['id'] ?>" target="_blank" class="btn btn-ghost btn-sm"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</a>
          <form method="POST" action="<?= BASE_PATH ?>/sales/<?= $sale['id'] ?>/delete" style="display:inline"
                onsubmit="return confirm('Delete this sale and restore stock?')">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
          </form>
        </div>
      </div>
      <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:1rem">
        <div><p class="text-muted text-sm">Date</p><p class="font-bold"><?= date('d M Y', strtotime($sale['created_at'])) ?></p></div>
        <div><p class="text-muted text-sm">Time</p><p class="font-bold"><?= date('H:i', strtotime($sale['created_at'])) ?></p></div>
        <div><p class="text-muted text-sm">Staff</p><p class="font-bold"><?= e($sale['staff_name']) ?></p></div>
        <div><p class="text-muted text-sm">Location</p><p class="font-bold"><?= e($sale['location_name']) ?></p></div>
        <?php if($sale['customer_name']): ?>
        <div><p class="text-muted text-sm">Customer</p><p class="font-bold"><?= e($sale['customer_name']) ?></p></div>
        <div><p class="text-muted text-sm">Phone</p><p class="font-bold"><?= e($sale['customer_phone']??'—') ?></p></div>
        <?php endif; ?>
        <?php if($sale['momo_ref']): ?>
        <div><p class="text-muted text-sm">MoMo Ref</p><p class="font-bold mono"><?= e($sale['momo_ref']) ?></p></div>
        <?php endif; ?>
        <?php if($sale['notes']): ?>
        <div style="grid-column:1/-1"><p class="text-muted text-sm">Notes</p><p><?= e($sale['notes']) ?></p></div>
        <?php endif; ?>
      </div>

      <!-- Line items -->
      <div style="border-top:1px solid var(--border)">
        <table>
          <thead><tr><th>Product</th><th>Size</th><th>Qty</th><th>Unit Price</th><th>Cost</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach($items as $item): ?>
            <tr>
              <td>
                <div class="font-bold text-sm"><?= e($item['name']) ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= e($item['gender']) ?><?= $item['design']?' · '.e($item['design']):'' ?></div>
              </td>
              <td><strong>Sz <?= e($item['size']) ?></strong></td>
              <td><?= $item['quantity'] ?></td>
              <td><?= money($item['unit_price']) ?></td>
              <td class="text-muted text-sm"><?= money($item['cost_price']) ?></td>
              <td class="font-bold"><?= money($item['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Totals sidebar -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-wallet" aria-hidden="true"></i> Financials</h3></div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:.6rem;font-size:.9rem">
        <div class="flex-center"><span>Subtotal</span><span class="ml-auto"><?= money($sale['subtotal']) ?></span></div>
        <?php if($sale['discount']>0): ?>
        <div class="flex-center" style="color:var(--accent2)"><span>Discount</span><span class="ml-auto">– <?= money($sale['discount']) ?></span></div>
        <?php endif; ?>
        <div class="flex-center font-bold" style="font-size:1.1rem;border-top:1px solid var(--border);padding-top:.6rem;margin-top:.2rem">
          <span>TOTAL</span><span class="ml-auto text-accent"><?= money($sale['total']) ?></span>
        </div>
        <?php if($sale['payment_method']==='cash' && $sale['amount_tendered']): ?>
        <div class="flex-center text-sm text-muted"><span>Tendered</span><span class="ml-auto"><?= money($sale['amount_tendered']) ?></span></div>
        <div class="flex-center text-sm" style="color:var(--accent2)"><span>Change</span><span class="ml-auto"><?= money($sale['change_due']) ?></span></div>
        <?php endif; ?>

        <!-- Owner-only profit -->
        <div style="border-top:2px dashed var(--border);padding-top:.75rem;margin-top:.35rem">
          <p class="text-muted text-sm mb-1">Owner View</p>
          <div class="flex-center text-sm"><span>Cost of Goods</span><span class="ml-auto text-muted"><?= money($totalCost) ?></span></div>
          <div class="flex-center font-bold" style="color:var(--accent2)"><span>Gross Profit</span><span class="ml-auto"><?= money($grossProfit) ?></span></div>
          <div class="flex-center text-sm text-muted"><span>Margin</span><span class="ml-auto"><?= $margin ?>%</span></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
