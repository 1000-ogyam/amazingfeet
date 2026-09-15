<?php
$pageTitle = 'Report · '.date('d M Y', strtotime($row['report_date']));
$cp = '/assessments';
$backUrl = BASE_PATH.'/assessments';
$yes = static fn(string $v): string => $v === 'yes'
    ? '<span class="badge badge-ok">Yes</span>'
    : '<span class="badge badge-low">No</span>';
$line = static function (string $label, string $html): string {
    return '<div class="flex-center" style="padding:.45rem 0;border-bottom:1px solid var(--border)"><span>'.$label.'</span><span class="ml-auto">'.$html.'</span></div>';
};
ob_start();
?>
<div style="display:grid;grid-template-columns:1fr 280px;gap:1.25rem;align-items:start">
  <div style="display:flex;flex-direction:column;gap:1rem">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> <?= e($row['staff_name']) ?></h3>
        <span class="text-sm text-muted ml-auto"><?= date('d M Y', strtotime($row['report_date'])) ?></span>
      </div>
      <div class="card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
        <div><p class="text-muted text-sm">Daily pairs</p><p class="font-bold" style="font-size:1.3rem"><?= (int)$row['pairs_day'] ?></p></div>
        <div><p class="text-muted text-sm">Weekly</p><p class="font-bold"><?= (int)$row['pairs_week'] ?> / <?= (int)$row['week_target'] ?></p></div>
        <div><p class="text-muted text-sm">Monthly</p><p class="font-bold"><?= (int)$row['pairs_month'] ?> / <?= (int)$row['month_target'] ?></p></div>
        <div style="grid-column:1/-1">
          <p class="text-muted text-sm">Commission level</p>
          <p class="font-bold text-accent"><?= e($row['commission_level']) ?></p>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Customer contact</h3></div>
      <div class="card-body">
        <?= $line('Customers today', '<strong>'.(int)$row['customers_today'].'</strong>') ?>
        <?= $line('Contacts collected', '<strong>'.(int)$row['contacts_collected'].'</strong>') ?>
        <?= $line('Asked all customers', $yes($row['asked_all_customers'])) ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Shop cleaning</h3></div>
      <div class="card-body">
        <?= $line('Floor swept & mopped', $yes($row['floor_cleaned'])) ?>
        <?= $line('Glass cleaned', $yes($row['glass_cleaned'])) ?>
        <?= $line('Shelves dusted', $yes($row['shelves_dusted'])) ?>
        <?= $line('Shoes arranged', $yes($row['shoes_arranged'])) ?>
        <?= $line('Counter cleaned', $yes($row['counter_cleaned'])) ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Washroom / toilet</h3></div>
      <div class="card-body">
        <?= $line('Toilet cleaned', $yes($row['toilet_cleaned'])) ?>
        <?= $line('Sink cleaned', $yes($row['sink_cleaned'])) ?>
        <?= $line('Floor clean & dry', $yes($row['washroom_floor_ok'])) ?>
        <?= $line('Tissue / soap available', $yes($row['tissue_soap_ok'])) ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>Personal appearance</h3></div>
      <div class="card-body">
        <?= $line('Neat dressing', $yes($row['neat_dressing'])) ?>
        <?= $line('Clean footwear', $yes($row['clean_footwear'])) ?>
        <?= $line('Hair tidy', $yes($row['hair_tidy'])) ?>
        <?= $line('Presentation professional', $yes($row['presentation_ok'])) ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3>WhatsApp & experience</h3></div>
      <div class="card-body">
        <?= $line('WhatsApp posted', $yes($row['whatsapp_posted'])) ?>
        <?php if ($row['whatsapp_content']): ?>
        <div style="padding:.45rem 0;border-bottom:1px solid var(--border)">
          <p class="text-muted text-sm">Content type</p>
          <p><?= e($row['whatsapp_content']) ?></p>
        </div>
        <?php endif; ?>
        <?= $line('Complaints', $yes($row['has_complaints'])) ?>
        <?php if ($row['complaints_detail']): ?>
        <div style="padding:.45rem 0;border-bottom:1px solid var(--border)">
          <p class="text-muted text-sm">Complaint details</p>
          <p><?= nl2br(e($row['complaints_detail'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($row['notes']): ?>
        <div style="padding:.45rem 0">
          <p class="text-muted text-sm">Notes / observations</p>
          <p><?= nl2br(e($row['notes'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Checklist score</h3></div>
    <div class="card-body" style="text-align:center">
      <div style="font-size:2.6rem;font-weight:700;color:<?= $score>=80?'var(--accent2)':($score>=60?'var(--warning)':'var(--danger)') ?>"><?= (int)$score ?>%</div>
      <p class="text-muted text-sm">Yes answers across cleaning, washroom, appearance, WhatsApp & contact ask</p>
      <p class="text-muted text-sm" style="margin-top:1rem">Submitted <?= date('d M Y H:i', strtotime($row['created_at'])) ?></p>
      <?php if ($row['updated_at'] !== $row['created_at']): ?>
      <p class="text-muted text-sm">Updated <?= date('d M Y H:i', strtotime($row['updated_at'])) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
