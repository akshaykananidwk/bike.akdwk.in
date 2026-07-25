<?php
/** @var array $vehicle, bool $gu */
$errs = \App\Core\Session::flash('errors') ?? [];
$vname = $gu && $vehicle['name_gu'] ? $vehicle['name_gu'] : $vehicle['name'];
?>
<?php if ($errs): ?><div class="alert alert-danger"><?php foreach ($errs as $e): ?><div><?= e($e[0]) ?></div><?php endforeach; ?></div><?php endif; ?>

<!-- Step indicator -->
<div class="d-flex justify-content-between mb-3 small text-muted" id="steps">
  <span data-step="1" class="fw-bold text-primary">1. <?= e(__('select_vehicle')) ?></span>
  <span data-step="2">2. <?= e(__('select_time')) ?></span>
  <span data-step="3">3. <?= e(__('your_details')) ?></span>
  <span data-step="4">4. <?= e(__('payment')) ?></span>
</div>

<form method="post" action="<?= e(base_url('/book/'.$vehicle['id'])) ?>" enctype="multipart/form-data" id="bookForm">
  <?= csrf_field() ?>

  <!-- STEP 1: vehicle -->
  <section class="wz" data-step="1">
    <div class="card mb-3"><div class="card-body d-flex gap-3 align-items-center">
      <img src="<?= $vehicle['main_image'] ? e(upload_url($vehicle['main_image'])) : 'https://placehold.co/120x90' ?>" style="width:110px;height:80px;object-fit:cover;border-radius:8px">
      <div>
        <div class="fw-bold"><?= e($vname) ?></div>
        <div class="text-muted small"><?= e($vehicle['category_name']) ?> · <?= e(str_replace('_',' ',$vehicle['transmission'])) ?></div>
        <div class="text-primary fw-bold"><?= money($vehicle['price_day']) ?>/<?= e(__('per_day')) ?><?php if ($vehicle['price_hour']>0): ?> · <?= money($vehicle['price_hour']) ?>/<?= e(__('per_hour')) ?><?php endif; ?></div>
      </div>
    </div></div>
  </section>

  <!-- STEP 2: time -->
  <section class="wz d-none" data-step="2">
    <div class="card mb-3"><div class="card-body">
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label"><?= e(__('pickup_date')) ?></label><input type="datetime-local" name="pickup" id="pickup" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label"><?= e(__('drop_date')) ?></label><input type="datetime-local" name="drop" id="drop" class="form-control" required></div>
      </div>
      <div id="availMsg" class="mt-2"></div>
      <div id="quoteBox" class="mt-2"></div>
    </div></div>
  </section>

  <!-- STEP 3: details + KYC -->
  <section class="wz d-none" data-step="3">
    <div class="card mb-3"><div class="card-body row g-2">
      <div class="col-md-6"><label class="form-label"><?= e(__('name')) ?> *</label><input name="customer_name" class="form-control" value="<?= e(old('customer_name')) ?>" required></div>
      <div class="col-md-6"><label class="form-label"><?= e(__('mobile')) ?> *</label>
        <div class="input-group">
          <input name="customer_mobile" id="cmobile" class="form-control" value="<?= e(old('customer_mobile')) ?>" maxlength="10" required>
          <button type="button" class="btn btn-outline-primary" id="sendOtpBtn"><?= e(__('send_otp')) ?></button>
        </div>
      </div>
      <div class="col-12 d-none" id="otpRow">
        <label class="form-label"><?= e(__('verify_otp')) ?></label>
        <div class="input-group" style="max-width:320px">
          <input id="otpInput" class="form-control" maxlength="6" inputmode="numeric" placeholder="6-digit OTP">
          <button type="button" class="btn btn-success" id="verifyOtpBtn"><?= e(__('verify_otp')) ?></button>
        </div>
        <div id="otpMsg" class="small mt-1"></div>
        <input type="hidden" name="otp_ok" id="otpOk" value="0">
      </div>
      <div class="col-md-6"><label class="form-label"><?= e(__('alt_mobile')) ?></label><input name="customer_alt_mobile" class="form-control" maxlength="10"></div>
      <div class="col-md-6"><label class="form-label"><?= e(__('riders')) ?></label><input type="number" name="riders" class="form-control" value="1" min="1"></div>
      <div class="col-12"><label class="form-label"><?= e(__('address')) ?></label><input name="customer_address" class="form-control"></div>
      <div class="col-md-6"><label class="form-label"><?= e(__('dl_photo')) ?> *</label><input type="file" name="dl_image" accept="image/*,application/pdf" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label"><?= e(__('id_photo')) ?></label><input type="file" name="id_image" accept="image/*,application/pdf" class="form-control"></div>
      <div class="col-12"><div class="form-check"><input type="checkbox" name="terms" value="1" class="form-check-input" id="terms" required><label class="form-check-label" for="terms"><?= e(__('accept_terms')) ?> (<a href="<?= e(base_url('/page/terms')) ?>" target="_blank">Terms</a>)</label></div></div>
    </div></div>
  </section>

  <!-- STEP 4: review -->
  <section class="wz d-none" data-step="4">
    <div class="card mb-3"><div class="card-body">
      <h6 class="fw-bold">Review &amp; Confirm</h6>
      <div id="reviewBox"></div>
      <p class="small text-muted mt-2">Proceed to choose your payment method on the next screen.</p>
    </div></div>
  </section>

  <!-- Sticky action bar -->
  <div class="position-fixed bottom-0 start-0 end-0 bg-white border-top p-2 d-flex gap-2" style="z-index:1055">
    <button type="button" class="btn btn-outline-secondary tap" id="prevBtn" style="min-width:90px"><?= e(__('back')) ?></button>
    <button type="button" class="btn btn-primary flex-grow-1 tap" id="nextBtn"><?= e(__('continue')) ?></button>
    <button type="submit" class="btn btn-success flex-grow-1 tap d-none" id="submitBtn"><i class="bi bi-check-lg"></i> <?= e(__('continue')) ?> &rarr; <?= e(__('payment')) ?></button>
  </div>
</form>

<script>
(function(){
  const VID = <?= (int)$vehicle['id'] ?>;
  const CSRF = window.CSRF;
  let step = 1; const maxStep = 4;
  let availabilityOk = false;

  function show(){
    document.querySelectorAll('.wz').forEach(s=>s.classList.toggle('d-none', +s.dataset.step!==step));
    document.querySelectorAll('#steps span').forEach(s=>s.classList.toggle('text-primary', +s.dataset.step===step));
    document.querySelectorAll('#steps span').forEach(s=>s.classList.toggle('fw-bold', +s.dataset.step===step));
    prevBtn.style.visibility = step===1 ? 'hidden':'visible';
    nextBtn.classList.toggle('d-none', step===maxStep);
    submitBtn.classList.toggle('d-none', step!==maxStep);
    if(step===4) buildReview();
  }
  const prevBtn=document.getElementById('prevBtn'), nextBtn=document.getElementById('nextBtn'), submitBtn=document.getElementById('submitBtn');
  prevBtn.onclick=()=>{ if(step>1){step--;show();} };
  nextBtn.onclick=async()=>{
    if(step===2){ const ok=await checkAvail(); if(!ok) return; }
    if(step===3){ if(!validateDetails()) return; }
    if(step<maxStep){ step++; show(); }
  };

  async function post(url, data){
    const fd=new FormData(); fd.append('_csrf',CSRF); for(const k in data) fd.append(k,data[k]);
    const r=await fetch(url,{method:'POST',headers:{'X-CSRF-Token':CSRF,'X-Requested-With':'XMLHttpRequest'},body:fd});
    return r.json();
  }

  async function checkAvail(){
    const pickup=document.getElementById('pickup').value, drop=document.getElementById('drop').value;
    const msg=document.getElementById('availMsg'), qb=document.getElementById('quoteBox');
    if(!pickup||!drop){ msg.innerHTML='<span class="text-danger">Select pickup & drop time.</span>'; return false; }
    msg.innerHTML='<?= e(__('loading')) ?>';
    try{
      const r=await post('<?= e(base_url('/book/')) ?>'+VID+'/availability',{pickup,drop});
      if(!r.ok){ msg.innerHTML='<span class="text-danger">'+r.error+'</span>'; return false; }
      availabilityOk=r.available;
      if(!r.available){ msg.innerHTML='<span class="text-danger"><i class="bi bi-x-circle"></i> Not available for this time.</span>'; qb.innerHTML=''; return false; }
      msg.innerHTML='<span class="text-success"><i class="bi bi-check-circle"></i> Available ('+r.units+' unit(s) free)</span>';
      qb.innerHTML='<div class="card card-body bg-light">'+r.quote_html+'</div>';
      window._quote=r.quote;
      return true;
    }catch(e){ msg.innerHTML='<span class="text-danger">Error checking availability.</span>'; return false; }
  }

  function validateDetails(){
    const name=document.querySelector('[name=customer_name]').value.trim();
    const mob=document.getElementById('cmobile').value.trim();
    const dl=document.querySelector('[name=dl_image]').files.length;
    const terms=document.getElementById('terms').checked;
    if(!name){ alert('Enter your name'); return false; }
    if(!/^[6-9]\d{9}$/.test(mob)){ alert('Enter a valid 10-digit mobile'); return false; }
    if(document.getElementById('otpOk').value!=='1'){ alert('Please verify your mobile with OTP'); return false; }
    if(!dl){ alert('Upload your Driving Licence photo'); return false; }
    if(!terms){ alert('Please accept the Terms & Conditions'); return false; }
    return true;
  }

  function buildReview(){
    const q=window._quote||{};
    const name=document.querySelector('[name=customer_name]').value;
    const pickup=document.getElementById('pickup').value, drop=document.getElementById('drop').value;
    document.getElementById('reviewBox').innerHTML=
      '<table class="table table-sm mb-0">'+
      '<tr><td>Vehicle</td><td class="text-end"><?= e(addslashes($vname)) ?></td></tr>'+
      '<tr><td>Pickup</td><td class="text-end">'+pickup.replace('T',' ')+'</td></tr>'+
      '<tr><td>Drop</td><td class="text-end">'+drop.replace('T',' ')+'</td></tr>'+
      '<tr><td>Name</td><td class="text-end">'+name+'</td></tr>'+
      '<tr class="fw-bold"><td>Pay now</td><td class="text-end"><?= e(setting('currency_symbol','₹')) ?>'+(q.advance||0)+'</td></tr>'+
      '</table>';
  }

  // OTP
  document.getElementById('sendOtpBtn').onclick=async function(){
    const mob=document.getElementById('cmobile').value.trim();
    const msg=document.getElementById('otpMsg');
    if(!/^[6-9]\d{9}$/.test(mob)){ alert('Enter a valid mobile first'); return; }
    document.getElementById('otpRow').classList.remove('d-none');
    msg.textContent='Sending…';
    const r=await post('<?= e(base_url('/otp/send')) ?>',{mobile:mob});
    if(r.ok){ msg.innerHTML='<span class="text-success">'+r.message+(r.debug_otp?(' (dev OTP: '+r.debug_otp+')'):'')+'</span>'; }
    else { msg.innerHTML='<span class="text-danger">'+r.error+'</span>'; }
  };
  document.getElementById('verifyOtpBtn').onclick=async function(){
    const mob=document.getElementById('cmobile').value.trim();
    const otp=document.getElementById('otpInput').value.trim();
    const msg=document.getElementById('otpMsg');
    const r=await post('<?= e(base_url('/otp/verify')) ?>',{mobile:mob,otp:otp});
    if(r.ok){ msg.innerHTML='<span class="text-success"><i class="bi bi-check-circle"></i> Verified</span>'; document.getElementById('otpOk').value='1'; }
    else { msg.innerHTML='<span class="text-danger">'+r.error+'</span>'; }
  };

  show();
})();
</script>
<div style="height:70px"></div>
