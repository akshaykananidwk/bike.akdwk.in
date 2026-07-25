<?php
/** Packages listing. Expects: $packages, $type */
$types = ['' => 'All', 'darshan' => 'Darshan Tours', 'outstation' => 'Outstation', 'transfer' => 'Airport / Station'];
?>
<section style="background:linear-gradient(135deg,var(--p),#151a3a 120%);color:#fff">
  <div class="container py-4 text-center">
    <span class="jai" style="color:var(--gold)">🙏 Jai Dwarkadhish</span>
    <h1 class="mt-1" style="font-size:26px">Dwarka Darshan &amp; Taxi Packages</h1>
    <p style="opacity:.9;margin:0">Fixed price · Car with driver · Fuel &amp; tolls included · No hidden charges</p>
  </div>
</section>

<div class="container py-4">
  <div class="d-flex gap-2 flex-wrap mb-3">
    <?php foreach ($types as $k => $label): ?>
      <a href="<?= e(base_url('/packages' . ($k ? '?type=' . $k : ''))) ?>" class="btn btn-sm btn-<?= $type === $k ? 'primary' : 'outline-secondary' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="row g-3">
    <?php if (!$packages): ?>
      <div class="col-12 text-center text-muted py-4">Packages coming soon.</div>
    <?php endif; ?>
    <?php foreach ($packages as $p): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="veh-card h-100 d-flex flex-column">
          <div class="imgwrap d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,var(--p),var(--s));color:#fff;aspect-ratio:16/7">
            <?php if ($p['image']): ?>
              <img src="<?= e(upload_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <?php else: ?>
              <div class="text-center px-2"><i class="bi bi-geo-alt-fill" style="font-size:26px"></i>
                <div class="fw-bold small mt-1"><?= e($p['from_location'] ?: 'Dwarka') ?><?= $p['to_location'] && $p['to_location'] !== $p['from_location'] ? ' → ' . e($p['to_location']) : '' ?></div></div>
            <?php endif; ?>
            <span class="pill position-absolute top-0 start-0 m-2" style="background:rgba(0,0,0,.55);color:#fff"><?= e(ucfirst($p['type'])) ?></span>
          </div>
          <div class="p-3 d-flex flex-column flex-grow-1">
            <div class="fw-bold"><?= e($p['name']) ?></div>
            <div class="text-muted small mb-2"><?= e($p['short_desc']) ?></div>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <?php if ($p['duration_text']): ?><span class="pill" style="background:#eef2f8;color:var(--muted)"><i class="bi bi-clock"></i> <?= e($p['duration_text']) ?></span><?php endif; ?>
              <?php if ($p['included_km']): ?><span class="pill" style="background:#eef2f8;color:var(--muted)"><?= (int)$p['included_km'] ?> km</span><?php endif; ?>
              <?php if ($p['seats']): ?><span class="pill" style="background:#eef2f8;color:var(--muted)"><i class="bi bi-people"></i> <?= (int)$p['seats'] ?></span><?php endif; ?>
            </div>
            <div class="mt-auto">
              <div class="d-flex align-items-baseline gap-2">
                <span class="price-tag fs-4"><?= money($p['price']) ?></span>
                <?php if ($p['strike_price'] > 0): ?><span class="text-muted text-decoration-line-through small"><?= money($p['strike_price']) ?></span><?php endif; ?>
              </div>
              <a href="<?= e(base_url('/package/' . $p['slug'])) ?>" class="btn btn-primary w-100 mt-2 tap">View &amp; Book</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card border-0 shadow-soft mt-4" style="border-radius:16px"><div class="card-body">
    <h2 class="section-title mb-2">Dwarka darshan by taxi — everything included</h2>
    <p class="text-muted small mb-0">
      Book a <strong>Dwarka darshan package</strong> with a private car and experienced driver. Our fixed-price tours cover
      <strong>Dwarkadhish Temple</strong>, <strong>Nageshwar Jyotirlinga</strong>, <strong>Beyt Dwarka</strong>,
      <strong>Gomti Ghat</strong>, <strong>Rukmini Temple</strong> and <strong>Bhadkeshwar Mahadev</strong>. We also run
      <strong>Dwarka to Somnath taxi</strong>, 2-day pilgrimage tours, and <strong>Jamnagar airport</strong> and
      <strong>Dwarka railway station</strong> transfers. Fuel, tolls and driver allowance are included — no surprises.
    </p>
  </div></div>
</div>
