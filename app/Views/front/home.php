<?php
/** Customer home. Expects: $categories, $vehicles, $gu, $vehicleCount */
$siteName = setting('site_name', 'Dwarka Rental');
?>
<!-- HERO -->
<section style="background:linear-gradient(135deg,var(--p),#151a3a 120%);color:#fff;position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;opacity:.15;background:radial-gradient(circle at 80% 20%, var(--gold), transparent 40%),radial-gradient(circle at 15% 80%, var(--s), transparent 45%)"></div>
  <div class="container position-relative py-4 py-md-5">
    <div class="text-center mb-3">
      <span class="jai" style="color:var(--gold);font-size:15px">🙏 જય દ્વારકાધીશ</span>
      <h1 class="mt-1" style="font-size:30px;line-height:1.15">Rent a Bike, Scooty &amp; Car <br class="d-none d-md-block">in <span style="color:var(--gold)">Dwarka</span></h1>
      <p style="opacity:.9;margin-bottom:0">દ્વારકામાં બાઇક, એક્ટિવા, સ્કૂટી અને કાર ભાડે — સ્કેન કરો, બુક કરો, રાઇડ કરો 🛵</p>
    </div>

    <!-- Search card -->
    <div class="card shadow-soft border-0" style="border-radius:18px">
      <div class="card-body">
        <form action="<?= e(base_url('/vehicles')) ?>" method="get" class="row g-2">
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-muted"><i class="bi bi-scooter"></i> Vehicle</label>
            <select name="category" class="form-select">
              <option value="">All vehicles</option>
              <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($gu && $c['name_gu'] ? $c['name_gu'] : $c['name']) ?></option><?php endforeach; ?>
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
            <div class="small fw-semibold mt-1"><?= e($gu && $c['name_gu'] ? $c['name_gu'] : $c['name']) ?></div>
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
              <div class="fw-semibold text-truncate"><?= e($gu && $v['name_gu'] ? $v['name_gu'] : $v['name']) ?></div>
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
        <h2 class="section-title mb-2">Vehicle rental across Devbhoomi Dwarka</h2>
        <p class="text-muted small mb-2">
          <?= e($siteName) ?> offers affordable <strong>bike rental, scooty &amp; Activa on rent, self-drive car rental,
          tempo traveller and cycle hire in Dwarka</strong>. Whether you are here for
          <strong>Dwarkadhish Temple</strong> darshan, a trip to <strong>Beyt Dwarka</strong>, <strong>Nageshwar Jyotirlinga</strong>,
          <strong>Gomti Ghat</strong>, <strong>Rukmini Temple</strong>, <strong>Okha</strong> or <strong>Mithapur</strong> —
          book a two-wheeler or car online and pick it up nearby. Hourly and daily packages available.
        </p>
        <div class="d-flex flex-wrap gap-1">
          <?php foreach (['Bike rent in Dwarka','Scooty on rent','Activa rental','Self-drive car','Tempo traveller','Cycle hire','Beyt Dwarka','Nageshwar','Gomti Ghat','Okha'] as $k): ?>
            <span class="pill" style="background:#eef2f8;color:var(--muted)"><?= e($k) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
</div>
