<?php /** @var array $masked, $s */
$chk = function (string $k) use ($s) { return ($s[$k] ?? '') === '1' ? 'checked' : ''; };
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/payments')) ?>">Payments</a></li>
  <li class="nav-item"><a class="nav-link active" href="<?= e(base_url('/admin/payments/gateways')) ?>">Gateways</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/payouts')) ?>">Payouts</a></li>
</ul>

<div class="row g-3">
  <div class="col-lg-7">
    <form method="post">
      <?= csrf_field() ?>

      <div class="card">
        <div class="card-header fw-bold">
          <i class="bi bi-credit-card-2-front"></i> Razorpay
          <small class="text-muted">(cards, UPI, netbanking, wallets — encrypted at rest)</small>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label">Key ID</label>
            <input name="razorpay_key_id" class="form-control" autocomplete="off" placeholder="rzp_live_xxxxxxxxxxxx">
            <small class="text-muted">Current: <?= e($masked['razorpay_key_id'] ?: 'not set') ?></small>
          </div>
          <div class="mb-2">
            <label class="form-label">Key Secret</label>
            <input name="razorpay_key_secret" class="form-control" autocomplete="off" placeholder="enter secret">
            <small class="text-muted">Current: <?= e($masked['razorpay_key_secret'] ?: 'not set') ?></small>
          </div>
          <div class="mb-2">
            <label class="form-label">Webhook Secret</label>
            <input name="razorpay_webhook_secret" class="form-control" autocomplete="off" placeholder="enter webhook secret">
            <small class="text-muted">Current: <?= e($masked['razorpay_webhook_secret'] ?: 'not set') ?></small>
          </div>
          <div class="form-check form-switch mt-3">
            <input type="checkbox" name="razorpay_enabled" value="1" class="form-check-input" id="rz" <?= $chk('razorpay_enabled') ?>>
            <label class="form-check-label" for="rz"><strong>Enable Razorpay checkout</strong></label>
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-header fw-bold"><i class="bi bi-toggles"></i> Other payment methods</div>
        <div class="card-body">
          <div class="form-check form-switch"><input type="checkbox" name="upi_enabled" value="1" class="form-check-input" id="pm_upi" <?= $chk('upi_enabled') ?>><label class="form-check-label" for="pm_upi">UPI QR (manual verification)</label></div>
          <div class="form-check form-switch"><input type="checkbox" name="cash_enabled" value="1" class="form-check-input" id="pm_cash" <?= $chk('cash_enabled') ?>><label class="form-check-label" for="pm_cash">Cash / pay at pickup</label></div>
          <hr>
          <div class="row g-2">
            <div class="col-md-4"><label class="form-label">PhonePe Merchant ID</label><input name="phonepe_merchant_id" class="form-control" autocomplete="off" placeholder="<?= e($masked['phonepe_merchant_id'] ?: 'not set') ?>"></div>
            <div class="col-md-4"><label class="form-label">PhonePe Salt Key</label><input name="phonepe_salt_key" class="form-control" autocomplete="off" placeholder="<?= e($masked['phonepe_salt_key'] ?: 'not set') ?>"></div>
            <div class="col-md-4"><label class="form-label">Salt Index</label><input name="phonepe_salt_index" class="form-control" autocomplete="off" placeholder="<?= e($masked['phonepe_salt_index'] ?: '1') ?>"></div>
            <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="phonepe_enabled" value="1" class="form-check-input" id="pm_pp" <?= $chk('phonepe_enabled') ?>><label class="form-check-label" for="pm_pp">Enable PhonePe</label></div></div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-md-6"><label class="form-label">Cashfree App ID</label><input name="cashfree_app_id" class="form-control" autocomplete="off" placeholder="<?= e($masked['cashfree_app_id'] ?: 'not set') ?>"></div>
            <div class="col-md-6"><label class="form-label">Cashfree Secret Key</label><input name="cashfree_secret_key" class="form-control" autocomplete="off" placeholder="<?= e($masked['cashfree_secret_key'] ?: 'not set') ?>"></div>
            <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="cashfree_enabled" value="1" class="form-check-input" id="pm_cf" <?= $chk('cashfree_enabled') ?>><label class="form-check-label" for="pm_cf">Enable Cashfree</label></div></div>
          </div>
        </div>
      </div>

      <button class="btn btn-primary mt-3"><i class="bi bi-save"></i> Save Gateway Settings</button>
      <p class="text-muted small mt-2 mb-0">Leave a field blank to keep the value already stored. Secrets are never shown in full again after saving.</p>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header fw-bold">Test your keys</div>
      <div class="card-body">
        <p class="small text-muted">Creates a ₹1 order on Razorpay to confirm the Key ID and Key Secret are correct. No money is charged.</p>
        <button class="btn btn-success" id="gwTest"><i class="bi bi-lightning-charge"></i> Test Razorpay Keys</button>
        <div id="gwResult" class="mt-2 small"></div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header fw-bold">Webhook URL</div>
      <div class="card-body">
        <p class="small text-muted mb-2">Add this in Razorpay Dashboard → Settings → Webhooks, with the events <code>payment.captured</code> and <code>payment.failed</code>. Use the same Webhook Secret you saved here.</p>
        <div class="input-group">
          <input class="form-control" id="whUrl" readonly value="<?= e(base_url('/webhook/razorpay')) ?>">
          <button class="btn btn-outline-secondary" type="button" id="whCopy">Copy</button>
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header fw-bold">Where to find the keys</div>
      <div class="card-body small text-muted">
        <ol class="ps-3 mb-0">
          <li>Sign in at <strong>dashboard.razorpay.com</strong>.</li>
          <li>Go to <strong>Account &amp; Settings → API Keys</strong>.</li>
          <li>Click <strong>Generate Live Key</strong> — copy the Key ID and Key Secret (the secret is shown only once).</li>
          <li>Paste both above and save, then press <strong>Test Razorpay Keys</strong>.</li>
          <li>Add the webhook URL, copy its secret back into the Webhook Secret field, and save again.</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('whCopy').onclick = function () {
  var i = document.getElementById('whUrl'); i.select(); i.setSelectionRange(0, 99999);
  if (navigator.clipboard) { navigator.clipboard.writeText(i.value); }
  this.textContent = 'Copied!';
};
document.getElementById('gwTest').onclick = async function () {
  var out = document.getElementById('gwResult');
  out.innerHTML = 'Testing…';
  var fd = new FormData(); fd.append('_csrf', window.CSRF);
  try {
    var r = await fetch('<?= e(base_url('/admin/payments/test-gateway')) ?>', {
      method: 'POST', headers: {'X-CSRF-Token': window.CSRF, 'X-Requested-With': 'XMLHttpRequest'}, body: fd
    });
    var d = await r.json();
    out.innerHTML = d.ok
      ? '<span class="text-success">✔ ' + d.message + '</span>'
      : '<span class="text-danger">✘ ' + (d.error || 'Test failed') + '</span>';
  } catch (e) {
    out.innerHTML = '<span class="text-danger">✘ ' + e + '</span>';
  }
};
</script>
