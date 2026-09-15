<?php $pageTitle='Staff Management'; $cp='/staff'; ob_start(); ?>
<div class="flex-center gap-2 mb-2">
  <h2 style="flex:1;font-size:1.25rem;display:flex;align-items:center;gap:.5rem"><i class="fa-solid fa-users" aria-hidden="true"></i> Staff</h2>
  <a href="<?= BASE_PATH ?>/staff/create" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Staff</a>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Today's Sales</th><th>This Week</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php if(empty($staff)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No staff added yet.</td></tr><?php endif; ?>
        <?php foreach($staff as $s): ?>
        <tr>
          <td class="font-bold"><?= e($s['name']) ?></td>
          <td class="text-sm text-muted"><?= e($s['email']) ?></td>
          <td class="text-sm"><?= e($s['phone']??'—') ?></td>
          <td class="font-bold"><?= $s['today_sales'] ?></td>
          <td><?= $s['week_sales'] ?></td>
          <td><span class="badge <?= $s['is_active']?'badge-ok':'badge-low' ?>"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
          <td>
            <form method="POST" action="<?= BASE_PATH ?>/staff/<?= $s['id'] ?>/toggle">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <button class="btn btn-ghost btn-xs"><?= $s['is_active'] ? '<i class="fa-solid fa-ban" aria-hidden="true"></i> Deactivate' : '<i class="fa-solid fa-check" aria-hidden="true"></i> Activate' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
