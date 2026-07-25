<?php
/** SEO landing page. Expects: serviceLabel, phrase, location, locSlug, serviceSlug, vehicles, services, locations */
$siteName = setting('site_name', 'Dwarka Rental');
?>
<section style="background:linear-gradient(135deg,var(--p),#151a3a 120%);color:#fff">
  <div class="container py-4 text-center">
    <span class="jai" style="color:var(--gold)">🙏 Jai Dwarkadhish</span>
    <h1 class="mt-1" style="font-size:26px"><?= e($serviceLabel) ?> in <span style="color:var(--gold)"><?= e($location) ?></span></h1>
    <p style="opacity:.9;margin:0">Book <?= e($phrase) ?> in <?= e($location) ?>, Devbhoomi Dwarka — instant online booking, verified vehicles, best price.</p>
    <a href="<?= e(base_url('/vehicles')) ?>" class="btn btn-light btn-sm mt-2 tap"><i class="bi bi-search"></i> See all vehicles</a>
  </div>
</section>

<div class="container py-4">
  <h2 class="section-title mb-3"><?= e($serviceLabel) ?> options in <?= e($location) ?></h2>
  <div class="row g-3">
    <?php if (!$vehicles): ?>
      <div class="col-12 text-center text-muted py-3">Vehicles coming soon — <a href="<?= e(base_url('/vehicles')) ?>">browse all vehicles</a> or contact us on WhatsApp.</div>
    <?php endif; ?>
    <?php foreach ($vehicles as $v): ?>
      <div class="col-6 col-lg-3">
        <div class="veh-card h-100">
          <div class="imgwrap d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#eef2f8,#dbe4f0)">
            <?php if ($v['main_image']): ?><img src="<?= e(upload_url($v['main_image'])) ?>" loading="lazy" alt="<?= e($v['name']) ?> — <?= e($serviceLabel) ?> in <?= e($location) ?>">
            <?php else: ?><i class="bi bi-scooter" style="font-size:44px;color:#9db0c8"></i><?php endif; ?>
            <span class="pill position-absolute top-0 start-0 m-2" style="background:rgba(0,0,0,.6);color:#fff"><?= e($v['category_name']) ?></span>
          </div>
          <div class="p-2 p-md-3">
            <div class="fw-semibold text-truncate"><?= e($v['name']) ?></div>
            <div class="mt-1"><span class="price-tag fs-5"><?= money($v['price_day']) ?></span><span class="text-muted small">/day</span></div>
            <a href="<?= e(base_url('/vehicle/'.$v['id'])) ?>" class="btn btn-primary btn-sm w-100 mt-2 tap">Book now</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card border-0 shadow-soft mt-4" style="border-radius:16px"><div class="card-body">
    <h2 class="section-title mb-2"><?= e($serviceLabel) ?> near <?= e($location) ?>, Dwarka</h2>
    <p class="text-muted small">
      Looking for <strong><?= e($phrase) ?> in <?= e($location) ?></strong>? <?= e($siteName) ?> offers affordable,
      verified <?= e(strtolower($serviceLabel)) ?> for <strong>Dwarkadhish Temple darshan</strong>, local sightseeing and
      outstation trips from <?= e($location) ?> to Somnath, Okha, Nageshwar Jyotirlinga, Beyt Dwarka, Jamnagar, Rajkot and Porbandar.
      Book online in minutes, pay securely, and ride with confidence. Hourly, daily and round-trip packages available.
    </p>
  </div></div>

  <!-- Internal links (SEO) -->
  <div class="mt-4">
    <h3 class="fw-bold" style="font-size:16px">Popular searches</h3>
    <div class="d-flex flex-wrap gap-1 mt-2">
      <?php foreach ($services as $sSlug => $s): if ($sSlug === $serviceSlug) continue; ?>
        <a class="pill text-decoration-none" style="background:#eef2f8;color:var(--muted)" href="<?= e(base_url('/rent/'.$sSlug.'-in-'.$locSlug)) ?>"><?= e($s[0]) ?> in <?= e($location) ?></a>
      <?php endforeach; ?>
      <?php foreach ($locations as $lSlug => $lName): if ($lSlug === $locSlug) continue; ?>
        <a class="pill text-decoration-none" style="background:#eef2f8;color:var(--muted)" href="<?= e(base_url('/rent/'.$serviceSlug.'-in-'.$lSlug)) ?>"><?= e($serviceLabel) ?> in <?= e($lName) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
