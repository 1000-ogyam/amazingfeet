<?php $pageTitle='Customer Database'; $cp='/customers'; ob_start(); ?>
<div class="flex-center gap-2 mb-2" style="flex-wrap:wrap">
  <form method="GET" class="flex-center gap-1" style="flex:1">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name or phone…" style="max-width:260px">
    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
    <?php if($search): ?><a href="<?= BASE_PATH ?>/customers" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear</a><?php endif; ?>
  </form>
  <a href="<?= BASE_PATH ?>/customers/export" class="btn btn-ghost btn-sm"><i class="fa-solid fa-download" aria-hidden="true"></i> Export CSV</a>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-users" aria-hidden="true"></i> Customers <span class="text-muted text-sm">(<?= count($customers) ?>)</span></h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Phone</th><th>Shoe Size</th><th>Notes</th><th>Added</th></tr></thead>
        <tbody>
          <?php if(empty($customers)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--muted)">No customers yet.</td></tr><?php endif; ?>
          <?php foreach($customers as $c): ?>
          <tr>
            <td class="font-bold"><?= e($c['name']) ?></td>
            <td class="mono text-sm"><?= e($c['phone']??'—') ?></td>
            <td><?= $c['shoe_size']?'Sz '.e($c['shoe_size']):'—' ?></td>
            <td class="text-muted text-sm"><?= e(substr($c['notes']??'',0,40)) ?></td>
            <td class="text-muted text-sm"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add customer -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Customer</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/customers/create">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="form-group"><label>Name *</label><input type="text" name="name" required placeholder="e.g. Akosua Mensah"></div>
        <div class="form-group"><label>Phone</label><input type="tel" name="phone" placeholder="+233…"></div>
        <div class="form-group"><label>Shoe Size</label><input type="text" name="shoe_size" placeholder="e.g. 32"></div>
        <div class="form-group"><label>Notes</label><textarea name="notes" placeholder="Any additional info…" style="min-height:60px"></textarea></div>
        <button type="submit" class="btn btn-primary w-full" style="justify-content:center">Add Customer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
      </form>
    </div>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
