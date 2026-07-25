<?php /** @var array $bookings */ ?>
<h6 class="mb-2">Bookings from your QR</h6>
<?php if (!$bookings): ?><div class="text-center text-muted py-4">No bookings yet. Share your QR poster to start earning!</div><?php endif; ?>
<?php foreach ($bookings as $b): ?>
  <div class="card mb-2"><div class="card-body py-2">
    <div class="d-flex justify-content-between">
      <div>
        <div class="fw-semibold small"><?= e($b['vehicle_name']) ?> <span class="badge bg-dark"><?= e($b['code']) ?></span></div>
        <div class="text-muted" style="font-size:12px"><?= e($b['customer_name']) ?> · <?= e(date('d M', strtotime($b['created_at']))) ?></div>
      </div>
      <div class="text-end">
        <div class="text-success fw-bold"><?= money($b['commission'] ?? 0) ?></div>
        <span class="badge bg-<?= in_array($b['status'],['confirmed','completed','returned','picked_up'])?'success':($b['status']==='cancelled'?'danger':'secondary') ?>" style="font-size:10px"><?= e(str_replace('_',' ',$b['status'])) ?></span>
      </div>
    </div>
  </div></div>
<?php endforeach; ?>
