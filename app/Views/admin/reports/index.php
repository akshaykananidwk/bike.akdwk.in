<?php /** @var array $report,$summary; string $from,$to,$type */
$types = ['summary'=>'Daily Revenue','monthly'=>'Monthly Revenue','shop'=>'Shop-wise','agency'=>'Agency-wise','vehicle'=>'Vehicle Utilisation','gst'=>'GST'];
$qs = fn($extra) => http_build_query(array_merge(['from'=>$from,'to'=>$to,'type'=>$type], $extra));
?>
<form method="get" class="card card-body mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-3"><label class="form-label small">Report</label><select name="type" class="form-select form-select-sm"><?php foreach ($types as $k=>$l): ?><option value="<?= $k ?>" <?= $type===$k?'selected':'' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-3"><label class="form-label small">From</label><input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-3"><label class="form-label small">To</label><input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-3"><button class="btn btn-sm btn-primary">Run</button>
      <a href="?<?= e($qs(['export'=>'csv'])) ?>" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel"></i> CSV</a>
      <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> PDF</button>
    </div>
  </div>
</form>

<div class="row g-2 mb-3">
  <div class="col-6 col-md-3"><div class="stat-card" style="background:#0d6efd"><div class="small opacity-75">Bookings</div><div class="fs-4 fw-bold"><?= $summary['bookings'] ?></div></div></div>
  <div class="col-6 col-md-3"><div class="stat-card" style="background:#20c997"><div class="small opacity-75">Revenue</div><div class="fs-5 fw-bold"><?= money($summary['revenue']) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="stat-card" style="background:#f59e0b"><div class="small opacity-75">Shop Commission</div><div class="fs-5 fw-bold"><?= money($summary['commission']) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="stat-card" style="background:#8b5cf6"><div class="small opacity-75">GST Collected</div><div class="fs-5 fw-bold"><?= money($summary['gst']) ?></div></div></div>
</div>

<div class="card"><div class="card-header fw-bold"><?= e($report['title']) ?></div><div class="table-responsive">
<table class="table table-sm table-striped mb-0">
  <thead class="table-light"><tr><?php foreach ($report['columns'] as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php if (!$report['rows']): ?><tr><td colspan="<?= count($report['columns']) ?>" class="text-center text-muted py-3">No data for this range.</td></tr><?php endif; ?>
  <?php foreach ($report['rows'] as $row): ?>
    <tr><?php foreach ($row as $k=>$v): ?><td><?= is_numeric($v) && (stripos($k,'revenue')!==false||stripos($k,'commission')!==false||stripos($k,'settlement')!==false||stripos($k,'gst')!==false||stripos($k,'total')!==false||stripos($k,'base')!==false) ? money($v) : e($v) ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
