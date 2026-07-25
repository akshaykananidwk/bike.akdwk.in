<?php
/** Customer home. Expects: $categories, $vehicles, $gu, $vehicleCount */
$siteName = setting('site_name', 'Dwarka Rental');
?>
<!-- HERO -->
<section style="background:linear-gradient(135deg,var(--p),#151a3a 120%);color:#fff;position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;opacity:.15;background:radial-gradient(circle at 80% 20%, var(--gold), transparent 40%),radial-gradient(circle at 15% 80%, var(--s), transparent 45%)"></div>
  <div class="container position-relative py-4 py-md-5">
    <div class="text-center mb-3">
      <span class="jai" style="color:var(--gold);font-size:15px">🙏 Jai Dwarkadhish</span>
      <h1 class="mt-1" style="font-size:30px;line-height:1.15">Bike, Car, Taxi &amp; Tempo Rental <br class="d-none d-md-block">in <span style="color:var(--gold)">Dwarka</span></h1>
      <p style="opacity:.9;margin-bottom:0">Book bikes, scooters, self-drive cars, taxis, cabs &amp; tempo travellers in Dwarka — scan, book, ride 🛵</p>
    </div>

    <!-- Search card -->
    <div class="card shadow-soft border-0" style="border-radius:18px">
      <div class="card-body">
        <form action="<?= e(base_url('/vehicles')) ?>" method="get" class="row g-2">
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-muted"><i class="bi bi-scooter"></i> Vehicle</label>
            <select name="category" class="form-select">
              <option value="">All vehicles</option>
              <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted"><i class="bi bi-calendar-event"></i> Pickup</label>
            <input type="datetime-local" name="pickup" class="form-control">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-muted"><i class="bi bi-calendar-check"></i> Drop</label>
            <input type="datetime-local" name="drop" class="form-control">
          </div>
          <div class="col-12 col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100 tap"><i class="bi bi-search"></i> Search</button>
          </div>
        </form>
      </div>
    </div>

    <!-- trust strip -->
    <div class="d-flex justify-content-center gap-3 flex-wrap mt-3 small" style="opacity:.95">
      <span><i class="bi bi-lightning-charge-fill" style="color:var(--gold)"></i> Instant booking</span>
      <span><i class="bi bi-shield-check" style="color:var(--gold)"></i> Verified vehicles</span>
      <span><i class="bi bi-cash-coin" style="color:var(--gold)"></i> Best price</span>
      <span><i class="bi bi-whatsapp" style="color:var(--gold)"></i> WhatsApp support</span>
    </div>
  </div>
</section>

<div class="container">
  <!-- CATEGORIES -->
  <section class="py-4">
    <h2 class="section-title mb-3">Choose your ride</h2>
    <div class="row g-2 g-md-3">
      <?php foreach ($categories as $c): ?>
        <div class="col-4 col-md-2">
          <a href="<?= e(base_url('/vehicles?category=' . $c['slug'])) ?>" class="chip h-100">
            <i class="bi <?= e($c['icon'] ?: 'bi-scooter') ?>"></i>
            <div class="small fw-semibold mt-1"><?= e($c['name']) ?></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- FEATURED VEHICLES -->
  <section class="pb-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Popular vehicles</h2>
      <a href="<?= e(base_url('/vehicles')) ?>" class="btn btn-sm btn-outline-primary">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
      <?php if (!$vehicles): ?>
        <div class="col-12 text-center text-muted py-4">Vehicles are being added — check back soon! 🛵</div>
      <?php endif; ?>
      <?php foreach ($vehicles as $v): ?>
        <div class="col-6 col-lg-3">
          <div class="veh-card h-100">
            <div class="imgwrap d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#eef2f8,#dbe4f0)">
              <?php if ($v['main_image']): ?>
                <img src="<?= e(upload_url($v['main_image'])) ?>" loading="lazy" alt="<?= e($v['name']) ?> on rent in Dwarka">
              <?php else: ?>
                <i class="bi <?= e($v['icon'] ?? 'bi-scooter') ?>" style="font-size:46px;color:#9db0c8"></i>
              <?php endif; ?>
              <span class="pill position-absolute top-0 start-0 m-2" style="background:rgba(0,0,0,.6);color:#fff"><?= e($v['category_name']) ?></span>
            </div>
            <div class="p-2 p-md-3">
              <div class="fw-semibold text-truncate"><?= e($v['name']) ?></div>
              <div class="d-flex align-items-center gap-1 my-1" style="color:var(--gold);font-size:12px">
                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                <span class="text-muted ms-1">4.5</span>
              </div>
              <div class="d-flex align-items-end justify-content-between">
                <div><span class="price-tag fs-5"><?= money($v['price_day']) ?></span><span class="text-muted small">/day</span></div>
              </div>
              <a href="<?= e(base_url('/vehicle/' . $v['id'])) ?>" class="btn btn-primary btn-sm w-100 mt-2 tap">Book now</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- DARSHAN PACKAGES -->
  <?php if (!empty($packages)): ?>
  <section class="py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Dwarka Darshan &amp; Taxi Packages</h2>
      <a href="<?= e(base_url('/packages')) ?>" class="btn btn-sm btn-outline-primary">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach ($packages as $p): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <a href="<?= e(base_url('/package/' . $p['slug'])) ?>" class="card border-0 shadow-soft h-100 text-decoration-none" style="border-radius:16px;color:inherit">
            <div class="card-body">
              <span class="pill" style="background:var(--p);color:#fff"><?= e(ucfirst($p['type'])) ?></span>
              <div class="fw-bold mt-2"><?= e($p['name']) ?></div>
              <div class="text-muted small"><?= e($p['short_desc']) ?></div>
              <div class="d-flex align-items-baseline gap-2 mt-2">
                <span class="price-tag fs-5"><?= money($p['price']) ?></span>
                <?php if ($p['strike_price'] > 0): ?><span class="text-muted text-decoration-line-through small"><?= money($p['strike_price']) ?></span><?php endif; ?>
                <?php if ($p['duration_text']): ?><span class="text-muted small ms-auto"><i class="bi bi-clock"></i> <?= e($p['duration_text']) ?></span><?php endif; ?>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- WHY CHOOSE US -->
  <section class="py-4">
    <h2 class="section-title mb-3 text-center">Why book with <?= e($siteName) ?>?</h2>
    <div class="row g-3">
      <?php
      $feats = [
        ['bi-lightning-charge-fill', 'Instant confirmation', 'Book in under 2 minutes with online payment & OTP.'],
        ['bi-shield-check', 'Verified &amp; serviced', 'Well-maintained bikes and cars from trusted local agencies.'],
        ['bi-cash-coin', 'Transparent pricing', 'No hidden charges. Pay per hour or per day, your choice.'],
        ['bi-geo-alt-fill', 'Right in Dwarka', 'Pickup near Dwarkadhish Temple, Gomti Ghat & Beyt Dwarka.'],
      ];
      foreach ($feats as [$ic, $t, $d]): ?>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-soft h-100" style="border-radius:16px">
            <div class="card-body text-center">
              <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--p),var(--s));color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 10px"><i class="bi <?= $ic ?>"></i></div>
              <div class="fw-bold small"><?= $t ?></div>
              <div class="text-muted" style="font-size:12px"><?= $d ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="py-3">
    <h2 class="section-title mb-3 text-center">How it works</h2>
    <div class="row g-3 text-center">
      <?php foreach ([['1','bi-search','Choose a vehicle','Pick a bike, scooty or car & your dates.'],['2','bi-credit-card','Pay securely','Pay online or via UPI, verify with OTP.'],['3','bi-emoji-sunglasses','Ride & explore','Collect your ride and enjoy Dwarka darshan!']] as [$n,$ic,$t,$d]): ?>
        <div class="col-12 col-md-4">
          <div class="p-3">
            <div style="width:56px;height:56px;border-radius:50%;border:2px dashed var(--p);color:var(--p);display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 8px"><i class="bi <?= $ic ?>"></i></div>
            <div class="fw-bold">Step <?= $n ?> · <?= $t ?></div>
            <div class="text-muted small"><?= $d ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SERVICE AREA (local SEO content) -->
  <section class="py-4">
    <div class="card border-0 shadow-soft" style="border-radius:16px">
      <div class="card-body">
        <h2 class="section-title mb-2">Bike, Car, Taxi &amp; Tempo Rental in Dwarka</h2>
        <p class="text-muted small mb-2">
          <?= e($siteName) ?> is your one-stop platform for <strong>bike rental, scooty &amp; Activa on rent,
          self-drive car rental, taxi &amp; cab booking, tempo traveller hire and cycle rental in Dwarka</strong>.
          Book online in minutes for <strong>Dwarkadhish Temple</strong> darshan, local sightseeing, or outstation trips.
        </p>
        <p class="text-muted small mb-2">
          Popular services include <strong>Dwarka darshan by taxi</strong>, <strong>self-drive cars</strong> and
          <strong>two-wheelers on rent</strong>, plus cab routes like <strong>Dwarka to Somnath</strong>,
          <strong>Dwarka to Okha &amp; Beyt Dwarka</strong>, <strong>Dwarka to Nageshwar Jyotirlinga</strong>,
          <strong>Dwarka to Jamnagar, Rajkot, Porbandar</strong> and <strong>Dwarka airport &amp; railway station pickup</strong>.
          One-way and round-trip cabs, 12 &amp; 17-seater tempo travellers, hourly and daily packages — all at the best price.
        </p>
        <div class="d-flex flex-wrap gap-1">
          <?php
          // Internal links to SEO landing pages (service in location).
          $popular = [
            'bike-rental-in-dwarka'=>'Bike rental in Dwarka','scooty-rental-in-dwarka'=>'Scooty on rent Dwarka',
            'car-rental-in-dwarka'=>'Car rental Dwarka','taxi-service-in-dwarka'=>'Taxi service Dwarka',
            'cab-booking-in-dwarka'=>'Cab booking Dwarka','car-with-driver-in-dwarka'=>'Car with driver',
            'tempo-traveller-in-dwarka'=>'Tempo traveller Dwarka','self-drive-car-in-dwarka'=>'Self-drive car',
            'bike-rental-in-okha'=>'Bike rental Okha','taxi-service-in-okha'=>'Taxi in Okha',
            'car-rental-in-mithapur'=>'Car rental Mithapur','taxi-service-in-beyt-dwarka'=>'Taxi Beyt Dwarka',
            'cycle-rental-in-dwarka'=>'Cycle hire Dwarka','tempo-traveller-in-nageshwar'=>'Tempo Nageshwar',
          ];
          foreach ($popular as $slug => $label): ?>
            <a class="pill text-decoration-none" style="background:#eef2f8;color:var(--muted)" href="<?= e(base_url('/rent/'.$slug)) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
</div>
