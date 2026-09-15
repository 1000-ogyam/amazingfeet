<?php
$pageTitle = 'Daily Report';
$cp = '/assessment';
$val = static function (string $key, $existing, string $default = 'no'): string {
    return ($existing[$key] ?? $default) === 'yes' ? 'yes' : 'no';
};
$yn = static function (string $name, string $selected): string {
    $yes = $selected === 'yes' ? 'checked' : '';
    $no  = $selected !== 'yes' ? 'checked' : '';
    return '<div class="yn-group" role="group" aria-label="'.e($name).'">'
        .'<label class="yn-opt"><input type="radio" name="'.e($name).'" value="yes" '.$yes.'> Yes</label>'
        .'<label class="yn-opt"><input type="radio" name="'.e($name).'" value="no" '.$no.'> No</label>'
        .'</div>';
};
ob_start();
?>
<style>
.assess-grid{display:flex;flex-direction:column;gap:1rem;max-width:820px}
.assess-meta{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.assess-stat{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:.75rem .9rem}
.assess-stat .lbl{font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.04em}
.assess-stat .val{font-size:1.25rem;font-weight:700;margin-top:.15rem}
.assess-stat .sub{font-size:.8rem;color:var(--muted);margin-top:.15rem}
.yn-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.55rem 0;border-bottom:1px solid var(--border)}
.yn-row:last-child{border-bottom:0}
.yn-row span{font-size:.9rem}
.yn-group{display:flex;gap:.35rem}
.yn-opt{display:inline-flex;align-items:center;gap:.3rem;font-weight:500;font-size:.85rem;text-transform:none;letter-spacing:0;padding:.28rem .55rem;border:1px solid var(--border);border-radius:6px;background:#fff;cursor:pointer}
.yn-opt:has(input:checked){border-color:var(--accent);background:rgba(200,83,42,.08)}
.yn-opt input{width:auto;margin:0}
.commission-box{border-left:3px solid var(--accent);background:var(--bg3);padding:.75rem .9rem;border-radius:0 8px 8px 0;margin-top:.5rem;font-size:.88rem}
@media(max-width:640px){.assess-meta{grid-template-columns:1fr}}
</style>

<div class="assess-grid">
  <div class="flex-center gap-1" style="flex-wrap:wrap">
    <form method="GET" class="flex-center gap-1" style="flex:1">
      <label class="text-sm text-muted" style="text-transform:none;letter-spacing:0;font-weight:500">Report date</label>
      <input type="date" name="date" value="<?= e($date) ?>" style="width:auto" onchange="this.form.submit()">
    </form>
    <a href="<?= BASE_PATH ?>/assessment/history" class="btn btn-ghost btn-sm"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> My history</a>
  </div>

  <?php if ($existing): ?>
  <div class="alert alert-success"><span class="alert-ic"><i class="fa-solid fa-check" aria-hidden="true"></i></span> Report already saved for this date — submit again to update.</div>
  <?php endif; ?>

  <form method="POST" action="<?= BASE_PATH ?>/assessment" class="assess-grid">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="report_date" value="<?= e($date) ?>">

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Daily report</h3></div>
      <div class="card-body">
        <div class="assess-meta">
          <div class="assess-stat"><div class="lbl">Date</div><div class="val" style="font-size:1rem"><?= date('d M Y', strtotime($date)) ?></div></div>
          <div class="assess-stat"><div class="lbl">Name</div><div class="val" style="font-size:1rem"><?= e($staffName) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Sales target tracking</h3></div>
      <div class="card-body">
        <p class="text-muted text-sm" style="margin-top:0">Figures auto-filled from your POS sales (pairs = units sold).</p>
        <div class="assess-meta">
          <div class="assess-stat">
            <div class="lbl">Daily pairs sold</div>
            <div class="val"><?= (int)$stats['pairs_day'] ?></div>
          </div>
          <div class="assess-stat">
            <div class="lbl">Weekly total so far</div>
            <div class="val"><?= (int)$stats['pairs_week'] ?> <span class="sub">/ <?= (int)$stats['week_target'] ?> pairs</span></div>
            <div class="sub">Week of <?= date('d M', strtotime($stats['week_start'])) ?></div>
          </div>
          <div class="assess-stat">
            <div class="lbl">Monthly total so far</div>
            <div class="val"><?= (int)$stats['pairs_month'] ?> <span class="sub">/ <?= (int)$stats['month_target'] ?> pairs</span></div>
            <div class="sub"><?= date('F Y', strtotime($date)) ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-percent" aria-hidden="true"></i> Commission status (month-to-date)</h3></div>
      <div class="card-body">
        <ul class="text-sm" style="margin:0 0 .75rem;padding-left:1.1rem;color:var(--muted)">
          <li>Below 40 pairs → No commission</li>
          <li>40–50 pairs → 3%</li>
          <li>51–70 pairs → 5%</li>
          <li>70+ pairs → 7–8%</li>
        </ul>
        <div class="commission-box">
          <strong>Current performance level:</strong> <?= e($stats['commission_level']) ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-address-book" aria-hidden="true"></i> Customer contact capture</h3></div>
      <div class="card-body">
        <div class="assess-meta" style="margin-bottom:.75rem">
          <div class="assess-stat">
            <div class="lbl">Total customers today</div>
            <div class="val"><?= (int)$stats['customers_today'] ?></div>
            <div class="sub">From your sales today</div>
          </div>
          <div class="assess-stat">
            <div class="lbl">Contacts collected</div>
            <div class="val"><?= (int)$stats['contacts_collected'] ?></div>
            <div class="sub">Sales with customer details</div>
          </div>
        </div>
        <div class="yn-row">
          <span>Confirm ALL customers were asked for contact</span>
          <?= $yn('asked_all_customers', $val('asked_all_customers', $existing)) ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-broom" aria-hidden="true"></i> Shop cleaning</h3></div>
      <div class="card-body">
        <?php
        $cleaning = [
          'floor_cleaned' => 'Floor swept & mopped',
          'glass_cleaned' => 'Glass cleaned (display + door)',
          'shelves_dusted' => 'Shelves dusted',
          'shoes_arranged' => 'Shoes arranged properly',
          'counter_cleaned' => 'Counter cleaned',
        ];
        foreach ($cleaning as $k => $label): ?>
        <div class="yn-row"><span><?= e($label) ?></span><?= $yn($k, $val($k, $existing)) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-toilet" aria-hidden="true"></i> Washroom / toilet</h3></div>
      <div class="card-body">
        <?php
        $wash = [
          'toilet_cleaned' => 'Toilet cleaned',
          'sink_cleaned' => 'Sink cleaned',
          'washroom_floor_ok' => 'Floor clean & dry',
          'tissue_soap_ok' => 'Tissue / soap available',
        ];
        foreach ($wash as $k => $label): ?>
        <div class="yn-row"><span><?= e($label) ?></span><?= $yn($k, $val($k, $existing)) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-user-check" aria-hidden="true"></i> Personal appearance</h3></div>
      <div class="card-body">
        <?php
        $appear = [
          'neat_dressing' => 'Neat dressing',
          'clean_footwear' => 'Clean footwear',
          'hair_tidy' => 'Hair tidy',
          'presentation_ok' => 'Overall presentation professional',
        ];
        foreach ($appear as $k => $label): ?>
        <div class="yn-row"><span><?= e($label) ?></span><?= $yn($k, $val($k, $existing)) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp posting</h3></div>
      <div class="card-body">
        <div class="yn-row">
          <span>Content posted on WhatsApp status</span>
          <?= $yn('whatsapp_posted', $val('whatsapp_posted', $existing)) ?>
        </div>
        <div class="form-group" style="margin-top:.85rem;margin-bottom:0">
          <label>Type of content</label>
          <input type="text" name="whatsapp_content" value="<?= e($existing['whatsapp_content'] ?? '') ?>" placeholder="e.g. New arrivals, promo, school shoes">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-comments" aria-hidden="true"></i> Customer experience</h3></div>
      <div class="card-body">
        <div class="yn-row">
          <span>Any complaints?</span>
          <?= $yn('has_complaints', $val('has_complaints', $existing)) ?>
        </div>
        <div class="form-group" style="margin-top:.85rem;margin-bottom:0">
          <label>If yes, explain</label>
          <textarea name="complaints_detail" rows="3" placeholder="What happened and how it was handled"><?= e($existing['complaints_detail'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fa-solid fa-note-sticky" aria-hidden="true"></i> Notes / observations</h3></div>
      <div class="card-body">
        <div class="form-group" style="margin:0">
          <label>Stock issues, customer requests, anything important</label>
          <textarea name="notes" rows="4" placeholder="Write notes here…"><?= e($existing['notes'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="flex-center gap-1">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= $existing ? 'Update report' : 'Submit daily report' ?></button>
      <a href="<?= BASE_PATH ?>/pos" class="btn btn-ghost">Back to POS</a>
    </div>
  </form>
</div>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
