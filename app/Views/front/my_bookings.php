<?php /** @var array $bookings, $mobile, bool $gu */ ?>
<h5 class="mb-3"><i class="bi bi-bag-check"></i> My Bookings</h5>
<?php if (!$mobile): ?>
  <div class="card card-body">
    <p class="text-muted">Verify your mobile with OTP to view your bookings.</p>
    <div class="input-group mb-2" style="max-width:360px">
      <input id="mbMobile" class="form-control" placeholder="Mobile number" maxlength="10">
      <button class="btn btn-outline-primary" id="mbSend"><?= e(__('send_otp')) ?></button>
    </div>
    <div class="input-group d-none" style="max-width:360px" id="mbOtpRow">
      <input id="mbOtp" class="form-control" placeholder="OTP" maxlength="6">
      <button class="btn btn-success" id="mbVerify"><?= e(__('verify_otp')) ?></button>
    </div>
    <div id="mbMsg" class="small mt-1"></div>
  </div>
  <script>
  async function post(url,data){const fd=new FormData();fd.append('_csrf',window.CSRF);for(const k in data)fd.append(k,data[k]);const r=await fetch(url,{method:'POST',headers:{'X-CSRF-Token':window.CSRF,'X-Requested-With':'XMLHttpRequest'},body:fd});return r.json();}
  document.getElementById('mbSend').onclick=async()=>{const m=document.getElementById('mbMobile').value.trim();const r=await post('<?= e(base_url('/otp/send')) ?>',{mobile:m});document.getElementById('mbMsg').textContent=r.ok?(r.message+(r.debug_otp?' (dev '+r.debug_otp+')':'')):r.error;if(r.ok)document.getElementById('mbOtpRow').classList.remove('d-none');};
  document.getElementById('mbVerify').onclick=async()=>{const m=document.getElementById('mbMobile').value.trim();const o=document.getElementById('mbOtp').value.trim();const r=await post('<?= e(base_url('/otp/verify')) ?>',{mobile:m,otp:o});if(r.ok){location.reload();}else{document.getElementById('mbMsg').textContent=r.error;}};
  </script>
<?php else: ?>
  <?php if (!empty($referral) && $referral['code']): ?>
    <div class="card border-0 shadow-soft mb-3" style="border-radius:16px;background:linear-gradient(135deg,var(--p),var(--s));color:#fff">
      <div class="card-body">
        <div class="fw-bold"><i class="bi bi-gift"></i> Invite friends, both save</div>
        <div class="small" style="opacity:.9">Share your code — your friend gets <?= money($referral['reward']) ?> off their first booking and you earn credit too.</div>
        <div class="d-flex align-items-center gap-2 mt-2">
          <span class="badge bg-light text-dark fs-6" id="refCode"><?= e($referral['code']) ?></span>
          <button class="btn btn-sm btn-light" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?= e($referral['code']) ?>');this.textContent='Copied!'">Copy</button>
          <a class="btn btn-sm btn-light" target="_blank"
             href="https://wa.me/?text=<?= rawurlencode('Book your bike/car/taxi in Dwarka on ' . setting('site_name','Dwarka Rental') . '. Use my code ' . $referral['code'] . ' to get a discount! ' . base_url('/')) ?>">
            <i class="bi bi-whatsapp"></i> Share
          </a>
        </div>
        <?php if ($referral['credit'] > 0 || $referral['points'] > 0): ?>
          <div class="mt-2 small">
            <?php if ($referral['credit'] > 0): ?><span class="badge bg-light text-dark">Credit: <?= money($referral['credit']) ?></span><?php endif; ?>
            <?php if ($referral['points'] > 0): ?><span class="badge bg-light text-dark">Points: <?= (int)$referral['points'] ?></span><?php endif; ?>
            <div style="opacity:.9">Your credit is applied automatically on your next booking.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!$bookings): ?><div class="text-center text-muted py-4">No bookings found for <?= e($mobile) ?>.</div><?php endif; ?>
  <?php foreach ($bookings as $b): ?>
    <div class="card mb-2"><div class="card-body py-2">
      <div class="d-flex justify-content-between">
        <div>
          <div class="fw-bold"><?= e($b['vehicle_name']) ?> <span class="badge bg-dark"><?= e($b['code']) ?></span></div>
          <div class="small text-muted"><?= e(date('d M h:iA', strtotime($b['pickup_at']))) ?> → <?= e(date('d M h:iA', strtotime($b['drop_at']))) ?></div>
        </div>
        <div class="text-end">
          <span class="badge bg-<?= in_array($b['status'],['confirmed','completed','returned','picked_up'])?'success':($b['status']==='cancelled'?'danger':'secondary') ?>"><?= e(str_replace('_',' ',$b['status'])) ?></span>
          <div class="small"><?= money($b['paid_amount']) ?></div>
        </div>
      </div>
      <a href="<?= e(base_url('/booking/'.$b['code'].'/success')) ?>" class="stretched-link"></a>
    </div></div>
  <?php endforeach; ?>
<?php endif; ?>
