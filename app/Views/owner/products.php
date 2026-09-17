<?php $pageTitle='Products'; $cp='/products'; ob_start(); ?>
<div class="products-toolbar">
  <form method="GET" class="products-filters">
    <input type="text" name="search" value="<?= e($_GET['search']??'') ?>" placeholder="Search name, SKU, barcode…">
    <select name="category_id">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($filters['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="gender">
      <option value="">All Gender</option>
      <?php foreach(['Boys','Girls','Unisex','Ladies'] as $g): ?>
      <option value="<?= $g ?>" <?= ($filters['gender']??'')===$g?'selected':'' ?>><?= $g ?></option>
      <?php endforeach; ?>
    </select>
    <label class="products-low-stock">
      <input type="checkbox" name="low_stock" value="1" <?= !empty($filters['low_stock'])?'checked':'' ?>> Low Stock
    </label>
    <div class="products-filter-actions">
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
      <a href="<?= BASE_PATH ?>/products" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
    </div>
  </form>
  <a href="<?= BASE_PATH ?>/products/create" class="btn btn-primary products-add-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product</a>
</div>

<div class="products-stats">
  <div class="products-stat">
    <div class="text-muted">Products</div><div class="font-bold"><?= count($products) ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Size variants</div><div class="font-bold"><?= (int)($variantCount ?? 0) ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Retail Value</div><div class="font-bold text-accent"><?= money($stockValue['retail_value']??0) ?></div>
  </div>
  <div class="products-stat">
    <div class="text-muted">Cost Value</div><div class="font-bold"><?= money($stockValue['cost_value']??0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-box" aria-hidden="true"></i> Products</h3>
    <span class="text-muted text-sm ml-auto hide-sm">Open a product to manage sizes</span>
  </div>

  <!-- Desktop table -->
  <div class="table-wrap products-table-desktop">
    <table>
      <thead>
        <tr>
          <th>Product</th><th>SKU</th><th>Category</th><th>Gender</th><th>Design</th>
          <th>Sizes</th><th>Price</th><th>Stock</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="9" class="products-empty">No products found.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p):
          $sizeCount = (int)($p['size_count'] ?? 1);
          $isLow = (int)($p['low_size_count'] ?? 0) > 0;
          $pmin = (float)($p['price_min'] ?? 0);
          $pmax = (float)($p['price_max'] ?? 0);
          $priceLabel = ($pmin === $pmax) ? money($pmin) : money($pmin).' – '.money($pmax);
          $catSlug = str_contains(strtolower($p['category_name']),'school')?'school':(str_contains(strtolower($p['category_name']),'ladies')?'ladies':'preloved');
        ?>
        <tr>
          <td class="products-name-cell"><?= e($p['name']) ?></td>
          <td class="mono text-sm"><?= e($p['sku'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $catSlug ?>"><?= e($p['category_name']) ?></span></td>
          <td><span class="badge badge-<?= strtolower($p['gender']) ?>"><?= e($p['gender']) ?></span></td>
          <td class="text-sm text-muted"><?= e($p['design']!==''?$p['design']:'—') ?></td>
          <td>
            <strong><?= $sizeCount ?></strong>
            <span class="text-muted text-sm">size<?= $sizeCount===1?'':'s' ?></span>
          </td>
          <td class="font-bold text-sm"><?= $priceLabel ?></td>
          <td>
            <span class="badge <?= $isLow?'badge-low':'badge-ok' ?>"><?= (int)$p['quantity'] ?></span>
            <?php if ($isLow): ?><span class="products-low-hint"><?= (int)$p['low_size_count'] ?> low</span><?php endif; ?>
          </td>
          <td class="products-actions">
            <a href="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/edit" class="btn btn-ghost btn-xs" title="View & edit sizes"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a>
            <a href="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/edit" class="btn btn-ghost btn-xs"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
            <form method="POST" action="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/delete" class="products-del-form"
                  onsubmit="return confirm('Remove this product and all <?= $sizeCount ?> size<?= $sizeCount===1?'':'s' ?>?')">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <button type="submit" class="btn btn-danger btn-xs"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile cards -->
  <div class="products-cards-mobile">
    <?php if (empty($products)): ?>
    <div class="products-empty">No products found.</div>
    <?php endif; ?>
    <?php foreach ($products as $p):
      $sizeCount = (int)($p['size_count'] ?? 1);
      $isLow = (int)($p['low_size_count'] ?? 0) > 0;
      $pmin = (float)($p['price_min'] ?? 0);
      $pmax = (float)($p['price_max'] ?? 0);
      $priceLabel = ($pmin === $pmax) ? money($pmin) : money($pmin).' – '.money($pmax);
      $catSlug = str_contains(strtolower($p['category_name']),'school')?'school':(str_contains(strtolower($p['category_name']),'ladies')?'ladies':'preloved');
    ?>
    <article class="product-card-m">
      <div class="product-card-m-top">
        <div>
          <h4 class="product-card-m-name"><?= e($p['name']) ?></h4>
          <div class="product-card-m-meta">
            <span class="badge badge-<?= $catSlug ?>"><?= e($p['category_name']) ?></span>
            <span class="badge badge-<?= strtolower($p['gender']) ?>"><?= e($p['gender']) ?></span>
            <?php if ($p['design'] !== ''): ?><span class="text-muted text-sm"><?= e($p['design']) ?></span><?php endif; ?>
          </div>
        </div>
        <div class="product-card-m-stock">
          <span class="badge <?= $isLow?'badge-low':'badge-ok' ?>"><?= (int)$p['quantity'] ?></span>
          <?php if ($isLow): ?><span class="products-low-hint"><?= (int)$p['low_size_count'] ?> low</span><?php endif; ?>
        </div>
      </div>
      <div class="product-card-m-row">
        <span class="text-muted">SKU</span>
        <span class="mono text-sm"><?= e($p['sku'] ?? '—') ?></span>
      </div>
      <div class="product-card-m-row">
        <span class="text-muted">Sizes</span>
        <span><strong><?= $sizeCount ?></strong> size<?= $sizeCount===1?'':'s' ?></span>
      </div>
      <div class="product-card-m-row">
        <span class="text-muted">Price</span>
        <span class="font-bold"><?= $priceLabel ?></span>
      </div>
      <div class="product-card-m-actions">
        <a href="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/edit" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen" aria-hidden="true"></i> View / Edit</a>
        <form method="POST" action="<?= BASE_PATH ?>/products/<?= (int)$p['id'] ?>/delete"
              onsubmit="return confirm('Remove this product and all <?= $sizeCount ?> size<?= $sizeCount===1?'':'s' ?>?')">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
        </form>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
