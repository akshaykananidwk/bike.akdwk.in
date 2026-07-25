<?php
/** Customer-facing layout — modern, mobile-first, SEO-rich. Expects: $title, $content, optional $meta, $splash. */
use App\Core\Lang;
use App\Core\Session;
$primary   = setting('primary_color', '#0d6efd');
$secondary = setting('secondary_color', '#20c997');
$siteName  = setting('site_name', 'Dwarka Rental');
$refShop   = Session::get('ref_shop_name');
$desc      = 'Rent a bike, scooty, Activa, car, tempo traveller or cycle in Dwarka. Instant online booking, verified vehicles, best price, doorstep pickup near Dwarkadhish Temple, Gomti Ghat, Beyt Dwarka & Nageshwar. બાઇક, સ્કૂટી, એક્ટિવા અને કાર ભાડે — દ્વારકા.';
$keywords  = 'bike rent in dwarka, bike rental dwarka, rent bike near me, two wheeler rental dwarka, scooty rent dwarka, activa on rent dwarka, car rental dwarka, self drive car dwarka, tempo traveller dwarka, cycle rental dwarka, bike hire dwarka, devbhoomi dwarka vehicle rental, beyt dwarka bike rent, nageshwar bike rental, gomti ghat, dwarkadhish temple, okha, mithapur, द्वारका बाइक रेंट, દ્વારકા બાઇક ભાડે, દ્વારકા સ્કૂટી ભાડે, દ્વારકા કાર ભાડે';
$showSplash = $splash ?? false;
$baseUrl   = rtrim(setting('site_url', '') ?: guess_base_url(), '/');
?>
<!doctype html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="<?= e($primary) ?>">
<title><?= e($title ?? ($siteName . ' — Bike, Scooty & Car Rental in Dwarka')) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="keywords" content="<?= e($keywords) ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= e($baseUrl . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<!-- Open Graph / social -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($title ?? $siteName) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:image" content="<?= e(setting('logo') ? upload_url(setting('logo')) : $baseUrl . '/assets/img/og.png') ?>">
<meta name="twitter:card" content="summary_large_image">
<?= $meta ?? '' ?>
<!-- LocalBusiness + WebSite structured data (helps local Google ranking) -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"AutoRental","name":<?= json_encode($siteName) ?>,"image":<?= json_encode(setting('logo') ? upload_url(setting('logo')) : '') ?>,"url":<?= json_encode($baseUrl) ?>,"telephone":<?= json_encode(setting('contact_mobile', '')) ?>,"priceRange":"₹₹","areaServed":"Devbhoomi Dwarka, Gujarat","address":{"@type":"PostalAddress","addressLocality":"Dwarka","addressRegion":"Gujarat","addressCountry":"IN"},"geo":{"@type":"GeoCoordinates","latitude":22.2394,"longitude":68.9678},"openingHours":"Mo-Su 06:00-23:00"}
</script>
<link rel="manifest" href="<?= e(base_url('/manifest.webmanifest')) ?>">
<!-- Self-hosted assets (fast, no CDN dependency, works offline via PWA) -->
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Noto+Sans+Gujarati:wght@400;600;700&display=swap" rel="stylesheet">
<style>
 :root{
   --p:<?= e($primary) ?>; --s:<?= e($secondary) ?>;
   --saffron:#ff8f1f; --gold:#ffc44d; --ink:#141b2d; --muted:#6b7280;
   --bg:#f6f8fc; --card:#ffffff; --ring:0 8px 30px rgba(20,27,45,.08);
 }
 *{box-sizing:border-box}
 html,body{overflow-x:hidden;max-width:100%}
 body{font-family:'Poppins','Noto Sans Gujarati',system-ui,sans-serif;background:var(--bg);color:var(--ink);margin:0;padding-bottom:76px;-webkit-tap-highlight-color:transparent}
 .form-control,.form-select{min-width:0}
 input[type=datetime-local]{max-width:100%}
 h1,h2,h3,h4,h5{font-weight:700;letter-spacing:-.02em}
 a{text-decoration:none}
 .btn{border-radius:12px;font-weight:600}
 .btn-primary{background:var(--p);border-color:var(--p)}
 .btn-primary:hover{filter:brightness(.94)}
 .text-primary{color:var(--p)!important}
 .tap{min-height:46px;display:inline-flex;align-items:center;justify-content:center}
 .shadow-soft{box-shadow:var(--ring)}
 /* Splash */
 #jsplash{position:fixed;inset:0;z-index:3000;display:flex;flex-direction:column;align-items:center;justify-content:center;
   background:radial-gradient(circle at 50% 30%, #2a1a5e, #10122b 70%);color:#fff;text-align:center;animation:splashOut .6s ease 1.9s forwards}
 #jsplash .mandala{width:120px;height:120px;border-radius:50%;border:3px dashed var(--gold);display:flex;align-items:center;justify-content:center;font-size:52px;animation:spin 9s linear infinite,pop .6s ease}
 #jsplash h1{font-size:34px;margin:18px 0 4px;background:linear-gradient(90deg,var(--gold),#fff,var(--saffron));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;animation:pop .7s ease .1s both}
 #jsplash p{opacity:.8;margin:0;font-size:14px}
 @keyframes spin{to{transform:rotate(360deg)}}
 @keyframes pop{from{transform:scale(.6);opacity:0}to{transform:scale(1);opacity:1}}
 @keyframes splashOut{to{opacity:0;visibility:hidden}}
 /* Nav */
 .topbar{background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border-bottom:1px solid #eef1f6;position:sticky;top:0;z-index:1030}
 .brand{font-weight:800;font-size:20px;background:linear-gradient(90deg,var(--p),var(--s));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
 .jai{font-family:'Noto Sans Gujarati',sans-serif;font-weight:700;color:var(--saffron);font-size:13px}
 .ref-banner{background:linear-gradient(90deg,var(--s),var(--p));color:#fff;text-align:center;padding:7px;font-size:13px;font-weight:600}
 /* Cards */
 .veh-card{border:1px solid #eef1f6;border-radius:18px;overflow:hidden;background:var(--card);transition:.2s;box-shadow:var(--ring)}
 .veh-card:hover{transform:translateY(-4px)}
 .veh-card .imgwrap{position:relative;aspect-ratio:4/3;overflow:hidden;background:#eef2f8}
 .veh-card img{width:100%;height:100%;object-fit:cover;transition:.3s}
 .veh-card:hover img{transform:scale(1.06)}
 .pill{border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600}
 .price-tag{color:var(--p);font-weight:800}
 .wa-float{position:fixed;right:16px;bottom:86px;background:#25d366;color:#fff;width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 8px 22px rgba(37,211,102,.45);z-index:1050}
 .bottombar{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #eef1f6;display:flex;z-index:1040}
 .bottombar a{flex:1;text-align:center;padding:8px 2px;color:var(--muted);font-size:11px;font-weight:600}
 .bottombar a.active{color:var(--p)}
 .bottombar a i{display:block;font-size:20px;margin-bottom:2px}
 .section-title{font-size:20px;font-weight:800}
 .chip{background:#fff;border:1px solid #eef1f6;border-radius:14px;padding:12px 8px;text-align:center;box-shadow:var(--ring);transition:.15s;display:block;color:var(--ink)}
 .chip:hover{transform:translateY(-3px);border-color:var(--p)}
 .chip i{font-size:26px;color:var(--p)}
</style>
</head>
<body>
<?php if ($showSplash): ?>
<div id="jsplash">
  <div class="mandala">🛕</div>
  <h1>જય દ્વારકાધીશ</h1>
  <p>🙏 Welcome to <?= e($siteName) ?></p>
</div>
<?php endif; ?>

<?php if ($refShop): ?>
  <div class="ref-banner"><i class="bi bi-shop"></i> <?= e(__('booking_via', ['shop' => $refShop])) ?></div>
<?php endif; ?>

<nav class="topbar">
  <div class="container d-flex align-items-center justify-content-between py-2">
    <a href="<?= e(base_url('/')) ?>" class="d-flex flex-column">
      <span class="brand"><?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" style="height:30px;vertical-align:middle"> <?php else: ?>🛵 <?php endif; ?><?= e($siteName) ?></span>
      <span class="jai">🙏 જય દ્વારકાધીશ</span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <div class="btn-group btn-group-sm">
        <a href="<?= e(base_url('/lang/en')) ?>" class="btn btn-outline-secondary <?= Lang::locale()==='en'?'active':'' ?>">EN</a>
        <a href="<?= e(base_url('/lang/gu')) ?>" class="btn btn-outline-secondary <?= Lang::locale()==='gu'?'active':'' ?>">ગુ</a>
      </div>
      <a href="<?= e(base_url('/my-bookings')) ?>" class="btn btn-sm btn-outline-primary tap" title="My Bookings"><i class="bi bi-bag-check"></i></a>
    </div>
  </div>
</nav>

<main>
  <?php if ($f = Session::flash('success')): ?><div class="container mt-3"><div class="alert alert-success shadow-soft"><?= e($f) ?></div></div><?php endif; ?>
  <?php if ($f = Session::flash('error')): ?><div class="container mt-3"><div class="alert alert-danger shadow-soft"><?= e($f) ?></div></div><?php endif; ?>
  <?= $content ?>
</main>

<?php if ($wa = setting('contact_whatsapp')): ?>
  <a class="wa-float" href="https://wa.me/91<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>

<nav class="bottombar">
  <a href="<?= e(base_url('/')) ?>"><i class="bi bi-house-door"></i>Home</a>
  <a href="<?= e(base_url('/vehicles')) ?>"><i class="bi bi-scooter"></i>Vehicles</a>
  <?php if ($wa = setting('contact_whatsapp')): ?><a href="https://wa.me/91<?= e($wa) ?>" target="_blank"><i class="bi bi-whatsapp"></i>Chat</a><?php endif; ?>
  <?php if ($m = setting('contact_mobile')): ?><a href="tel:<?= e($m) ?>"><i class="bi bi-telephone"></i>Call</a><?php endif; ?>
  <a href="<?= e(base_url('/my-bookings')) ?>"><i class="bi bi-bag-check"></i>Bookings</a>
</nav>

<footer class="text-center small text-muted py-4" style="margin-bottom:60px">
  <div class="jai mb-2" style="font-size:15px">🙏 જય દ્વારકાધીશ</div>
  <a href="<?= e(base_url('/page/terms')) ?>" class="text-muted">Terms</a> ·
  <a href="<?= e(base_url('/page/privacy')) ?>" class="text-muted">Privacy</a> ·
  <a href="<?= e(base_url('/page/cancellation')) ?>" class="text-muted">Cancellation</a> ·
  <a href="<?= e(base_url('/page/about')) ?>" class="text-muted">About</a>
  <div class="mt-2">© <?= date('Y') ?> <?= e($siteName) ?> · Bike, Scooty &amp; Car rental in Dwarka</div>
</footer>

<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>
<script>
window.CSRF=document.querySelector('meta[name=csrf-token]').content;
<?php if ($showSplash): ?>setTimeout(function(){var s=document.getElementById('jsplash');if(s)s.remove();},2600);<?php endif; ?>
if('serviceWorker' in navigator){navigator.serviceWorker.register('<?= e(base_url('/sw.js')) ?>').catch(function(){});}
</script>
</body>
</html>
