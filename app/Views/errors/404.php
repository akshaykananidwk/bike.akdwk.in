<?php /** 404 page */ ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 — Not Found</title>
<style>body{font-family:system-ui,'Noto Sans Gujarati',sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;background:#0f172a;color:#e2e8f0;text-align:center}
.box{padding:24px}h1{font-size:72px;margin:0;color:#0d6efd}a{color:#20c997}</style>
</head><body>
<div class="box">
  <h1>404</h1>
  <p>The page you are looking for was not found.</p>
  <p><a href="<?= is_file(BASE_PATH.'/config/config.php') ? e(base_url('/')) : '/' ?>">Go home</a></p>
</div>
</body></html>
