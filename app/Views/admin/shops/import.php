<div class="card"><div class="card-header fw-bold">Bulk Import Shops (CSV)</div><div class="card-body">
  <p class="text-muted">Upload a CSV with a header row. Recognised columns:
    <code>name, name_gu, owner_name, mobile, whatsapp, area, city, commission_type, commission_value, code</code>.
    Missing codes are auto-generated (SHOP-DWK-###). Duplicate codes are skipped.</p>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="mb-3"><input type="file" name="csv" accept=".csv,text/csv" class="form-control" required></div>
    <button class="btn btn-primary"><i class="bi bi-upload"></i> Import</button>
    <a href="<?= e(base_url('/admin/shops')) ?>" class="btn btn-outline-secondary">Back</a>
  </form>
  <hr>
  <p class="mb-1 small fw-bold">Sample</p>
  <pre class="bg-light p-2 small">name,name_gu,owner_name,mobile,area,commission_type,commission_value
Krishna Store,કૃષ્ણા સ્ટોર,Ramesh,9876543210,Bus Stand,percent,10
Ocean Hotel,ઓશન હોટેલ,Kiran,9812345678,Beach Road,fixed,50</pre>
</div></div>
