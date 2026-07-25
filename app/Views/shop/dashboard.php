<?php /** @var array $shop,$wallet; float $todayC,$totalC,$monthC; int $todayBookings */ ?>
<?php if (($shop['status'] ?? '') !== 'active'): ?>
  <div class="alert alert-warning py-2 small"><i class="bi bi-hourglass-split"></i>
    <strong>Pending approval.</strong> Your shop is registered but not live yet. Our team will verify and activate it shortly —
    then your QR poster will start earning commission.</div>
<?php endif; ?>
<div class="mb-2"><span class="badge bg-dark"><?= e($shop['code']) ?></span> <span class="text-muted small"><?= e($shop['name']) ?></span></div>
<div class="row g-2 mb-3">
  <div class="col-6"><div class="stat" style="background:#0d6efd"><div class="small opacity-75"><?= e(__('today_bookings')) ?></div><div class="fs-4 fw-bold"><?= $todayBookings ?></div></div></div>
  <div class="col-6"><div class="stat" style="background:#20c997"><div class="small opacity-75"><?= e(__('today_commission')) ?></div><div class="fs-4 fw-bold"><?= money($todayC) ?></div></div></div>
  <div class="col-6"><div class="stat" style="background:#8b5cf6"><div class="small opacity-75"><?= e(__('this_month')) ?></div><div class="fs-5 fw-bold"><?= money($monthC) ?></div></div></div>
  <div class="col-6"><div class="stat" style="background:#f59e0b"><div class="small opacity-75"><?= e(__('total_commission')) ?></div><div class="fs-5 fw-bold"><?= money($totalC) ?></div></div></div>
</div>
<div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center">
  <div><div class="text-muted small"><?= e(__('wallet_balance')) ?></div><div class="fs-3 fw-bold text-success"><?= money($wallet['balance']) ?></div>
    <div class="text-muted small"><?= e(__('total_withdrawn')) ?>: <?= money($wallet['total_withdrawn'] ?? 0) ?></div></div>
  <a href="<?= e(base_url('/shop/wallet')) ?>" class="btn btn-success"><?= e(__('withdraw')) ?></a>
</div></div>
<div class="d-grid gap-2">
  <a href="<?= e(base_url('/shop/qr')) ?>" class="btn btn-outline-primary"><i class="bi bi-qr-code"></i> <?= e(__('my_qr')) ?></a>
  <a href="<?= e(base_url('/shop/bookings')) ?>" class="btn btn-outline-secondary"><i class="bi bi-calendar-check"></i> View Bookings</a>
</div>
