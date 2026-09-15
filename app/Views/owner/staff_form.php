<?php $pageTitle='Add Staff'; $cp='/staff'; $backUrl=BASE_PATH.'/staff'; ob_start(); ?>
<?php if($error=$error??''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:480px">
  <div class="card-header"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Staff Member</h3></div>
  <div class="card-body">
    <form method="POST" action="<?= BASE_PATH ?>/staff/create">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="form-group"><label>Full Name *</label><input type="text" name="name" required placeholder="e.g. Abena Asante"></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="staff@amazingfeet.com"></div>
      <div class="form-group"><label>Phone</label><input type="tel" name="phone" placeholder="+233…"></div>
      <div class="form-row">
        <div class="form-group"><label>Password *</label><input type="password" name="password" required minlength="6" placeholder="Min 6 chars"></div>
        <div class="form-group"><label>POS PIN (4 digits)</label><input type="text" name="pin" maxlength="4" pattern="\d{4}" placeholder="1234"></div>
      </div>
      <div class="flex gap-1 mt-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Create Staff</button>
        <a href="<?= BASE_PATH ?>/staff" class="btn btn-ghost"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
