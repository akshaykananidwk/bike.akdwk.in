<?php /** @var array $version,$history; string $repo,$branch,$tokenMask,$keepBackups; bool $autoCheck */ ?>
<div class="row g-3">
  <div class="col-lg-6">
    <form method="post" class="card"><div class="card-header fw-bold">GitHub Configuration</div><div class="card-body">
      <?= csrf_field() ?>
      <div class="mb-2"><label class="form-label">Repository (owner/repo)</label><input name="github_repo" class="form-control" value="<?= e($repo) ?>" placeholder="akshaykananidwk/bike.akdwk.in"></div>
      <div class="mb-2"><label class="form-label">Branch</label><input name="github_branch" class="form-control" value="<?= e($branch) ?>"></div>
      <div class="mb-2"><label class="form-label">Personal Access Token</label><input name="github_token" class="form-control" placeholder="<?= $tokenMask ?: 'ghp_…' ?>"><small class="text-muted">Stored encrypted (AES-256). Current: <?= e($tokenMask ?: 'not set') ?></small></div>
      <div class="row g-2">
        <div class="col-6"><label class="form-label">Keep last N backups</label><input name="update_keep_backups" type="number" class="form-control" value="<?= e($keepBackups) ?>"></div>
        <div class="col-6 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="github_auto_check" value="1" class="form-check-input" id="ac" <?= $autoCheck?'checked':'' ?>><label class="form-check-label" for="ac">Daily auto-check</label></div></div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-outline-secondary" id="testBtn">Test Connection</button>
      </div>
      <div id="testResult" class="small mt-2"></div>
    </div></form>
  </div>

  <div class="col-lg-6">
    <div class="card"><div class="card-header fw-bold">Version &amp; Update</div><div class="card-body">
      <div class="d-flex justify-content-between mb-2">
        <div><div class="text-muted small">Current version</div><div class="fw-bold"><?= e($version['version'] ?? '1.0.0') ?></div></div>
        <div class="text-end"><div class="text-muted small">Commit</div><code><?= e(substr($version['commit'] ?? '', 0, 7) ?: '—') ?></code></div>
      </div>
      <form method="post" action="<?= e(base_url('/admin/updates/migrate')) ?>" class="mb-2" onsubmit="return confirm('Apply any pending database migrations now?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-dark w-100"><i class="bi bi-database-gear"></i> Run DB Migrations (after manual upload)</button>
      </form>
      <button class="btn btn-outline-primary w-100 mb-2" id="checkBtn"><i class="bi bi-arrow-repeat"></i> Check for Update</button>
      <div id="checkResult"></div>
      <button class="btn btn-success w-100 mt-2 d-none" id="updateBtn" onclick="return runUpdate()"><i class="bi bi-download"></i> Update Now</button>
      <div class="mt-3" id="progressWrap" style="display:none">
        <div class="progress mb-2"><div class="progress-bar progress-bar-striped progress-bar-animated" id="progBar" style="width:0%"></div></div>
        <pre id="log" class="bg-dark text-light p-2 small" style="max-height:260px;overflow:auto;border-radius:8px"></pre>
      </div>
    </div></div>
  </div>
</div>

<div class="card mt-3"><div class="card-header fw-bold">Update History</div><div class="table-responsive"><table class="table table-sm mb-0">
  <thead class="table-light"><tr><th>When</th><th>From → To</th><th>Status</th><th>Files backup</th><th>DB backup</th><th>Notes</th></tr></thead>
  <tbody>
  <?php if (!$history): ?><tr><td colspan="6" class="text-center text-muted py-3">No updates yet.</td></tr><?php endif; ?>
  <?php foreach ($history as $h): ?>
    <tr>
      <td><small><?= e(date('d M h:iA', strtotime($h['created_at']))) ?></small></td>
      <td><small><code><?= e(substr($h['from_commit'] ?? '',0,7)) ?></code> → <code><?= e(substr($h['to_commit'] ?? '',0,7)) ?></code></small></td>
      <td><span class="badge bg-<?= $h['status']==='success'?'success':($h['status']==='rolled_back'?'danger':($h['status']==='running'?'warning':'secondary')) ?>"><?= e($h['status']) ?></span></td>
      <td><small><?= e($h['files_backup']) ?></small></td>
      <td><small><?= e($h['db_backup']) ?></small></td>
      <td><small class="text-danger"><?= e(mb_substr((string)$h['log'],0,60)) ?></small></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>

<script>
const CSRF=window.CSRF;
function post(url){ return fetch(url,{method:'POST',headers:{'X-CSRF-Token':CSRF,'X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams({_csrf:CSRF})}); }

document.getElementById('testBtn').onclick=async function(){
  const out=document.getElementById('testResult'); out.textContent='Testing…';
  const r=await (await post('<?= e(base_url('/admin/updates/test')) ?>')).json();
  out.innerHTML=r.ok?'<span class="text-success">✔ Connected: '+r.full_name+(r.private?' (private)':'')+'</span>':'<span class="text-danger">✘ '+r.error+'</span>';
};

document.getElementById('checkBtn').onclick=async function(){
  const out=document.getElementById('checkResult'); out.innerHTML='Checking…';
  const r=await (await post('<?= e(base_url('/admin/updates/check')) ?>')).json();
  if(!r.ok){ out.innerHTML='<div class="alert alert-danger py-2">'+r.error+'</div>'; return; }
  const i=r.info;
  if(!i.update_available){ out.innerHTML='<div class="alert alert-success py-2">You are running the latest version ✔</div>'; document.getElementById('updateBtn').classList.add('d-none'); return; }
  out.innerHTML='<div class="card border-primary"><div class="card-body p-2 small">'
    +'<div class="fw-bold">New version available</div>'
    +'<div>Commit: <code>'+i.short_sha+'</code> by '+ (i.author||'') +'</div>'
    +'<div class="text-muted">'+(i.date||'')+'</div>'
    +'<div class="my-1">'+ (i.message||'').split('\n')[0] +'</div>'
    +'<div>'+i.changed_count+' file(s) changed</div>'
    +(i.changed_files&&i.changed_files.length?'<details><summary>files</summary><div style="max-height:120px;overflow:auto">'+i.changed_files.map(f=>'<div>'+f+'</div>').join('')+'</div></details>':'')
    +'</div></div>';
  document.getElementById('updateBtn').classList.remove('d-none');
};

async function runUpdate(){
  if(!confirm('Start update now? The site will enter maintenance mode.')) return false;
  document.getElementById('progressWrap').style.display='block';
  const log=document.getElementById('log'), bar=document.getElementById('progBar');
  log.textContent=''; let pct=0;
  const res=await fetch('<?= e(base_url('/admin/updates/run')) ?>',{method:'POST',headers:{'X-CSRF-Token':CSRF},body:new URLSearchParams({_csrf:CSRF})});
  const reader=res.body.getReader(); const dec=new TextDecoder();
  let buf='';
  while(true){
    const {done,value}=await reader.read(); if(done) break;
    buf+=dec.decode(value,{stream:true});
    let lines=buf.split('\n'); buf=lines.pop();
    for(const line of lines){ if(!line) continue;
      const [status,...rest]=line.split('|'); const step=rest.join('|');
      log.textContent+= (status==='error'?'✘ ':(status==='done'||status==='success'?'✔ ':'… '))+step+'\n';
      log.scrollTop=log.scrollHeight;
      if(status==='done'||status==='success'){ pct=Math.min(95,pct+12); bar.style.width=pct+'%'; }
      if(status==='result_ok'){ bar.style.width='100%'; bar.classList.remove('progress-bar-animated'); bar.classList.add('bg-success'); setTimeout(()=>location.reload(),1500); }
      if(status==='result_fail'){ bar.classList.add('bg-danger'); }
    }
  }
  return false;
}
</script>
