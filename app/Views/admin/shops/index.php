<?php /** @var array $shops */ ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <form class="d-flex gap-2" method="get">
    <input name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Search name / code / mobile" style="width:240px">
    <button class="btn btn-sm btn-outline-secondary">Search</button>
  </form>
  <div class="d-flex gap-2">
    <a href="<?= e(base_url('/admin/shops/import')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i> Import CSV</a>
    <div class="btn-group btn-group-sm">
      <a href="<?= e(base_url('/admin/shops/posters/bulk?format=pdf&size=A4')) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-zip"></i> Bulk Posters (PDF)</a>
      <a href="<?= e(base_url('/admin/shops/posters/bulk?format=png')) ?>" class="btn btn-outline-success">PNG ZIP</a>
    </div>
    <a href="<?= e(base_url('/admin/shops/create')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Shop</a>
  </div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
  <thead class="table-light"><tr>
    <th>Code</th><th>Name</th><th>Mobile</th><th>Commission</th><th>Scans</th><th>Bookings</th><th>Wallet</th><th>KYC</th><th>Status</th><th></th>
  </tr></thead>
  <tbody>
  <?php if (!$shops): ?><tr><td colspan="10" class="text-center text-muted py-3">No shops yet.</td></tr><?php endif; ?>
  <?php foreach ($shops as $s):
    $conv = $s['scans'] > 0 ? round($s['bookings']/$s['scans']*100) : 0; ?>
    <tr>
      <td><span class="badge bg-dark"><?= e($s['code']) ?></span></td>
      <td><?= e($s['name']) ?><?php if ($s['name_gu']): ?><br><small class="text-muted"><?= e($s['name_gu']) ?></small><?php endif; ?></td>
      <td><?= e($s['mobile']) ?></td>
      <td><?php if ($s['commission_type']==='inherit'): ?><span class="text-muted">Global</span>
          <?php elseif ($s['commission_type']==='percent'): ?><?= e($s['commission_value']) ?>%
          <?php else: ?><?= money($s['commission_value']) ?><?php endif; ?></td>
      <td><?= (int)$s['scans'] ?></td>
      <td><?= (int)$s['bookings'] ?> <small class="text-muted">(<?= $conv ?>%)</small></td>
      <td><?= money($s['wallet'] ?? 0) ?></td>
      <td><span class="badge bg-<?= $s['kyc_status']==='approved'?'success':($s['kyc_status']==='rejected'?'danger':'secondary') ?>"><?= e($s['kyc_status']) ?></span></td>
      <td><span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?>"><?= e($s['status']) ?></span></td>
      <td class="text-nowrap">
        <div class="btn-group btn-group-sm">
          <a class="btn btn-outline-secondary" href="<?= e(base_url('/admin/shops/'.$s['id'].'/qr')) ?>" title="QR"><i class="bi bi-qr-code"></i></a>
          <a class="btn btn-outline-success" href="<?= e(base_url('/admin/shops/'.$s['id'].'/poster?format=pdf&size=A4')) ?>" title="Poster PDF"><i class="bi bi-file-earmark-pdf"></i></a>
          <a class="btn btn-outline-dark" href="<?= e(base_url('/admin/shops/'.$s['id'].'/login-as')) ?>" title="Login as this shop"><i class="bi bi-box-arrow-in-right"></i></a>
          <a class="btn btn-outline-primary" href="<?= e(base_url('/admin/shops/'.$s['id'].'/edit')) ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="<?= e(base_url('/admin/shops/'.$s['id'].'/delete')) ?>" onsubmit="return confirm('Delete this shop?')" class="d-inline">
            <?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
