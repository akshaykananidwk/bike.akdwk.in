<?php /** @var array $logs */ ?>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
  <thead class="table-light"><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Changes</th><th>IP</th></tr></thead>
  <tbody>
  <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-3">No activity yet.</td></tr><?php endif; ?>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><small><?= e(date('d M h:iA', strtotime($l['created_at']))) ?></small></td>
      <td><?= e($l['user_name'] ?: '—') ?> <span class="badge bg-secondary" style="font-size:9px"><?= e($l['role']) ?></span></td>
      <td><code><?= e($l['action']) ?></code></td>
      <td><?= e($l['entity']) ?><?= $l['entity_id']?' #'.e($l['entity_id']):'' ?></td>
      <td style="max-width:300px"><?php if ($l['old_values'] || $l['new_values']): ?><details><summary class="small text-primary">diff</summary><div class="small"><?php if($l['old_values']):?><div class="text-danger">old: <?= e(mb_substr($l['old_values'],0,150)) ?></div><?php endif;?><?php if($l['new_values']):?><div class="text-success">new: <?= e(mb_substr($l['new_values'],0,150)) ?></div><?php endif;?></div></details><?php endif; ?></td>
      <td><small><?= e($l['ip']) ?></small></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
