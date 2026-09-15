<?php $pageTitle='Sales Targets'; $cp='/targets'; ob_start(); ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:1.25rem;align-items:start">

  <!-- Performance -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">
    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> This Week's Performance</h3></div>
      <div class="card-body">
        <?php if (empty($performance)): ?>
        <p class="text-muted text-sm">No targets set for this week.</p>
        <?php else: ?>
        <?php foreach ($performance as $t): ?>
        <div style="margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border)">
          <div class="flex-center gap-1 mb-1">
            <h4 style="font-size:.95rem"><?= e($t['category_name']??'Overall Target') ?></h4>
            <span class="text-muted text-sm ml-auto"><?= date('d M',strtotime($t['week_start'])) ?> – <?= date('d M',strtotime($t['week_end'])) ?></span>
          </div>
          <!-- Units -->
          <div class="flex-center gap-1 mb-1">
            <span class="text-sm">Units: <?= $t['actual_units'] ?> / <?= $t['target_units'] ?></span>
            <span style="margin-left:auto;font-weight:700;color:<?= $t['pct_units']>=100?'var(--accent2)':($t['pct_units']>=60?'var(--warning)':'var(--danger)') ?>"><?= $t['pct_units'] ?>%</span>
          </div>
          <div class="progress-wrap mb-1">
            <div class="progress-bar <?= $t['pct_units']>=100?'green':($t['pct_units']>=60?'amber':'red') ?>" style="width:<?= $t['pct_units'] ?>%"></div>
          </div>
          <!-- Revenue -->
          <div class="flex-center gap-1 mb-1">
            <span class="text-sm">Revenue: <?= money($t['actual_revenue']) ?> / <?= money($t['target_revenue']) ?></span>
            <span style="margin-left:auto;font-weight:700;color:<?= $t['pct_revenue']>=100?'var(--accent2)':($t['pct_revenue']>=60?'var(--warning)':'var(--danger)') ?>"><?= $t['pct_revenue'] ?>%</span>
          </div>
          <div class="progress-wrap">
            <div class="progress-bar <?= $t['pct_revenue']>=100?'green':($t['pct_revenue']>=60?'amber':'red') ?>" style="width:<?= $t['pct_revenue'] ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- All targets history -->
    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-list" aria-hidden="true"></i> All Targets</h3></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Week</th><th>Category</th><th>Unit Target</th><th>Revenue Target</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($targets as $t): ?>
            <tr>
              <td class="text-sm"><?= date('d M',strtotime($t['week_start'])) ?> – <?= date('d M Y',strtotime($t['week_end'])) ?></td>
              <td><?= e($t['category_name']??'Overall') ?></td>
              <td><?= $t['target_units'] ?> units</td>
              <td><?= money($t['target_revenue']) ?></td>
              <td>
                <form method="POST" action="<?= BASE_PATH ?>/targets/<?= $t['id'] ?>/delete" onsubmit="return confirm('Delete this target?')">
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
  </div>

  <!-- Set new target -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Set New Target</h3></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_PATH ?>/targets/create">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="form-group">
          <label>Category (optional)</label>
          <select name="category_id">
            <option value="">— Overall (all categories) —</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Week Start *</label>
            <input type="date" name="week_start" required value="<?= $weekStart ?>">
          </div>
          <div class="form-group">
            <label>Week End *</label>
            <input type="date" name="week_end" required value="<?= $weekEnd ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Target Units</label>
          <input type="number" name="target_units" min="0" value="0" placeholder="e.g. 20">
        </div>
        <div class="form-group">
          <label>Target Revenue (GHS)</label>
          <input type="number" name="target_revenue" min="0" step="0.01" value="0" placeholder="e.g. 1800">
        </div>
        <button type="submit" class="btn btn-primary w-full" style="justify-content:center">Set Target <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
      </form>
    </div>
  </div>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
