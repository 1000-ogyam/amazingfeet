<?php
$pageTitle = 'New SMS Campaign';
$cp = '/sms';
$old = $old ?? [];
$includeCustomers = array_key_exists('include_customers', $old)
    ? !empty($old['include_customers'])
    : true;
ob_start();
?>
<div class="products-toolbar">
  <a href="<?= BASE_PATH ?>/sms" class="btn btn-ghost btn-sm">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
  </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-error"><span class="alert-ic"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span> <?= e($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Compose campaign</h3>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/sms/create" id="smsCampaignForm"
            onsubmit="return confirm('Send this SMS to the selected recipients? Credits will be used on Arkesel.');">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <div class="form-group">
          <label>Title <span class="text-muted">(optional)</span></label>
          <input type="text" name="title" maxlength="150" value="<?= e($old['title'] ?? '') ?>"
                 placeholder="e.g. Weekend sale">
        </div>

        <div class="form-group">
          <label>Message *</label>
          <textarea name="message" id="smsMessage" rows="5" required maxlength="1000"
                    placeholder="Hi! Amazing Feet — this weekend only…"><?= e($old['message'] ?? '') ?></textarea>
          <div class="flex-center gap-2" style="justify-content:space-between;margin-top:.35rem">
            <span class="text-muted text-sm">~160 chars ≈ 1 SMS segment (longer messages use more credits)</span>
            <span class="mono text-sm" id="smsCharCount">0</span>
          </div>
        </div>

        <div class="form-group">
          <label>Audience</label>
          <label class="flex-center gap-1" style="font-weight:500;cursor:pointer;margin-bottom:.5rem">
            <input type="checkbox" name="include_customers" value="1" id="includeCustomers"
                   <?= $includeCustomers ? 'checked' : '' ?>>
            All customers with a phone
            <span class="text-muted text-sm">(<?= (int)$customerPhoneCount ?>)</span>
          </label>
          <label style="display:block;margin-bottom:.35rem">Custom numbers</label>
          <textarea name="custom_numbers" id="customNumbers" rows="4"
                    placeholder="One per line, or comma-separated&#10;0244123456&#10;+233241234567"><?= e($old['custom_numbers'] ?? '') ?></textarea>
          <p class="text-muted text-sm" style="margin:.35rem 0 0">
            Local Ghana numbers (0…) are converted to 233… automatically. Duplicates are removed.
          </p>
        </div>

        <div class="flex-center gap-2" style="flex-wrap:wrap;margin-top:1rem">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send SMS
          </button>
          <a href="<?= BASE_PATH ?>/sms" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sender</h3></div>
    <div class="card-body">
      <p class="text-sm" style="margin:0 0 .75rem">
        Messages send as <strong class="mono"><?= e(ARKESEL_SENDER) ?></strong>
        via Arkesel.
      </p>
      <?php if (!empty($balance['ok']) && $balance['balance'] !== null): ?>
      <p class="text-sm" style="margin:0">
        SMS balance: <strong class="mono"><?= e($balance['balance']) ?></strong>
      </p>
      <?php elseif (!empty($balance['error'])): ?>
      <p class="text-muted text-sm" style="margin:0">
        Balance unavailable: <?= e($balance['error']) ?>
      </p>
      <?php endif; ?>
      <p class="text-muted text-sm" style="margin:.75rem 0 0">
        Set <span class="mono">ARKESEL_API_KEY</span> and <span class="mono">ARKESEL_SENDER</span>
        in <span class="mono">config/local.php</span>.
      </p>
    </div>
  </div>
</div>

<script>
(function () {
  var ta = document.getElementById('smsMessage');
  var cnt = document.getElementById('smsCharCount');
  function update() {
    var n = (ta.value || '').length;
    var segs = n === 0 ? 0 : (n <= 160 ? 1 : Math.ceil(n / 153));
    cnt.textContent = n + ' chars · ~' + segs + ' segment' + (segs === 1 ? '' : 's');
  }
  ta.addEventListener('input', update);
  update();
})();
</script>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
