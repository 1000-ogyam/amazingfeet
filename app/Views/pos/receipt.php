<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <title>Receipt <?= e($sale['sale_ref']) ?> — Amazing Feet</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="icon" href="<?= e(af_favicon_data_uri()) ?>">
  <?php if (!empty($embed)): ?>
  <style>
    html,body{background:#fff;margin:0}
    body.receipt-embed{padding:.75rem}
    .receipt-wrap{margin:0 auto;box-shadow:none;max-width:420px}
  </style>
  <?php endif; ?>
  <style>
    /* Existing thermal / ticket print format (screen preview + iframe print) */
    @media print {
      @page { margin: 8mm; size: auto; }
      html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .no-print { display: none !important; }
      .receipt-wrap {
        max-width: 420px !important;
        width: 100% !important;
        margin: 0 auto !important;
        padding: 1rem !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #fff !important;
      }
    }
  </style>
</head>
<body class="<?= !empty($embed) ? 'receipt-embed' : '' ?>" style="<?= empty($embed) ? 'background:var(--bg)' : '' ?>">

<?php if (empty($embed)): ?>
<!-- Actions bar (hidden on print) -->
<div class="no-print" style="max-width:420px;margin:1.5rem auto 0;display:flex;gap:.75rem;flex-wrap:wrap">
  <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print" aria-hidden="true"></i> Print Receipt</button>
  <a href="<?= BASE_PATH ?>/pos" class="btn btn-success btn-sm"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> New Sale</a>
  <?php if (isOwner()): ?>
  <a href="<?= BASE_PATH ?>/sales" class="btn btn-ghost btn-sm"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> All Sales <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="receipt-wrap" id="receiptPrintArea">
  <div class="receipt-logo">
    <div class="receipt-logo-ic"><i class="fa-solid fa-shoe-prints fa-2x" aria-hidden="true"></i></div>
    <h2>Amazing Feet</h2>
    <p class="receipt-meta">Accra, Ghana</p>
    <p class="receipt-meta receipt-contact-line" style="margin-top:.35rem"><i class="fa-solid fa-phone" aria-hidden="true"></i> For queries, contact us</p>
    <p class="receipt-meta" style="margin-top:.75rem;font-weight:600;letter-spacing:.05em">SALES RECEIPT</p>
  </div>

  <div style="font-size:.78rem;color:var(--muted);display:grid;grid-template-columns:1fr 1fr;gap:.25rem;margin-bottom:.75rem">
    <div><strong>Ref:</strong> <?= e($sale['sale_ref']) ?></div>
    <div style="text-align:right"><strong>Date:</strong> <?= date('d M Y', strtotime($sale['created_at'])) ?></div>
    <div><strong>Time:</strong> <?= date('H:i', strtotime($sale['created_at'])) ?></div>
    <div style="text-align:right"><strong>Staff:</strong> <?= e($sale['staff_name']) ?></div>
    <div><strong>Location:</strong> <?= e($sale['location_name']) ?></div>
    <div style="text-align:right"><strong>Payment:</strong> <?= strtoupper($sale['payment_method']) ?></div>
    <?php if ($sale['customer_name']): ?>
    <div style="grid-column:1/-1"><strong>Customer:</strong> <?= e($sale['customer_name']) ?> <?= $sale['customer_phone'] ? '· '.e($sale['customer_phone']) : '' ?></div>
    <?php endif; ?>
  </div>

  <div class="receipt-items">
    <div style="display:flex;font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;padding:.25rem 0;border-bottom:1px solid var(--border);margin-bottom:.35rem">
      <span style="flex:1">Item</span><span style="min-width:30px;text-align:center">Qty</span>
      <span style="min-width:65px;text-align:right">Unit</span>
      <span style="min-width:70px;text-align:right">Total</span>
    </div>
    <?php foreach ($items as $item): ?>
    <div class="ri-row">
      <div class="ri-name">
        <?= e($item['name']) ?>
        <?php if ($item['design']): ?> – <?= e($item['design']) ?><?php endif; ?>
        <span style="color:var(--muted)"> (Sz <?= e($item['size']) ?>, <?= e($item['gender']) ?>)</span>
      </div>
      <div class="ri-qty"><?= $item['quantity'] ?></div>
      <div style="min-width:65px;text-align:right;font-size:.85rem"><?= number_format($item['unit_price'], 2) ?></div>
      <div class="ri-total"><?= number_format($item['line_total'], 2) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="receipt-totals">
    <div class="rt-row"><span>Subtotal</span><span>GHS <?= number_format($sale['subtotal'], 2) ?></span></div>
    <?php if ($sale['discount'] > 0): ?>
    <div class="rt-row" style="color:var(--accent2)"><span>Discount</span><span>– GHS <?= number_format($sale['discount'], 2) ?></span></div>
    <?php endif; ?>
    <div class="rt-row grand"><span>TOTAL</span><span>GHS <?= number_format($sale['total'], 2) ?></span></div>
    <?php if ($sale['payment_method'] === 'cash' && $sale['amount_tendered']): ?>
    <div class="rt-row text-muted text-sm"><span>Tendered</span><span>GHS <?= number_format($sale['amount_tendered'], 2) ?></span></div>
    <div class="rt-row text-sm" style="color:var(--accent2)"><span>Change</span><span>GHS <?= number_format($sale['change_due'], 2) ?></span></div>
    <?php endif; ?>
    <?php if ($sale['momo_ref']): ?>
    <div class="rt-row text-sm text-muted"><span>MoMo Ref</span><span><?= e($sale['momo_ref']) ?></span></div>
    <?php endif; ?>
  </div>

  <?php if ($sale['notes']): ?>
  <div style="margin-top:.75rem;font-size:.78rem;color:var(--muted);border-top:1px solid var(--border);padding-top:.5rem">
    <strong>Note:</strong> <?= e($sale['notes']) ?>
  </div>
  <?php endif; ?>

  <div class="receipt-footer">
    <p>Thank you for shopping at Amazing Feet!</p>
    <p style="margin-top:.25rem">All sales are final. Exchange within 7 days with receipt.</p>
    <div style="margin-top:.75rem;font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;opacity:.5"><?= e($sale['sale_ref']) ?></div>
  </div>
</div>

<?php if (empty($embed)): ?>
<script>
// Auto-open print dialog on load (existing behaviour)
window.addEventListener('load', () => {
  setTimeout(() => window.print(), 500);
});
</script>
<?php endif; ?>
</body>
</html>
