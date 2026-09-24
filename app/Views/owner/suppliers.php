<?php $pageTitle = 'Suppliers'; $cp = '/suppliers'; ob_start(); ?>
<div class="flex-center gap-2 mb-2" style="flex-wrap:wrap">
  <form method="GET" class="flex-center gap-1" style="flex:1">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, phone, email…" style="max-width:260px">
    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
    <?php if ($search): ?><a href="<?= BASE_PATH ?>/suppliers" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</a><?php endif; ?>
  </form>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-truck" aria-hidden="true"></i> Suppliers <span class="text-muted text-sm">(<?= count($suppliers) ?>)</span></h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Notes</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($suppliers)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No suppliers yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($suppliers as $s): ?>
          <tr style="<?= !(int)$s['is_active'] ? 'opacity:.55' : '' ?>">
            <td class="font-bold"><?= e($s['name']) ?></td>
            <td class="mono text-sm"><?= e($s['phone'] ?? '—') ?></td>
            <td class="text-sm"><?= e($s['email'] ?? '—') ?></td>
            <td class="text-muted text-sm"><?= e(substr($s['notes'] ?? '', 0, 40)) ?></td>
            <td><?= (int)$s['is_active'] ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-low">Inactive</span>' ?></td>
            <td>
              <?php if ((int)$s['is_active']): ?>
              <form method="POST" action="<?= BASE_PATH ?>/suppliers/<?= (int)$s['id'] ?>/delete" style="display:inline"
                    onsubmit="return confirm('Deactivate this supplier?')">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Deactivate</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Supplier</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/suppliers/create">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="form-group"><label>Name *</label><input type="text" name="name" required placeholder="e.g. Accra Footwear Supply"></div>
        <div class="form-group"><label>Phone</label><input type="tel" name="phone" placeholder="+233…"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="orders@…"></div>
        <div class="form-group"><label>Notes</label><textarea name="notes" placeholder="Lead time, contacts…" style="min-height:60px"></textarea></div>
        <button type="submit" class="btn btn-primary w-full" style="justify-content:center">Add Supplier <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
