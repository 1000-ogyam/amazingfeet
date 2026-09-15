<?php
$pageTitle = 'Staff Assessments';
$cp = '/assessments';
ob_start();
$am = new AssessmentModel();
?>
<div class="flex-center gap-2 mb-2" style="flex-wrap:wrap">
  <form method="GET" class="flex-center gap-1" style="flex:1;flex-wrap:wrap">
    <input type="date" name="date" value="<?= e($filters['date'] ?? '') ?>" style="width:auto" title="Exact date">
    <input type="date" name="from" value="<?= e($filters['from'] ?? '') ?>" style="width:auto" title="From">
    <input type="date" name="to" value="<?= e($filters['to'] ?? '') ?>" style="width:auto" title="To">
    <select name="staff_id" style="max-width:180px">
      <option value="">All Staff</option>
      <?php foreach ($staff as $s): ?>
      <option value="<?= $s['id'] ?>" <?= ($filters['staff_id']??'')==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
    <a href="<?= BASE_PATH ?>/assessments" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset</a>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-clipboard-user" aria-hidden="true"></i> Daily assessments <span class="text-muted text-sm">(<?= count($rows) ?>)</span></h3>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th><th>Staff</th><th>Pairs day</th><th>Week</th><th>Month</th>
          <th>Commission</th><th>Contacts</th><th>Checklist</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--muted)">No daily reports yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $score = $am->checklistScore($r);
          $scoreColor = $score >= 80 ? 'var(--accent2)' : ($score >= 60 ? 'var(--warning)' : 'var(--danger)');
        ?>
        <tr>
          <td class="text-sm"><?= date('d M Y', strtotime($r['report_date'])) ?></td>
          <td class="font-bold text-sm"><?= e($r['staff_name']) ?></td>
          <td class="font-bold"><?= (int)$r['pairs_day'] ?></td>
          <td class="text-sm"><?= (int)$r['pairs_week'] ?>/<?= (int)$r['week_target'] ?></td>
          <td class="text-sm"><?= (int)$r['pairs_month'] ?>/<?= (int)$r['month_target'] ?></td>
          <td class="text-sm"><?= e($r['commission_level']) ?></td>
          <td class="text-sm"><?= (int)$r['contacts_collected'] ?>/<?= (int)$r['customers_today'] ?></td>
          <td style="font-weight:700;color:<?= $scoreColor ?>"><?= $score ?>%</td>
          <td><a href="<?= BASE_PATH ?>/assessments/<?= (int)$r['id'] ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-eye" aria-hidden="true"></i> View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
