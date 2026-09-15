<?php $pageTitle='Products'; $cp='/products'; ob_start(); ?>
<div class="flex-center gap-2 mb-2" style="flex-wrap:wrap">
  <form method="GET" class="flex-center gap-1" style="flex:1;flex-wrap:wrap">
    <input type="text" name="search" value="<?= e($_GET['search']??'') ?>" placeholder="Search name, SKU, barcode…" style="max-width:220px">
    <select name="category_id" style="max-width:160px">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($filters['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="gender" style="max-width:120px">
      <option value="">All Gender</option>
      <?php foreach(['Boys','Girls','Unisex','Ladies'] as $g): ?>
      <option value="<?= $g ?>" <?= ($filters['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
      <?php endforeach; ?>
    </select>
    <label style="display:flex;align-items:center;gap:.35rem;font-weight:400;text-transform:none;letter-spacing:0;font-size:.875rem">
      <input type="checkbox" name="low_stock" value="1" <?= !empty($filters['low_stock'])?'checked':'' ?> style="width:auto"> Low Stock Only
    </label>
    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a href="<?= BASE_PATH ?>/products" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
  </form>
  <a href="<?= BASE_PATH ?>/products/create" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product</a>
</div>

<!-- Stock value summary (owner only) -->
<div style="display:grid;grid-template-columns:repeat(3,auto);gap:.75rem;margin-bottom:1rem;width:fit-content">
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:.65rem 1rem;font-size:.82rem">
    <div class="text-muted">Total SKUs</div><div class="font-bold"><?= count($products) ?></div>
  </div>
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:.65rem 1rem;font-size:.82rem">
    <div class="text-muted">Retail Value</div><div class="font-bold text-accent"><?= money($stockValue['retail_value']??0) ?></div>
  </div>
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:.65rem 1rem;font-size:.82rem">
    <div class="text-muted">Cost Value</div><div class="font-bold"><?= money($stockValue['cost_value']??0) ?></div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Product</th><th>SKU</th><th>Category</th><th>Gender</th><th>Design</th><th>Size</th>
          <th>Selling Price</th><th>Cost Price</th><th>Margin</th><th>Stock</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="11" style="text-align:center;padding:2rem;color:var(--muted)">No products found.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p):
          $margin = $p['selling_price']>0 ? round(($p['selling_price']-$p['cost_price'])/$p['selling_price']*100) : 0;
          $isLow  = $p['quantity'] <= $p['low_stock_threshold'];
        ?>
        <tr>
          <td style="font-weight:600;font-size:.875rem"><?= e($p['name']) ?></td>
          <td class="mono text-sm"><?= e($p['sku'] ?? '—') ?></td>
          <td><span class="badge badge-<?= str_contains(strtolower($p['category_name']),'school')?'school':(str_contains(strtolower($p['category_name']),'ladies')?'ladies':'preloved') ?>"><?= e($p['category_name']) ?></span></td>
          <td><span class="badge badge-<?= strtolower($p['gender']) ?>"><?= e($p['gender']) ?></span></td>
          <td class="text-sm text-muted"><?= e($p['design']??'—') ?></td>
          <td><strong>Sz <?= e($p['size']) ?></strong></td>
          <td class="font-bold"><?= money($p['selling_price']) ?></td>
          <td class="text-muted text-sm"><?= money($p['cost_price']) ?></td>
          <td style="color:<?= $margin>=40?'var(--accent2)':($margin>=20?'var(--warning)':'var(--danger)') ?>;font-weight:600"><?= $margin ?>%</td>
          <td>
            <span class="badge <?= $isLow?'badge-low':'badge-ok' ?>"><?= $p['quantity'] ?></span>
            <?php if ($isLow): ?><span style="font-size:.65rem;color:var(--danger);display:block">Low!</span><?php endif; ?>
          </td>
          <td>
            <a href="<?= BASE_PATH ?>/products/<?= $p['id'] ?>/edit" class="btn btn-ghost btn-xs"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
            <form method="POST" action="<?= BASE_PATH ?>/products/<?= $p['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Remove this product?')">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <button type="submit" class="btn btn-danger btn-xs"><i class="fa-solid fa-trash" aria-hidden="true"></i> Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
