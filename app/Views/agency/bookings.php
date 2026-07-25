<?php /** @var array $bookings */ ?>
<h6 class="mb-2">Bookings</h6>
<?php if (!$bookings): ?><p class="text-muted small">No bookings yet.</p><?php endif; ?>
<?php foreach ($bookings as $b): ?>
  <div class="card mb-2"><div class="card-body py-2">
    <div class="d-flex justify-content-between">
      <div>
        <div class="fw-semibold small"><?= e($b['vehicle_name']) ?> <span class="badge bg-dark"><?= e($b['code']) ?></span></div>
        <div class="text-muted" style="font-size:12px"><?= e($b['customer_name']) ?> · <a href="tel:<?= e($b['customer_mobile']) ?>"><?= e($b['customer_mobile']) ?></a></div>
        <div class="text-muted" style="font-size:12px"><?= e(date('d M h:iA', strtotime($b['pickup_at']))) ?> → <?= e(date('d M h:iA', strtotime($b['drop_at']))) ?></div>
        <div class="small">Balance to collect: <strong><?= money($b['balance_amount']) ?></strong></div>
      </div>
      <span class="badge bg-<?= in_array($b['status'],['confirmed','picked_up','returned','completed'])?'success':'secondary' ?> align-self-start"><?= e(str_replace('_',' ',$b['status'])) ?></span>
    </div>
    <?php if ($b['status']==='confirmed'): ?>
      <button class="btn btn-sm btn-primary mt-2 w-100" data-bs-toggle="modal" data-bs-target="#pick<?= $b['id'] ?>"><i class="bi bi-box-arrow-up"></i> Mark Picked Up</button>
      <div class="modal fade" id="pick<?= $b['id'] ?>"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= e(base_url('/agency/bookings/'.$b['id'].'/pickup')) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
          <div class="modal-header"><h6 class="modal-title">Pickup — <?= e($b['code']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body text-start">
            <div class="mb-2"><label class="form-label small">Odometer</label><input name="odo" class="form-control"></div>
            <div class="mb-2"><label class="form-label small">Fuel level</label><input name="fuel" class="form-control" placeholder="e.g. Full / Half"></div>
            <div class="mb-2"><label class="form-label small">Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
          </div>
          <div class="modal-footer"><button class="btn btn-primary w-100">Confirm Pickup</button></div>
        </form>
      </div></div></div>
    <?php elseif ($b['status']==='picked_up'): ?>
      <button class="btn btn-sm btn-success mt-2 w-100" data-bs-toggle="modal" data-bs-target="#ret<?= $b['id'] ?>"><i class="bi bi-box-arrow-in-down"></i> Mark Returned</button>
      <div class="modal fade" id="ret<?= $b['id'] ?>"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= e(base_url('/agency/bookings/'.$b['id'].'/return')) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
          <div class="modal-header"><h6 class="modal-title">Return — <?= e($b['code']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body text-start">
            <div class="mb-2"><label class="form-label small">Odometer</label><input name="odo" class="form-control"></div>
            <div class="mb-2"><label class="form-label small">Fuel level</label><input name="fuel" class="form-control"></div>
            <div class="mb-2"><label class="form-label small">Extra charges (late/damage/fuel)</label><input name="extra_charges" type="number" step="0.01" class="form-control" value="0"></div>
            <div class="mb-2"><label class="form-label small">Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
            <p class="small text-muted">Deposit on file: <?= money($b['deposit']) ?>. Refund = deposit − extra charges.</p>
          </div>
          <div class="modal-footer"><button class="btn btn-success w-100">Confirm Return</button></div>
        </form>
      </div></div></div>
    <?php endif; ?>
  </div></div>
<?php endforeach; ?>
