<?php /** @var array $messages */ ?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp')) ?>">Settings</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/templates')) ?>">Templates</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/queue')) ?>">Queue</a></li>
  <li class="nav-item"><a class="nav-link active" href="<?= e(base_url('/admin/whatsapp/inbox')) ?>">Inbox</a></li>
</ul>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
  <thead class="table-light"><tr><th>From</th><th>Message</th><th>Received</th></tr></thead>
  <tbody>
  <?php if (!$messages): ?><tr><td colspan="3" class="text-center text-muted py-3">No inbound messages. Point your WhatsApp API's inbound webhook to <code><?= e(base_url('/api/whatsapp_inbound')) ?></code>.</td></tr><?php endif; ?>
  <?php foreach ($messages as $m): ?>
    <tr><td><?= e($m['from_number']) ?></td><td><?= e($m['message']) ?></td><td><small><?= e(date('d M h:iA', strtotime($m['created_at']))) ?></small></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
