<?php
$pageTitle = 'Settings';
$cp = '/settings';
ob_start();
?>
<form method="POST" action="<?= BASE_PATH ?>/settings" class="settings-grid">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">

  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-comment-sms" aria-hidden="true"></i> SMS</h3>
      <?php if ($smsConfigured): ?>
      <span class="badge badge-ok">Arkesel connected · <?= e(defined('ARKESEL_SENDER') ? ARKESEL_SENDER : '') ?></span>
      <?php else: ?>
      <span class="badge badge-low">Arkesel not configured</span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <label class="setting-row">
        <span class="setting-switch">
          <input type="checkbox" name="sms_enabled" value="1" id="smsEnabled" <?= $smsEnabled ? 'checked' : '' ?>>
          <span class="setting-slider" aria-hidden="true"></span>
        </span>
        <span>
          <strong>Enable SMS</strong>
          <span class="text-muted text-sm setting-desc">Master switch. When off, the system sends no SMS at all.</span>
        </span>
      </label>

      <div class="setting-children" id="smsChildren">
        <label class="setting-row">
          <span class="setting-switch">
            <input type="checkbox" name="sms_receipts" value="1" <?= $smsReceipts ? 'checked' : '' ?>>
            <span class="setting-slider" aria-hidden="true"></span>
          </span>
          <span>
            <strong>Receipt SMS after each sale</strong>
            <span class="text-muted text-sm setting-desc">Texts the customer a receipt when a POS sale is completed.</span>
          </span>
        </label>
        <label class="setting-row">
          <span class="setting-switch">
            <input type="checkbox" name="sms_campaigns" value="1" <?= $smsCampaigns ? 'checked' : '' ?>>
            <span class="setting-slider" aria-hidden="true"></span>
          </span>
          <span>
            <strong>SMS campaigns</strong>
            <span class="text-muted text-sm setting-desc">Allows sending bulk messages from SMS Campaigns.</span>
          </span>
        </label>
      </div>

      <?php if (!$smsConfigured): ?>
      <p class="text-muted text-sm" style="margin:1rem 0 0">
        Messages can't be delivered until <span class="mono">ARKESEL_API_KEY</span> is set in the server config.
      </p>
      <?php endif; ?>

      <div style="margin-top:1.25rem">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save settings</button>
      </div>
    </div>
  </div>
</form>

<script>
(function () {
  const master = document.getElementById('smsEnabled');
  const children = document.getElementById('smsChildren');
  function sync() { children.classList.toggle('is-off', !master.checked); }
  master.addEventListener('change', sync);
  sync();
})();
</script>
<?php
$content = ob_get_clean();
require APP_ROOT . '/Views/layouts/main.php';
