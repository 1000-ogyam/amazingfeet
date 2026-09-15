<?php $pageTitle='Locations'; $cp='/locations'; ob_start(); ?>
<?php if($s=flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start">
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sale Locations</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Location</th><th>Type</th><th></th></tr></thead>
        <tbody>
          <?php foreach($locations as $l): ?>
          <tr>
            <td class="font-bold"><?= e($l['name']) ?></td>
            <td><span class="badge badge-<?= $l['type']==='shop'?'ok':'school' ?>"><?= ucfirst($l['type']) ?></span></td>
            <td>
              <form method="POST" action="<?= BASE_PATH ?>/locations/<?= $l['id'] ?>/delete" onsubmit="return confirm('Remove this location?')">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <button class="btn btn-danger btn-xs"><i class="fa-solid fa-trash" aria-hidden="true"></i> Remove</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Location</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/locations/create">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="form-group">
          <label>Location Name *</label>
          <input type="text" name="name" required placeholder="e.g. School C, Kumasi Branch">
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="type">
            <option value="shop">Shop</option>
            <option value="school">School activation</option>
            <option value="other">Other</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-full" style="justify-content:center">Add Location <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
      </form>
    </div>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
