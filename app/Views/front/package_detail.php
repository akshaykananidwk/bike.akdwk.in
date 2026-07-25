<?php
/** Package detail + booking form. Expects: $pkg, $reviews, $rating */
$errs = \App\Core\Session::flash('errors') ?? [];
$places = array_filter(array_map('trim', explode(',', (string)$pkg['places'])));
$inc = array_filter(array_map('trim', explode(',', (string)$pkg['inclusions'])));
$exc = array_filter(array_map('trim', explode(',', (string)$pkg['exclusions'])));
$avg = round((float)($rating['avg_rating'] ?? 0), 1);
$cnt = (int)($rating['cnt'] ?? 0);
$otpReq = otp_required();
?>
<div class="container py-3">
  <a href="<?= e(base_url('/packages')) ?>" class="btn btn-sm btn-link px-0"><i class="bi bi-arrow-left"></i> All packages</a>
  <?php if ($errs): ?><div class="alert alert-danger"><?php foreach ($errs as $er): ?><div><?= e($er[0]) ?></div><?php endforeach; ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card border-0 shadow-soft" style="border-radius:16px"><div class="card-body">
        <span class="pill" style="background:var(--p);color:#fff"><?= e(ucfirst($pkg['type'])) ?></span>
        <h1 class="mt-2" style="font-size:24px"><?= e($pkg['name']) ?></h1>
        <?php if ($cnt > 0): ?>
          <div style="color:var(--gold);font-size:14px">
            <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $avg >= $i ? '-fill' : ($avg >= $i-0.5 ? '-half' : '') ?>"></i><?php endfor; ?>
            <span class="text-muted small ms-1"><?= $avg ?> · <?= $cnt ?> review<?= $cnt>1?'s':'' ?></span>
          </div>
        <?php endif; ?>
        <p class="text-muted mt-2"><?= e($pkg['short_desc']) ?></p>

        <div class="row text-center g-2 my-3">
          <?php if ($pkg['duration_text']): ?><div class="col"><div class="text-muted small">Duration</div><div class="fw-bold"><?= e($pkg['duration_text']) ?></div></div><?php endif; ?>
          <?php if ($pkg['included_km']): ?><div class="col border-start"><div class="text-muted small">Included</div><div class="fw-bold"><?= (int)$pkg['included_km'] ?> km</div></div><?php endif; ?>
          <?php if ($pkg['vehicle_type']): ?><div class="col border-start"><div class="text-muted small">Vehicle</div><div class="fw-bold small"><?= e($pkg['vehicle_type']) ?></div></div><?php endif; ?>
        </div>

        <?php if ($places): ?>
          <h2 class="section-title" style="font-size:17px">Places covered</h2>
          <div class="d-flex flex-wrap gap-1 mb-3">
            <?php foreach ($places as $pl): ?><span class="pill" style="background:#eef2f8;color:var(--muted)"><i class="bi bi-geo-alt"></i> <?= e($pl) ?></span><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($pkg['description']): ?><p class="text-muted"><?= nl2br(e($pkg['description'])) ?></p><?php endif; ?>

        <div class="row g-3 mt-1">
          <?php if ($inc): ?><div class="col-md-6"><div class="fw-bold small mb-1 text-success">✔ Included</div>
            <?php foreach ($inc as $i): ?><div class="small text-muted">• <?= e($i) ?></div><?php endforeach; ?></div><?php endif; ?>
          <?php if ($exc): ?><div class="col-md-6"><div class="fw-bold small mb-1 text-danger">✘ Not included</div>
            <?php foreach ($exc as $x): ?><div class="small text-muted">• <?= e($x) ?></div><?php endforeach; ?></div><?php endif; ?>
        </div>
        <?php if ($pkg['extra_km_rate'] > 0): ?>
          <div class="alert alert-light border mt-3 small mb-0">Extra km beyond the package limit: <strong><?= money($pkg['extra_km_rate']) ?>/km</strong>, payable to the driver.</div>
        <?php endif; ?>
      </div></div>

      <?php if ($reviews): ?>
      <div class="card border-0 shadow-soft mt-3" style="border-radius:16px"><div class="card-body">
        <h2 class="section-title" style="font-size:18px">What travellers say</h2>
        <?php foreach ($reviews as $r): ?>
          <div class="border-bottom py-2">
            <div style="color:var(--gold);font-size:13px"><?php for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $r['rating']>=$i?'-fill':'' ?>"></i><?php endfor; ?>
              <span class="fw-semibold text-dark ms-1"><?= e($r['customer_name']) ?></span></div>
            <?php if ($r['comment']): ?><div class="small text-muted"><?= e($r['comment']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div></div>
      <?php endif; ?>
    </div>

    <!-- Booking box -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-soft" style="border-radius:16px;position:sticky;top:80px"><div class="card-body">
        <div class="d-flex align-items-baseline gap-2">
          <span class="price-tag fs-2"><?= money($pkg['price']) ?></span>
          <?php if ($pkg['strike_price'] > 0): ?><span class="text-muted text-decoration-line-through"><?= money($pkg['strike_price']) ?></span><?php endif; ?>
        </div>
        <div class="text-muted small mb-3">Total for the trip · pay <?= (float)$pkg['advance_percent'] ?>% now to confirm</div>

        <form method="post" action="<?= e(base_url('/package/' . $pkg['slug'] . '/book')) ?>">
          <?= csrf_field() ?>
          <div class="mb-2"><label class="form-label small fw-semibold">Travel date *</label>
            <input type="date" name="travel_date" class="form-control" min="<?= date('Y-m-d') ?>" required></div>
          <div class="row g-2">
            <div class="col-7"><label class="form-label small fw-semibold">Your name *</label>
              <input name="customer_name" class="form-control" value="<?= e(old('customer_name')) ?>" required></div>
            <div class="col-5"><label class="form-label small fw-semibold">Persons</label>
              <input type="number" name="pax" class="form-control" value="1" min="1" max="<?= (int)($pkg['seats'] ?: 10) ?>"></div>
          </div>
          <div class="mb-2 mt-2"><label class="form-label small fw-semibold">Mobile *</label>
            <div class="input-group">
              <input name="customer_mobile" id="pmobile" class="form-control" maxlength="10" inputmode="numeric" required>
              <?php if ($otpReq): ?><button type="button" class="btn btn-outline-primary" id="pSendOtp">Send OTP</button><?php endif; ?>
            </div>
          </div>
          <?php if ($otpReq): ?>
          <div class="mb-2 d-none" id="pOtpRow">
            <div class="input-group"><input id="pOtp" class="form-control" maxlength="6" placeholder="6-digit OTP">
              <button type="button" class="btn btn-success" id="pVerifyOtp">Verify</button></div>
            <div id="pOtpMsg" class="small mt-1"></div>
          </div>
          <?php endif; ?>
          <div class="mb-2"><label class="form-label small fw-semibold">Pickup address / hotel</label>
            <input name="pickup_address" class="form-control" placeholder="Hotel name or area in <?= e($pkg['from_location'] ?: 'Dwarka') ?>"></div>
          <div class="form-check mb-2"><input type="checkbox" name="terms" value="1" class="form-check-input" id="pt" required>
            <label class="form-check-label small" for="pt">I accept the <a href="<?= e(base_url('/page/terms')) ?>" target="_blank">Terms</a></label></div>
          <button class="btn btn-primary w-100 btn-lg tap"><i class="bi bi-calendar-check"></i> Book this package</button>
        </form>

        <?php if ($wa = setting('contact_whatsapp')): ?>
          <a href="https://wa.me/91<?= e($wa) ?>?text=<?= rawurlencode('Hi, I want to know more about the ' . $pkg['name'] . ' package.') ?>" target="_blank" class="btn btn-success w-100 mt-2 tap"><i class="bi bi-whatsapp"></i> Ask on WhatsApp</a>
        <?php endif; ?>
      </div></div>
    </div>
  </div>
</div>

<?php if ($otpReq): ?>
<script>
(function(){
  function post(url,data){var fd=new FormData();fd.append('_csrf',window.CSRF);for(var k in data)fd.append(k,data[k]);
    return fetch(url,{method:'POST',headers:{'X-CSRF-Token':window.CSRF,'X-Requested-With':'XMLHttpRequest'},body:fd}).then(function(r){return r.json();});}
  var send=document.getElementById('pSendOtp'), ver=document.getElementById('pVerifyOtp');
  if(send) send.onclick=async function(){
    var m=document.getElementById('pmobile').value.trim(), msg=document.getElementById('pOtpMsg');
    if(!/^[6-9]\d{9}$/.test(m)){ alert('Enter a valid mobile'); return; }
    document.getElementById('pOtpRow').classList.remove('d-none'); msg.textContent='Sending…';
    var r=await post('<?= e(base_url('/otp/send')) ?>',{mobile:m});
    msg.innerHTML = r.ok ? '<span class="text-success">'+r.message+(r.debug_otp?(' (dev '+r.debug_otp+')'):'')+'</span>'
                         : '<span class="text-danger">'+r.error+'</span>';
  };
  if(ver) ver.onclick=async function(){
    var m=document.getElementById('pmobile').value.trim(), o=document.getElementById('pOtp').value.trim(), msg=document.getElementById('pOtpMsg');
    var r=await post('<?= e(base_url('/otp/verify')) ?>',{mobile:m,otp:o});
    msg.innerHTML = r.ok ? '<span class="text-success"><i class="bi bi-check-circle"></i> Verified</span>'
                         : '<span class="text-danger">'+r.error+'</span>';
  };
})();
</script>
<?php endif; ?>
