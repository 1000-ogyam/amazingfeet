<?php $pageTitle='All Sales'; $cp='/sales'; ob_start(); ?>
<div class="flex-center gap-2 mb-2" style="flex-wrap:wrap">
  <form method="GET" class="flex-center gap-1" style="flex:1;flex-wrap:wrap">
    <input type="text" name="search" value="<?= e($filters['search']??'') ?>" placeholder="Search ref, staff…" style="max-width:180px">
    <input type="date" name="date" value="<?= e($filters['date']??'') ?>" style="width:auto">
    <select name="location_id" style="max-width:150px">
      <option value="">All Locations</option>
      <?php foreach($locations as $l): ?>
      <option value="<?= $l['id'] ?>" <?= ($filters['location_id']??'')==$l['id']?'selected':'' ?>><?= e($l['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="staff_id" style="max-width:150px">
      <option value="">All Staff</option>
      <?php foreach($staff as $s): ?>
      <option value="<?= $s['id'] ?>" <?= ($filters['staff_id']??'')==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="payment_method" style="max-width:130px">
      <option value="">All Payments</option>
      <?php foreach(['cash','momo','card','split'] as $pm): ?>
      <option value="<?= $pm ?>" <?= ($filters['payment_method']??'')===$pm?'selected':'' ?>><?= strtoupper($pm) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a href="<?= BASE_PATH ?>/sales" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
  </form>
  <a href="<?= BASE_PATH ?>/pos" class="btn btn-primary"><i class="fa-solid fa-cash-register" aria-hidden="true"></i> New Sale</a>
</div>

<form method="POST" action="<?= BASE_PATH ?>/sales/bulk" id="salesBulkForm">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <?php foreach ($filters as $fk => $fv): if (!is_scalar($fv)) continue; ?>
  <input type="hidden" name="filters[<?= e($fk) ?>]" value="<?= e((string)$fv) ?>">
  <?php endforeach; ?>

  <div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:.5rem">
      <h3><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Sales <span class="text-muted text-sm">(<?= count($sales) ?>)</span></h3>
      <?php $totalRev = array_sum(array_column($sales,'total')); ?>
      <span class="text-sm">Total: <strong class="text-accent"><?= money($totalRev) ?></strong></span>
      <div class="ml-auto flex-center gap-1" style="flex-wrap:wrap">
        <span class="text-sm text-muted" id="bulkCount">0 selected</span>
        <button type="submit" name="bulk_action" value="export" class="btn btn-ghost btn-sm" id="bulkExportBtn">
          <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export
        </button>
        <button type="submit" name="bulk_action" value="delete" class="btn btn-danger btn-sm" id="bulkDeleteBtn"
                onclick="return confirm('Delete selected sales and restore stock?')">
          <i class="fa-solid fa-trash" aria-hidden="true"></i> Delete
        </button>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:2.2rem">
              <input type="checkbox" id="selectAllSales" aria-label="Select all" style="width:auto">
            </th>
            <th>Ref</th><th>Date/Time</th><th>Staff</th><th>Location</th><th>Payment</th><th>Total</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($sales)): ?>
          <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--muted)">No sales found.</td></tr>
          <?php endif; ?>
          <?php foreach($sales as $s): ?>
          <tr>
            <td>
              <input type="checkbox" name="ids[]" value="<?= (int)$s['id'] ?>" class="sale-row-check" style="width:auto">
            </td>
            <td class="mono text-accent text-sm"><?= e($s['sale_ref']) ?></td>
            <td class="text-sm"><?= date('d M Y H:i', strtotime($s['created_at'])) ?></td>
            <td class="text-sm"><?= e($s['staff_name']) ?></td>
            <td class="text-sm"><?= e($s['location_name']) ?></td>
            <td><span class="badge badge-<?= $s['payment_method'] ?>"><?= strtoupper($s['payment_method']) ?></span></td>
            <td class="font-bold"><?= money($s['total']) ?></td>
            <td style="white-space:nowrap">
              <a href="<?= BASE_PATH ?>/sales/<?= $s['id'] ?>" class="btn btn-ghost btn-xs" title="View"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
              <a href="<?= BASE_PATH ?>/sales/<?= $s['id'] ?>/edit" class="btn btn-ghost btn-xs" title="Edit"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
              <button type="submit" formaction="<?= BASE_PATH ?>/sales/<?= $s['id'] ?>/delete" formmethod="POST"
                      class="btn btn-danger btn-xs" title="Delete"
                      onclick="return confirm('Delete <?= e($s['sale_ref']) ?> and restore stock?')">
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<script>
(function () {
  const form = document.getElementById('salesBulkForm');
  const selectAll = document.getElementById('selectAllSales');
  const checks = () => [...form.querySelectorAll('.sale-row-check')];
  const countEl = document.getElementById('bulkCount');
  const deleteBtn = document.getElementById('bulkDeleteBtn');

  function sync() {
    const list = checks();
    const n = list.filter(c => c.checked).length;
    countEl.textContent = n + ' selected';
    if (selectAll) selectAll.checked = list.length > 0 && n === list.length;
    if (selectAll) selectAll.indeterminate = n > 0 && n < list.length;
    deleteBtn.disabled = n === 0;
  }

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      checks().forEach(c => { c.checked = selectAll.checked; });
      sync();
    });
  }
  form.addEventListener('change', e => {
    if (e.target.classList.contains('sale-row-check')) sync();
  });
  form.addEventListener('submit', e => {
    const action = (e.submitter && e.submitter.value) || '';
    if (action === 'delete' && !checks().some(c => c.checked)) {
      e.preventDefault();
      alert('Select at least one sale to delete.');
    }
  });
  sync();
})();
</script>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
