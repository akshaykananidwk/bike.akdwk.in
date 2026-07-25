<?php
/** Admin dashboard. Expects: $stats, $series, $topShops, $recent */
$cards = [
    ['today_bookings', "Today's Bookings", $stats['today_bookings'], '#0d6efd', 'bi-calendar-check'],
    ['today_revenue', "Today's Revenue", money($stats['today_revenue']), '#20c997', 'bi-cash'],
    ['pending_payouts', 'Pending Payouts', $stats['pending_payouts'], '#f59e0b', 'bi-wallet2'],
    ['active_vehicles', 'Active Vehicles', $stats['active_vehicles'], '#8b5cf6', 'bi-scooter'],
];
?>
<?php if ($pendingShops || $pendingAgencies): ?>
<div class="alert alert-warning shadow-sm">
  <div class="fw-bold mb-2"><i class="bi bi-person-plus"></i> New partner registrations awaiting your approval</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <tbody>
      <?php foreach ($pendingShops as $ps): ?>
        <tr>
          <td><span class="badge bg-success">Shop</span></td>
          <td><span class="badge bg-dark"><?= e($ps['code']) ?></span></td>
          <td><?= e($ps['name']) ?></td>
          <td><?= e($ps['mobile']) ?></td>
          <td><small class="text-muted"><?= e(date('d M h:iA', strtotime($ps['created_at']))) ?></small></td>
          <td class="text-end"><a href="<?= e(base_url('/admin/shops/'.$ps['id'].'/edit')) ?>" class="btn btn-sm btn-primary">Review &amp; Approve</a></td>
        </tr>
      <?php endforeach; ?>
      <?php foreach ($pendingAgencies as $pa): ?>
        <tr>
          <td><span class="badge bg-warning text-dark">Agency</span></td>
          <td><span class="badge bg-dark"><?= e($pa['code']) ?></span></td>
          <td><?= e($pa['name']) ?></td>
          <td><?= e($pa['mobile']) ?></td>
          <td><small class="text-muted"><?= e(date('d M h:iA', strtotime($pa['created_at']))) ?></small></td>
          <td class="text-end"><a href="<?= e(base_url('/admin/agencies/'.$pa['id'].'/edit')) ?>" class="btn btn-sm btn-primary">Review &amp; Approve</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="small text-muted mt-1">Set <strong>Status = Active</strong> on the partner's page to make them live.</div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <?php foreach ($cards as [$key, $label, $value, $color, $icon]): ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card" style="background:<?= e($color) ?>">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="small opacity-75"><?= e($label) ?></div>
          <div class="fs-4 fw-bold"><?= e($value) ?></div>
        </div>
        <i class="bi <?= e($icon) ?> fs-1 opacity-50"></i>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header fw-bold">Revenue — Last 7 Days</div>
      <div class="card-body"><canvas id="revChart" height="110"></canvas></div>
    </div>
    <div class="card mt-3">
      <div class="card-header fw-bold">Recent Bookings</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Code</th><th>Customer</th><th>Vehicle</th><th>Shop</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (!$recent): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">No bookings yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recent as $b): ?>
            <tr>
              <td><a href="<?= e(base_url('/admin/bookings')) ?>"><?= e($b['code']) ?></a></td>
              <td><?= e($b['customer_name']) ?></td>
              <td><?= e($b['vehicle_name']) ?></td>
              <td><?= e($b['shop_name'] ?: 'Direct') ?></td>
              <td><?= money($b['paid_amount']) ?></td>
              <td><span class="badge bg-secondary"><?= e($b['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header fw-bold">Top Shops (scan → booking)</div>
      <ul class="list-group list-group-flush">
        <?php if (!$topShops): ?><li class="list-group-item text-muted">No shops yet.</li><?php endif; ?>
        <?php foreach ($topShops as $s):
            $conv = $s['scans'] > 0 ? round($s['bookings'] / $s['scans'] * 100) : 0; ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div><div class="fw-semibold"><?= e($s['name']) ?></div><small class="text-muted"><?= e($s['code']) ?></small></div>
            <div class="text-end"><span class="badge bg-primary"><?= (int)$s['bookings'] ?> bk</span><br><small class="text-muted"><?= (int)$s['scans'] ?> scans · <?= $conv ?>%</small></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_column($series, 'date')) ?>;
const data = <?= json_encode(array_column($series, 'revenue')) ?>;
new Chart(document.getElementById('revChart'), {
  type:'line',
  data:{labels,datasets:[{label:'Revenue (₹)',data,borderColor:'<?= e(setting('primary_color','#0d6efd')) ?>',backgroundColor:'rgba(13,110,253,.1)',fill:true,tension:.3}]},
  options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});
</script>
