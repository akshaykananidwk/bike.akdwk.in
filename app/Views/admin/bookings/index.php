<?php /** @var array $bookings,$f,$shops,$agencies */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Bookings</h5>
  <a href="<?= e(base_url('/admin/bookings/create')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Walk-in Booking</a>
</div>
<form class="card card-body mb-3" method="get">
  <div class="row g-2">
    <div class="col-6 col-md-2"><input name="q" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Code/name/mobile"></div>
    <div class="col-6 col-md-2"><select name="status" class="form-select form-select-sm"><option value="">Any status</option><?php foreach (['pending_payment','confirmed','picked_up','returned','completed','cancelled','no_show'] as $s): ?><option value="<?= $s ?>" <?= $f['status']===$s?'selected':'' ?>><?= str_replace('_',' ',$s) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select name="payment" class="form-select form-select-sm"><option value="">Any payment</option><?php foreach (['unpaid','advance_paid','pending_verification','paid','refunded'] as $s): ?><option value="<?= $s ?>" <?= $f['payment']===$s?'selected':'' ?>><?= str_replace('_',' ',$s) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select name="shop" class="form-select form-select-sm"><option value="">Any shop</option><?php foreach ($shops as $s): ?><option value="<?= $s['id'] ?>" <?= (string)$f['shop']===(string)$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><input type="date" name="from" value="<?= e($f['from']) ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-2"><input type="date" name="to" value="<?= e($f['to']) ?>" class="form-control form-control-sm"></div>
  </div>
  <div class="mt-2"><button class="btn btn-sm btn-primary">Filter</button> <a href="<?= e(base_url('/admin/bookings')) ?>" class="btn btn-sm btn-outline-secondary">Reset</a></div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>Code</th><th>Customer</th><th>Vehicle</th><th>Shop</th><th>Amount</th><th>Paid</th><th>Status</th><th>Payment</th><th>Date</th></tr></thead>
  <tbody>
  <?php if (!$bookings): ?><tr><td colspan="9" class="text-center text-muted py-3">No bookings.</td></tr><?php endif; ?>
  <?php foreach ($bookings as $b): ?>
    <tr onclick="location='<?= e(base_url('/admin/bookings/'.$b['id'])) ?>'" style="cursor:pointer">
      <td><span class="badge bg-dark"><?= e($b['code']) ?></span></td>
      <td><?= e($b['customer_name']) ?><br><small class="text-muted"><?= e($b['customer_mobile']) ?></small></td>
      <td><?= e($b['vehicle_name']) ?></td>
      <td><?= e($b['shop_name'] ?: 'Direct') ?></td>
      <td><?= money($b['total_amount']) ?></td>
      <td><?= money($b['paid_amount']) ?></td>
      <td><span class="badge bg-<?= in_array($b['status'],['confirmed','completed','returned','picked_up'])?'success':($b['status']==='cancelled'?'danger':'secondary') ?>"><?= e(str_replace('_',' ',$b['status'])) ?></span></td>
      <td><span class="badge bg-<?= $b['payment_status']==='paid'?'success':($b['payment_status']==='pending_verification'?'warning':'secondary') ?>"><?= e(str_replace('_',' ',$b['payment_status'])) ?></span></td>
      <td><small><?= e(date('d M h:iA', strtotime($b['created_at']))) ?></small></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
