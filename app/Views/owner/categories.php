<?php $pageTitle='Categories'; $cp='/categories'; ob_start(); ?>
<?php if($e=flash('error')): ?><div class="alert alert-error"><?= e($e) ?></div><?php endif; ?>
<?php if($s=flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-tag" aria-hidden="true"></i> Product Categories</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th></th></tr></thead>
        <tbody>
          <?php foreach($categories as $c): ?>
          <tr>
            <td class="font-bold"><?= e($c['name']) ?></td>
            <td class="mono text-sm text-muted"><?= e($c['slug']) ?></td>
            <td><?= $c['sort_order'] ?></td>
            <td>
              <form method="POST" action="<?= BASE_PATH ?>/categories/<?= $c['id'] ?>/delete" onsubmit="return confirm('Delete this category?')">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <button class="btn btn-danger btn-xs"><i class="fa-solid fa-trash" aria-hidden="true"></i> Del</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Category</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/categories/create">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="form-group"><label>Category Name *</label><input type="text" name="name" required placeholder="e.g. Sandals"></div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="0" min="0"></div>
        <button type="submit" class="btn btn-primary w-full" style="justify-content:center">Add Category <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
      </form>
    </div>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
