<?php
/** @var array $booking, $vehicle, $agency, bool $gu */
$razor = setting('razorpay_enabled') && setting('razorpay_key_id');
$upi = setting('upi_enabled');
$cash = setting('cash_enabled');
?>
<div class="row g-3">
  <div class="col-md-5 order-md-2">
    <div class="card"><div class="card-header fw-bold">Booking <?= e($booking['code']) ?></div><div class="card-body">
      <div class="d-flex gap-2 mb-2">
        <img src="<?= $vehicle['main_image'] ? e(upload_url($vehicle['main_image'])) : 'https://placehold.co/80x60' ?>" style="width:80px;height:60px;object-fit:cover;border-radius:6px">
        <div><div class="fw-bold small"><?= e($vehicle['name']) ?></div><div class="text-muted" style="font-size:12px"><?= e(date('d M h:iA', strtotime($booking['pickup_at']))) ?> → <?= e(date('d M h:iA', strtotime($booking['drop_at']))) ?></div></div>
      </div>
      <table class="table table-sm mb-0">
        <tr><td>Base rental</td><td class="text-end"><?= money($booking['base_amount']) ?></td></tr>
        <?php if ($booking['tax_amount']>0): ?><tr><td>GST</td><td class="text-end"><?= money($booking['tax_amount']) ?></td></tr><?php endif; ?>
        <tr><td>Deposit (refundable)</td><td class="text-end"><?= money($booking['deposit']) ?></td></tr>
        <tr class="fw-bold border-top"><td>Total</td><td class="text-end"><?= money($booking['total_amount']) ?></td></tr>
        <tr class="text-primary fw-bold"><td>Pay now</td><td class="text-end"><?= money($booking['advance_amount']) ?></td></tr>
        <tr><td>Balance at pickup</td><td class="text-end"><?= money($booking['balance_amount']) ?></td></tr>
      </table>
    </div></div>
  </div>
  <div class="col-md-7 order-md-1">
    <h5><?= e(__('payment')) ?></h5>
    <div class="accordion" id="payAcc">
      <?php if ($razor): ?>
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#pRazor">Pay Online (Card / UPI / Netbanking)</button></h2>
        <div id="pRazor" class="accordion-collapse collapse show" data-bs-parent="#payAcc"><div class="accordion-body">
          <p class="text-muted small">Secure payment via Razorpay.</p>
          <button class="btn btn-primary w-100 tap" id="razorBtn"><i class="bi bi-lock"></i> <?= e(__('pay_now')) ?> <?= money($booking['advance_amount']) ?></button>
        </div></div>
      </div>
      <?php endif; ?>
      <?php if ($upi): ?>
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button <?= $razor?'collapsed':'' ?>" data-bs-toggle="collapse" data-bs-target="#pUpi">Pay via UPI QR</button></h2>
        <div id="pUpi" class="accordion-collapse collapse <?= $razor?'':'show' ?>" data-bs-parent="#payAcc"><div class="accordion-body">
          <a href="<?= e(base_url('/pay/'.$booking['code'].'/upi')) ?>" class="btn btn-outline-primary w-100 tap"><i class="bi bi-qr-code"></i> Show UPI QR &amp; Pay</a>
        </div></div>
      </div>
      <?php endif; ?>
      <?php if ($cash): ?>
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#pCash">Pay at Pickup (Cash)</button></h2>
        <div id="pCash" class="accordion-collapse collapse" data-bs-parent="#payAcc"><div class="accordion-body">
          <p class="text-muted small">Reserve now, pay <?= money($booking['advance_amount']) ?> at the counter. Booking fee (if any) may still apply online.</p>
          <form method="post" action="<?= e(base_url('/pay/'.$booking['code'].'/cash')) ?>"><?= csrf_field() ?>
            <button class="btn btn-success w-100 tap"><i class="bi bi-cash"></i> Reserve (Pay at pickup)</button>
          </form>
        </div></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<div style="height:20px"></div>
<script>
const rb=document.getElementById('razorBtn');
if(rb){ rb.onclick=function(){ window.location='<?= e(base_url('/pay/'.$booking['code'].'/razorpay/create')) ?>'; }; }
</script>
