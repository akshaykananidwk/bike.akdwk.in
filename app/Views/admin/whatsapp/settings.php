<?php /** @var array $masked,$s; bool $enabled */ ?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link active" href="<?= e(base_url('/admin/whatsapp')) ?>">Settings</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/templates')) ?>">Templates</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/queue')) ?>">Queue</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/inbox')) ?>">Inbox</a></li>
</ul>

<div class="row g-3">
  <div class="col-lg-7">
    <form method="post" class="card"><div class="card-header fw-bold">API Configuration <small class="text-muted">(encrypted at rest)</small></div><div class="card-body">
      <?= csrf_field() ?>
      <div class="mb-2"><label class="form-label">API URL</label><input name="wa_api_url" class="form-control" placeholder="<?= $masked['wa_api_url'] ?: 'https://wa.example.com/api/send' ?>"></div>
      <div class="mb-2"><label class="form-label">API Key</label><input name="wa_api_key" class="form-control" placeholder="<?= $masked['wa_api_key'] ?: 'enter key' ?>"><small class="text-muted">Current: <?= e($masked['wa_api_key'] ?: 'not set') ?></small></div>
      <div class="mb-2"><label class="form-label">Session ID</label><input name="wa_session_id" class="form-control" placeholder="<?= $masked['wa_session_id'] ?: 'session' ?>"></div>
      <div class="mb-2"><label class="form-label">Sender Number</label><input name="wa_sender" class="form-control" placeholder="<?= $masked['wa_sender'] ?: '9198xxxxxxxx' ?>"></div>
      <div class="mb-2"><label class="form-label">Inbound Webhook Secret</label><input name="wa_inbound_secret" class="form-control" value="<?= e($s['wa_inbound_secret'] ?? '') ?>"><small class="text-muted">Used to verify POSTs to <?= e(base_url('/api/whatsapp_inbound')) ?></small></div>
      <hr>
      <div class="row g-2">
        <div class="col-6"><label class="form-label">Daily Limit</label><input name="wa_daily_limit" type="number" class="form-control" value="<?= e($s['wa_daily_limit'] ?? 500) ?>"></div>
        <div class="col-3"><label class="form-label">Min Delay (s)</label><input name="wa_min_delay" type="number" class="form-control" value="<?= e($s['wa_min_delay'] ?? 3) ?>"></div>
        <div class="col-3"><label class="form-label">Max Delay (s)</label><input name="wa_max_delay" type="number" class="form-control" value="<?= e($s['wa_max_delay'] ?? 8) ?>"></div>
      </div>
      <div class="form-check form-switch mt-3"><input type="checkbox" name="wa_enabled" value="1" class="form-check-input" id="wae" <?= $enabled?'checked':'' ?>><label class="form-check-label" for="wae"><strong>Enable WhatsApp sending</strong> (queue dispatched by cron)</label></div>
      <button class="btn btn-primary mt-3">Save Settings</button>
    </form></div>
  </div>
  <div class="col-lg-5">
    <div class="card"><div class="card-header fw-bold">Send Test Message</div><div class="card-body">
      <div class="mb-2"><label class="form-label">To (mobile)</label><input id="testTo" class="form-control"></div>
      <div class="mb-2"><label class="form-label">Message</label><textarea id="testMsg" class="form-control" rows="3">Test message from Dwarka Rental ✔</textarea></div>
      <button class="btn btn-success" id="testBtn"><i class="bi bi-send"></i> Send Test</button>
      <div id="testResult" class="mt-2 small"></div>
    </div></div>
    <div class="card mt-3"><div class="card-body small">
      <div class="fw-bold mb-1">Cron setup</div>
      <code>* * * * * php <?= e(BASE_PATH) ?>/cron.php</code>
      <p class="text-muted mt-1 mb-0">Dispatches the queue, sends reminders, and auto-cancels unpaid bookings.</p>
    </div></div>
  </div>
</div>
<script>
document.getElementById('testBtn').onclick=async function(){
  const out=document.getElementById('testResult'); out.textContent='Sending…';
  const fd=new FormData(); fd.append('_csrf',window.CSRF); fd.append('to',document.getElementById('testTo').value); fd.append('message',document.getElementById('testMsg').value);
  const r=await fetch('<?= e(base_url('/admin/whatsapp/test')) ?>',{method:'POST',headers:{'X-CSRF-Token':window.CSRF,'X-Requested-With':'XMLHttpRequest'},body:fd});
  const d=await r.json();
  out.innerHTML=d.ok?'<span class="text-success">✔ Sent (HTTP '+d.http+')</span><br><pre class="small">'+(d.response||'')+'</pre>':'<span class="text-danger">✘ '+(d.error||('HTTP '+d.http))+'</span><br><pre class="small">'+(d.response||'')+'</pre>';
};
</script>
