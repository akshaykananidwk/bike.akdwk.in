<?php $g = fn($k,$d='') => e($s[$k] ?? $d); $chk = fn($k) => !empty($s[$k]) && $s[$k] !== '0' ? 'checked' : ''; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-bold">Site Identity</div><div class="card-body row g-3">
      <div class="col-md-6"><label class="form-label">Site Name</label><input name="site_name" class="form-control" value="<?= $g('site_name') ?>"></div>
      <div class="col-md-6"><label class="form-label">Site Name (ગુજરાતી)</label><input name="site_name_gu" class="form-control" value="<?= $g('site_name_gu') ?>"></div>
      <div class="col-md-8"><label class="form-label">Site URL</label><input name="site_url" class="form-control" value="<?= $g('site_url') ?>"></div>
      <div class="col-md-4"><label class="form-label">Default Language</label><select name="default_language" class="form-select"><option value="en" <?= ($s['default_language']??'')==='en'?'selected':'' ?>>English</option><option value="gu" <?= ($s['default_language']??'')==='gu'?'selected':'' ?>>ગુજરાતી</option></select></div>
      <div class="col-md-4"><label class="form-label">Currency Symbol</label><input name="currency_symbol" class="form-control" value="<?= $g('currency_symbol','₹') ?>"></div>
      <div class="col-md-4"><label class="form-label">Currency Code</label><input name="currency_code" class="form-control" value="<?= $g('currency_code','INR') ?>"></div>
      <div class="col-md-4"><label class="form-label">Timezone</label><input name="timezone" class="form-control" value="<?= $g('timezone','Asia/Kolkata') ?>"></div>
      <div class="col-md-6"><label class="form-label">Primary Color</label><input name="primary_color" type="color" class="form-control form-control-color" value="<?= $g('primary_color','#0d6efd') ?>"></div>
      <div class="col-md-6"><label class="form-label">Secondary Color</label><input name="secondary_color" type="color" class="form-control form-control-color" value="<?= $g('secondary_color','#20c997') ?>"></div>
      <div class="col-md-6"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept="image/*"><?php if (!empty($s['logo'])): ?><img src="<?= e(upload_url($s['logo'])) ?>" style="height:36px" class="mt-2"><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Favicon</label><input type="file" name="favicon" class="form-control" accept="image/*"></div>
    </div></div></div>

    <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-bold">Booking &amp; Commission Rules</div><div class="card-body row g-3">
      <div class="col-md-4"><label class="form-label">GST %</label><input name="gst_percent" type="number" step="0.01" class="form-control" value="<?= $g('gst_percent','0') ?>"></div>
      <div class="col-md-4"><label class="form-label">Advance Payment %</label><input name="advance_percent" type="number" step="0.01" class="form-control" value="<?= $g('advance_percent','100') ?>"></div>
      <div class="col-md-4"><label class="form-label">Booking Fee ₹</label><input name="booking_fee" type="number" step="0.01" class="form-control" value="<?= $g('booking_fee','0') ?>"></div>
      <div class="col-md-4"><label class="form-label">Auto-cancel (min)</label><input name="auto_cancel_minutes" type="number" class="form-control" value="<?= $g('auto_cancel_minutes','20') ?>"></div>
      <div class="col-md-4"><label class="form-label">Attribution (days)</label><input name="attribution_days" type="number" class="form-control" value="<?= $g('attribution_days','30') ?>"></div>
      <div class="col-md-4"><label class="form-label">Min. Withdrawal ₹</label><input name="min_withdrawal" type="number" class="form-control" value="<?= $g('min_withdrawal','500') ?>"></div>
      <hr class="my-1">
      <div class="col-md-6"><label class="form-label">Default Commission Type</label><select name="commission_type" class="form-select"><option value="percent" <?= ($s['commission_type']??'')==='percent'?'selected':'' ?>>Percentage %</option><option value="fixed" <?= ($s['commission_type']??'')==='fixed'?'selected':'' ?>>Fixed ₹</option></select></div>
      <div class="col-md-6"><label class="form-label">Default Commission Value</label><input name="commission_value" type="number" step="0.01" class="form-control" value="<?= $g('commission_value','10') ?>"></div>
      <div class="col-12"><div class="form-check"><input type="checkbox" name="commission_on_base_only" value="1" class="form-check-input" id="cob" <?= $chk('commission_on_base_only') ?>><label class="form-check-label" for="cob">Calculate commission on base rental only (exclude deposit &amp; taxes)</label></div></div>
      <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="otp_enabled" value="1" class="form-check-input" id="otpen" <?= (!array_key_exists('otp_enabled', $s) || $chk('otp_enabled')) ? 'checked' : '' ?>><label class="form-check-label" for="otpen"><strong>Require mobile OTP at checkout</strong> <span class="text-muted small">(auto-skipped until WhatsApp API is configured, so bookings are never blocked)</span></label></div></div>
      <hr class="my-1">
      <div class="col-12"><div class="fw-bold small mb-1">Partner registration &amp; extras</div></div>
      <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" name="shop_registration_enabled" value="1" class="form-check-input" id="sreg" <?= (!array_key_exists('shop_registration_enabled',$s) || $chk('shop_registration_enabled')) ? 'checked' : '' ?>><label class="form-check-label" for="sreg">Allow shops to register themselves</label></div></div>
      <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" name="agency_registration_enabled" value="1" class="form-check-input" id="areg" <?= (!array_key_exists('agency_registration_enabled',$s) || $chk('agency_registration_enabled')) ? 'checked' : '' ?>><label class="form-check-label" for="areg">Allow agencies to register themselves</label></div></div>
      <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" name="registration_auto_approve" value="1" class="form-check-input" id="autoap" <?= $chk('registration_auto_approve') ?>><label class="form-check-label" for="autoap">Auto-approve new partners <span class="text-muted small">(off = you approve each one)</span></label></div></div>
      <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" name="voice_greeting_enabled" value="1" class="form-check-input" id="voice" <?= (!array_key_exists('voice_greeting_enabled',$s) || $chk('voice_greeting_enabled')) ? 'checked' : '' ?>><label class="form-check-label" for="voice">Speak “Jai Dwarkadhish” when the site opens</label></div></div>
    </div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">Payment Routing &amp; Settlement</div><div class="card-body">
      <div class="form-check form-switch mb-2"><input type="checkbox" name="payments_to_platform" value="1" class="form-check-input" id="p2p" <?= (!array_key_exists('payments_to_platform',$s) || $chk('payments_to_platform')) ? 'checked' : '' ?>><label class="form-check-label" for="p2p"><strong>All payments to my (admin) account first</strong> — agencies are settled from their wallet after the hold period.</label></div>
      <div class="row g-2">
        <div class="col-6"><label class="form-label">Settlement hold (hours)</label><input name="settlement_hold_hours" type="number" class="form-control" value="<?= $g('settlement_hold_hours','48') ?>"></div>
        <div class="col-6"><label class="form-label">Platform payee name</label><input name="platform_payee_name" class="form-control" value="<?= $g('platform_payee_name') ?>"></div>
        <div class="col-12"><label class="form-label">Platform UPI ID (your account)</label><input name="platform_upi_id" class="form-control" value="<?= $g('platform_upi_id') ?>" placeholder="yourname@upi"></div>
        <div class="col-12"><label class="form-label">Platform UPI QR image</label><input type="file" name="platform_upi_qr" class="form-control" accept="image/*"><?php if (!empty($s['platform_upi_qr'])): ?><img src="<?= e(upload_url($s['platform_upi_qr'])) ?>" style="max-height:120px" class="mt-2 border rounded"><?php endif; ?></div>
      </div>
    </div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">Payment Methods</div><div class="card-body">
      <?php foreach (['razorpay_enabled'=>'Razorpay','upi_enabled'=>'UPI QR (agency-wise)','cash_enabled'=>'Cash / Pay at pickup','phonepe_enabled'=>'PhonePe','cashfree_enabled'=>'Cashfree'] as $k=>$l): ?>
        <div class="form-check form-switch"><input type="checkbox" name="<?= $k ?>" value="1" class="form-check-input" id="<?= $k ?>" <?= $chk($k) ?>><label class="form-check-label" for="<?= $k ?>"><?= e($l) ?></label></div>
      <?php endforeach; ?>
      <small class="text-muted">Configure Razorpay keys in the Payments / Settings secret section; UPI QR is set per agency.</small>
    </div></div></div>

    <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">Contact &amp; System</div><div class="card-body row g-3">
      <div class="col-md-6"><label class="form-label">Contact Mobile</label><input name="contact_mobile" class="form-control" value="<?= $g('contact_mobile') ?>"></div>
      <div class="col-md-6"><label class="form-label">Contact WhatsApp</label><input name="contact_whatsapp" class="form-control" value="<?= $g('contact_whatsapp') ?>"></div>
      <div class="col-md-6"><label class="form-label">Contact Email</label><input name="contact_email" class="form-control" value="<?= $g('contact_email') ?>"></div>
      <div class="col-md-6"><label class="form-label">Google Maps Link</label><input name="map_link" class="form-control" value="<?= $g('map_link') ?>"></div>
      <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="maintenance_mode" value="1" class="form-check-input" id="mm" <?= $chk('maintenance_mode') ?>><label class="form-check-label" for="mm"><strong>Maintenance Mode</strong> (front-end shows a maintenance page)</label></div></div>
    </div></div>
    <div class="card mt-3"><div class="card-header fw-bold">SMTP (Email)</div><div class="card-body row g-3">
      <div class="col-md-6"><label class="form-label">SMTP Host</label><input name="smtp_host" class="form-control" value="<?= $g('smtp_host') ?>"></div>
      <div class="col-md-2"><label class="form-label">Port</label><input name="smtp_port" class="form-control" value="<?= $g('smtp_port','587') ?>"></div>
      <div class="col-md-4"><label class="form-label">From Email</label><input name="smtp_from" class="form-control" value="<?= $g('smtp_from') ?>"></div>
      <div class="col-md-6"><label class="form-label">SMTP User</label><input name="smtp_user" class="form-control" value="<?= $g('smtp_user') ?>"></div>
    </div></div>
    </div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Settings</button></div>
</form>
