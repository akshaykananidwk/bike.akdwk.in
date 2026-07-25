<?php /** @var array $b,$payments,$ledger */ ?>
<a href="<?= e(base_url('/admin/bookings')) ?>" class="btn btn-sm btn-link px-0">&larr; Bookings</a>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-header d-flex justify-content-between"><span class="fw-bold"><?= e($b['code']) ?></span>
      <span class="badge bg-<?= in_array($b['status'],['confirmed','completed','returned','picked_up'])?'success':($b['status']==='cancelled'?'danger':'secondary') ?>"><?= e(str_replace('_',' ',$b['status'])) ?></span></div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <h6 class="text-muted small">CUSTOMER</h6>
        <div><?= e($b['customer_name']) ?> · <a href="tel:<?= e($b['customer_mobile']) ?>"><?= e($b['customer_mobile']) ?></a></div>
        <?php if ($b['customer_address']): ?><div class="small text-muted"><?= e($b['customer_address']) ?></div><?php endif; ?>
        <div class="small">Riders: <?= (int)$b['riders'] ?></div>
      </div>
      <div class="col-md-6">
        <h6 class="text-muted small">RENTAL</h6>
        <div><?= e($b['vehicle_name']) ?></div>
        <div class="small"><?= e(date('d M h:iA', strtotime($b['pickup_at']))) ?> → <?= e(date('d M h:iA', strtotime($b['drop_at']))) ?></div>
        <div class="small">Agency: <?= e($b['agency_name']) ?> <?= $b['agency_mobile']?'('.e($b['agency_mobile']).')':'' ?></div>
        <div class="small">Via: <?= e($b['shop_name'] ?: 'Direct') ?> (<?= e($b['source']) ?>)</div>
      </div>
      <div class="col-12"><hr class="my-1">
        <div class="row text-center">
          <div class="col"><small class="text-muted">Total</small><div class="fw-bold"><?= money($b['total_amount']) ?></div></div>
          <div class="col"><small class="text-muted">Paid</small><div class="fw-bold text-success"><?= money($b['paid_amount']) ?></div></div>
          <div class="col"><small class="text-muted">Balance</small><div class="fw-bold"><?= money($b['balance_amount']) ?></div></div>
          <div class="col"><small class="text-muted">Deposit</small><div class="fw-bold"><?= money($b['deposit']) ?></div></div>
          <?php if ($b['refund_amount']>0): ?><div class="col"><small class="text-muted">Refunded</small><div class="fw-bold text-danger"><?= money($b['refund_amount']) ?></div></div><?php endif; ?>
        </div>
      </div>
    </div></div>

    <!-- KYC images -->
    <div class="card mt-3"><div class="card-header fw-bold">KYC Documents</div><div class="card-body d-flex gap-2 flex-wrap">
      <?php foreach (['dl_image'=>'Driving Licence','id_image'=>'ID Proof','pickup_photo'=>'Pickup','return_photo'=>'Return'] as $k=>$lbl): ?>
        <?php if ($b[$k]): ?><div class="text-center"><a href="<?= e(upload_url($b[$k])) ?>" target="_blank"><img src="<?= e(upload_url($b[$k])) ?>" style="height:90px" class="rounded border"></a><div class="small text-muted"><?= $lbl ?></div></div><?php endif; ?>
      <?php endforeach; ?>
      <?php if (!$b['dl_image'] && !$b['id_image']): ?><span class="text-muted">No documents.</span><?php endif; ?>
    </div></div>

    <!-- Payments + ledger -->
    <div class="card mt-3"><div class="card-header fw-bold">Payments</div><div class="table-responsive"><table class="table table-sm mb-0">
      <thead><tr><th>Gateway</th><th>Amount</th><th>Status</th><th>UTR</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($payments as $p): ?><tr><td><?= e($p['gateway']) ?></td><td><?= money($p['amount']) ?></td><td><?= e($p['status']) ?></td><td><?= e($p['utr']) ?></td><td><small><?= e(date('d M h:iA', strtotime($p['created_at']))) ?></small></td></tr><?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="5" class="text-muted text-center">No payments.</td></tr><?php endif; ?>
      </tbody></table></div></div>

    <?php if ($ledger): ?>
    <div class="card mt-3"><div class="card-header fw-bold">Commission Ledger</div><div class="table-responsive"><table class="table table-sm mb-0">
      <thead><tr><th>Beneficiary</th><th>Type</th><th>Base</th><th>Rate</th><th>Amount</th></tr></thead><tbody>
      <?php foreach ($ledger as $l): ?><tr class="<?= $l['entry_type']==='reversal'?'text-danger':'' ?>"><td><?= e($l['beneficiary_type']) ?> <?= $l['beneficiary_id']?'#'.$l['beneficiary_id']:'' ?></td><td><?= e($l['entry_type']) ?></td><td><?= money($l['base_amount']) ?></td><td><?= $l['rate_type']?($l['rate_type']==='percent'?$l['rate_value'].'%':money($l['rate_value'])):'—' ?></td><td><?= money($l['amount']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
    <?php endif; ?>
  </div>

  <!-- Actions -->
  <div class="col-lg-4">
    <div class="card"><div class="card-header fw-bold">Change Status</div><div class="card-body">
      <form method="post" action="<?= e(base_url('/admin/bookings/'.$b['id'].'/status')) ?>">
        <?= csrf_field() ?>
        <select name="status" class="form-select mb-2" onchange="document.getElementById('refundBox').style.display=this.value==='cancelled'?'block':'none'">
          <?php foreach (['confirmed','picked_up','returned','completed','cancelled','no_show'] as $s): ?><option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
        </select>
        <div id="refundBox" style="display:none" class="mb-2">
          <label class="form-label small">Refund amount (creates reversal)</label>
          <input name="refund_amount" type="number" step="0.01" class="form-control" value="0" max="<?= e($b['paid_amount']) ?>">
        </div>
        <button class="btn btn-primary w-100" onclick="return confirm('Update status?')">Update Status</button>
      </form>
    </div></div>
    <div class="card mt-3"><div class="card-body d-grid gap-2">
      <a href="<?= e(base_url('/booking/'.$b['code'].'/invoice')) ?>" target="_blank" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> Print Invoice</a>
      <a href="<?= e(base_url('/booking/'.$b['code'].'/success')) ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-eye"></i> Customer View</a>
    </div></div>
  </div>
</div>
