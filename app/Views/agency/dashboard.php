<?php /** @var array $agency,$stats,$upcoming */ ?>
<?php if (($agency['status'] ?? '') !== 'active'): ?>
  <div class="alert alert-warning py-2 small"><i class="bi bi-hourglass-split"></i>
    <strong>Pending approval.</strong> Your agency is registered but not live yet. Our team will verify and activate it shortly —
    then your vehicles can receive bookings.</div>
<?php endif; ?>
<div class="mb-2"><span class="badge bg-dark"><?= e($agency['code']) ?></span> <span class="text-muted small"><?= e($agency['name']) ?></span></div>
<div class="row g-2 mb-3">
  <div class="col-6"><div class="stat" style="background:#0d6efd"><div class="small opacity-75">Vehicles</div><div class="fs-4 fw-bold"><?= $stats['vehicles'] ?></div></div></div>
  <div class="col-6"><div class="stat" style="background:#20c997"><div class="small opacity-75">Today's Bookings</div><div class="fs-4 fw-bold"><?= $stats['today'] ?></div></div></div>
  <div class="col-6"><div class="stat" style="background:#8b5cf6"><div class="small opacity-75">Active Rides</div><div class="fs-4 fw-bold"><?= $stats['active'] ?></div></div></div>
  <div class="col-6"><div class="stat" style="background:#f59e0b"><div class="small opacity-75">Wallet</div><div class="fs-5 fw-bold"><?= money($stats['wallet']) ?></div></div></div>
</div>
<h6 class="small text-muted">Upcoming / Active</h6>
<?php if (!$upcoming): ?><p class="text-muted small">No active bookings.</p><?php endif; ?>
<?php foreach ($upcoming as $b): ?>
  <div class="card mb-1"><div class="card-body py-2 d-flex justify-content-between">
    <div><div class="fw-semibold small"><?= e($b['vehicle_name']) ?> <span class="badge bg-dark"><?= e($b['code']) ?></span></div>
      <div class="text-muted" style="font-size:12px"><?= e($b['customer_name']) ?> · <?= e(date('d M h:iA', strtotime($b['pickup_at']))) ?></div></div>
    <span class="badge bg-<?= $b['status']==='picked_up'?'info':'success' ?> align-self-center"><?= e(str_replace('_',' ',$b['status'])) ?></span>
  </div></div>
<?php endforeach; ?>
