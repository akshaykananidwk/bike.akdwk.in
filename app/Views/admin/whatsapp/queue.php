<?php /** @var array $queue,$stats */ ?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp')) ?>">Settings</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/templates')) ?>">Templates</a></li>
  <li class="nav-item"><a class="nav-link active" href="<?= e(base_url('/admin/whatsapp/queue')) ?>">Queue</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/inbox')) ?>">Inbox</a></li>
</ul>
<div class="row g-2 mb-3">
  <div class="col-4"><div class="stat-card" style="background:#f59e0b"><div class="small opacity-75">Pending</div><div class="fs-4 fw-bold"><?= $stats['pending'] ?></div></div></div>
  <div class="col-4"><div class="stat-card" style="background:#20c997"><div class="small opacity-75">Sent</div><div class="fs-4 fw-bold"><?= $stats['sent'] ?></div></div></div>
  <div class="col-4"><div class="stat-card" style="background:#dc2626"><div class="small opacity-75">Failed</div><div class="fs-4 fw-bold"><?= $stats['failed'] ?></div></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
  <thead class="table-light"><tr><th>#</th><th>To</th><th>Template</th><th>Message</th><th>Status</th><th>Attempts</th><th>Scheduled</th></tr></thead>
  <tbody>
  <?php if (!$queue): ?><tr><td colspan="7" class="text-center text-muted py-3">Queue empty.</td></tr><?php endif; ?>
  <?php foreach ($queue as $q): ?>
    <tr>
      <td><?= (int)$q['id'] ?></td>
      <td><?= e($q['to_number']) ?></td>
      <td><small><?= e($q['template_key']) ?></small></td>
      <td style="max-width:280px"><small><?= e(mb_substr($q['message'],0,80)) ?></small></td>
      <td><span class="badge bg-<?= $q['status']==='sent'?'success':($q['status']==='failed'?'danger':'warning') ?>"><?= e($q['status']) ?></span><?php if ($q['last_error']): ?><br><small class="text-danger"><?= e(mb_substr($q['last_error'],0,40)) ?></small><?php endif; ?></td>
      <td><?= (int)$q['attempts'] ?></td>
      <td><small><?= e(date('d M h:iA', strtotime($q['scheduled_at']))) ?></small></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
