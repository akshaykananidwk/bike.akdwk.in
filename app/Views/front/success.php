<?php
/** @var array $booking, $vehicle, $agency, $shop, bool $gu */
$confirmed = in_array($booking['status'], ['confirmed','picked_up','returned','completed'], true);
$waMsg = rawurlencode("My Dwarka Rental booking " . $booking['code'] . " for " . ($vehicle['name'] ?? '') . " is confirmed.");
$map = setting('map_link', '');
?>
<div class="text-center mb-3">
  <?php if ($confirmed): ?>
    <div class="display-1 text-success"><i class="bi bi-check-circle-fill"></i></div>
    <h4><?= e(__('booking_confirmed')) ?></h4>
  <?php elseif ($booking['payment_status']==='pending_verification'): ?>
    <div class="display-1 text-warning"><i class="bi bi-hourglass-split"></i></div>
    <h4>Payment under verification</h4>
    <p class="text-muted">We'll confirm your booking once payment is verified.</p>
  <?php else: ?>
    <div class="display-1 text-secondary"><i class="bi bi-clock-history"></i></div>
    <h4>Booking pending payment</h4>
    <a href="<?= e(base_url('/checkout/'.$booking['code'])) ?>" class="btn btn-primary"><?= e(__('pay_now')) ?></a>
  <?php endif; ?>
  <div class="badge bg-dark fs-6 mt-2"><?= e($booking['code']) ?></div>
</div>

<?php if (!empty($booking['pickup_otp']) && $booking['status'] === 'confirmed'): ?>
<div class="card border-warning mb-3" style="border-width:2px">
  <div class="card-body text-center">
    <div class="text-muted small"><i class="bi bi-shield-lock"></i> Show this OTP to the agency at pickup</div>
    <div class="fw-bold" style="font-size:34px;letter-spacing:8px;color:var(--p)"><?= e($booking['pickup_otp']) ?></div>
    <div class="text-muted small">The vehicle is handed over only after this OTP is verified.</div>
  </div>
</div>
<?php endif; ?>

<div class="card mb-3"><div class="card-body">
  <table class="table table-sm mb-0">
    <tr><td>Vehicle</td><td class="text-end fw-semibold"><?= e($vehicle['name'] ?? '') ?></td></tr>
    <tr><td>Pickup</td><td class="text-end"><?= e(date('d M Y, h:i A', strtotime($booking['pickup_at']))) ?></td></tr>
    <tr><td>Drop</td><td class="text-end"><?= e(date('d M Y, h:i A', strtotime($booking['drop_at']))) ?></td></tr>
    <tr><td><?= e(__('amount_paid')) ?></td><td class="text-end"><?= money($booking['paid_amount']) ?></td></tr>
    <tr><td><?= e(__('balance')) ?> (at pickup)</td><td class="text-end"><?= money($booking['balance_amount']) ?></td></tr>
    <?php if ($agency): ?><tr><td>Agency contact</td><td class="text-end"><a href="tel:<?= e($agency['mobile']) ?>"><?= e($agency['mobile']) ?></a></td></tr><?php endif; ?>
    <?php if ($shop): ?><tr><td>Booked via</td><td class="text-end"><?= e($shop['name']) ?></td></tr><?php endif; ?>
  </table>
</div></div>

<div class="d-grid gap-2">
  <a href="<?= e(base_url('/booking/'.$booking['code'].'/invoice')) ?>" class="btn btn-outline-primary tap" target="_blank"><i class="bi bi-file-earmark-pdf"></i> <?= e(__('download_pdf')) ?></a>
  <?php if ($map): ?><a href="<?= e($map) ?>" target="_blank" class="btn btn-outline-secondary tap"><i class="bi bi-geo-alt"></i> Pickup Location</a><?php endif; ?>
  <a href="https://wa.me/?text=<?= $waMsg ?>" target="_blank" class="btn btn-success tap"><i class="bi bi-whatsapp"></i> <?= e(__('open_whatsapp')) ?></a>
  <a href="<?= e(base_url('/')) ?>" class="btn btn-link"><?= e(__('home')) ?></a>
</div>
