<?php
/** @var array $booking, $agency; string $upiId, $deepLink; ?string $qrImage; float $amount */
use App\Core\Qr;
$qrData = $deepLink ? Qr::toDataUri($deepLink, 6, 2) : null;
?>
<div class="text-center mb-3">
  <h5>Pay <?= money($amount) ?> via UPI</h5>
  <div class="badge bg-dark"><?= e($booking['code']) ?></div>
</div>

<div class="card mb-3"><div class="card-body text-center">
  <?php if ($qrImage): ?>
    <img src="<?= e(upload_url($qrImage)) ?>" alt="UPI QR" style="max-width:240px" class="img-fluid border rounded">
    <div class="text-muted small mt-2">Scan with any UPI app and pay exactly <?= money($amount) ?></div>
  <?php elseif ($qrData): ?>
    <img src="<?= e($qrData) ?>" alt="UPI QR" style="max-width:240px" class="border rounded">
    <div class="mt-2"><a href="<?= e($deepLink) ?>" class="btn btn-primary tap"><i class="bi bi-phone"></i> Open UPI App</a></div>
    <div class="text-muted small mt-1">UPI ID: <strong><?= e($upiId) ?></strong></div>
  <?php else: ?>
    <div class="alert alert-warning">UPI details are not configured. Please choose another payment method.</div>
  <?php endif; ?>
</div></div>

<form method="post" enctype="multipart/form-data" class="card card-body">
  <?= csrf_field() ?>
  <h6 class="fw-bold">After paying, confirm here</h6>
  <div class="mb-2"><label class="form-label">UTR / Reference Number</label><input name="utr" class="form-control" placeholder="12-digit UTR from your UPI app"></div>
  <div class="mb-3"><label class="form-label">Payment Screenshot</label><input type="file" name="screenshot" accept="image/*" class="form-control"></div>
  <button class="btn btn-success tap"><i class="bi bi-check-lg"></i> Submit for Verification</button>
  <a href="<?= e(base_url('/checkout/'.$booking['code'])) ?>" class="btn btn-link">Choose another method</a>
</form>
