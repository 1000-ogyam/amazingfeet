<?php
/** Page links for a server-paginated list. Expects $pagination from paginateQuery(). */
$pgPage = (int)$pagination['page'];
$pgTotal = (int)$pagination['total'];
$pgPages = (int)$pagination['totalPages'];
$pgPer = (int)$pagination['perPage'];
$pgUrl = static function (int $n): string {
    $params = array_filter(array_merge($_GET, ['page' => $n]), static fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
};
$pgFrom = $pgTotal === 0 ? 0 : ($pgPage - 1) * $pgPer + 1;
$pgTo = min($pgPage * $pgPer, $pgTotal);
?>
<?php if ($pgPages > 1): ?>
<div class="products-pager no-print">
  <div class="products-pager-info text-muted text-sm">Showing <?= $pgFrom ?>–<?= $pgTo ?> of <?= $pgTotal ?> · Page <?= $pgPage ?> of <?= $pgPages ?></div>
  <div class="products-pager-btns">
    <?php if ($pgPage > 1): ?>
    <a class="btn btn-ghost btn-sm" href="<?= e($pgUrl(1)) ?>" title="First"><i class="fa-solid fa-angles-left" aria-hidden="true"></i></a>
    <a class="btn btn-ghost btn-sm" href="<?= e($pgUrl($pgPage - 1)) ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev</a>
    <?php else: ?>
    <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-angles-left" aria-hidden="true"></i></button>
    <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Prev</button>
    <?php endif; ?>
    <?php
      $pgStart = max(1, $pgPage - 2);
      $pgEnd = min($pgPages, $pgPage + 2);
      if ($pgStart > 1) echo '<span class="products-pager-ellipsis">…</span>';
      for ($i = $pgStart; $i <= $pgEnd; $i++):
        if ($i === $pgPage):
    ?>
    <span class="btn btn-primary btn-sm products-page-current"><?= $i ?></span>
    <?php else: ?>
    <a class="btn btn-ghost btn-sm" href="<?= e($pgUrl($i)) ?>"><?= $i ?></a>
    <?php
        endif;
      endfor;
      if ($pgEnd < $pgPages) echo '<span class="products-pager-ellipsis">…</span>';
    ?>
    <?php if ($pgPage < $pgPages): ?>
    <a class="btn btn-ghost btn-sm" href="<?= e($pgUrl($pgPage + 1)) ?>">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    <a class="btn btn-ghost btn-sm" href="<?= e($pgUrl($pgPages)) ?>" title="Last"><i class="fa-solid fa-angles-right" aria-hidden="true"></i></a>
    <?php else: ?>
    <button type="button" class="btn btn-ghost btn-sm" disabled>Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
    <button type="button" class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-angles-right" aria-hidden="true"></i></button>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
