<?php
/**
 * Application routes. $router is provided by the front controller.
 * Handlers are "Controller@method" resolved under App\Controllers\.
 *
 * Controllers are added across the build phases; routes for not-yet-built
 * controllers are grouped by phase so the file stays readable.
 */

/** @var App\Core\Router $router */

// --- Language switch -------------------------------------------------------
$router->get('/lang/{locale}', 'LangController@switch');

// --- Customer front-end (Phase 3) -----------------------------------------
$router->get('/', 'Front\HomeController@index');
$router->get('/s/{code}', 'Front\ReferralController@scan');           // QR scan attribution
$router->get('/vehicles', 'Front\VehicleController@index');
$router->get('/vehicle/{id}', 'Front\VehicleController@show');
$router->get('/rent/{slug}', 'Front\SeoLandingController@show');   // SEO location pages
$router->get('/packages', 'Front\PackageController@index');
$router->any('/review/{token}', 'Front\ReviewController@show');
$router->get('/reviews', 'Front\ReviewController@index');
$router->get('/package/{slug}', 'Front\PackageController@show');
$router->post('/package/{slug}/book', 'Front\PackageController@book');
$router->get('/page/{slug}', 'Front\PageController@show');

// Booking wizard + OTP + payment (Phase 3/4)
$router->any('/book/{id}', 'Front\BookingController@start');
$router->post('/book/{id}/availability', 'Front\BookingController@availability');
$router->post('/book/{id}/quote', 'Front\BookingController@quote');
$router->post('/book/{id}/coupon', 'Front\BookingController@applyCoupon');
$router->any('/checkout/{code}', 'Front\BookingController@checkout');
$router->post('/otp/send', 'Front\OtpController@send');
$router->post('/otp/verify', 'Front\OtpController@verify');
$router->get('/booking/{code}/success', 'Front\BookingController@success');
$router->get('/booking/{code}/invoice', 'Front\BookingController@invoice');
$router->any('/my-bookings', 'Front\BookingController@myBookings');

// Payments (Phase 4)
$router->any('/pay/{code}/razorpay/create', 'Front\PaymentController@razorpayCreate');
$router->post('/pay/{code}/razorpay/callback', 'Front\PaymentController@razorpayCallback');
$router->post('/webhook/razorpay', 'Front\PaymentController@razorpayWebhook');
$router->any('/pay/{code}/upi', 'Front\PaymentController@upi');
$router->any('/pay/{code}/cash', 'Front\PaymentController@cash');

// WhatsApp inbound webhook (Phase 6)
$router->post('/api/whatsapp_inbound', 'Api\WhatsappController@inbound');

// --- Admin panel (Phase 2+) -----------------------------------------------
$router->any('/admin/login', 'Admin\AuthController@login');
$router->get('/admin/logout', 'Admin\AuthController@logout');
// Return to admin from an impersonated (login-as) session — not behind AdminAuth.
$router->get('/admin/return', 'Admin\AuthController@returnToAdmin');
$router->group(['prefix' => '/admin', 'middleware' => ['AdminAuth']], function ($r) {
    $r->get('/', 'Admin\DashboardController@index');
    $r->get('/dashboard', 'Admin\DashboardController@index');

    // Shops
    $r->get('/shops', 'Admin\ShopController@index');
    $r->any('/shops/create', 'Admin\ShopController@create');
    $r->any('/shops/{id}/edit', 'Admin\ShopController@edit');
    $r->post('/shops/{id}/delete', 'Admin\ShopController@delete');
    $r->any('/shops/import', 'Admin\ShopController@import');
    $r->get('/shops/{id}/login-as', 'Admin\AuthController@loginAsShop');
    $r->get('/agencies/{id}/login-as', 'Admin\AuthController@loginAsAgency');
    $r->get('/shops/{id}/qr', 'Admin\ShopController@qr');
    $r->get('/shops/{id}/poster', 'Admin\ShopController@poster');
    $r->get('/shops/posters/bulk', 'Admin\ShopController@bulkPosters');

    // Agencies
    $r->get('/agencies', 'Admin\AgencyController@index');
    $r->any('/agencies/create', 'Admin\AgencyController@create');
    $r->any('/agencies/{id}/edit', 'Admin\AgencyController@edit');
    $r->post('/agencies/{id}/delete', 'Admin\AgencyController@delete');

    // Packages
    $r->get('/packages', 'Admin\PackageController@index');
    $r->any('/packages/create', 'Admin\PackageController@create');
    $r->any('/packages/{id}/edit', 'Admin\PackageController@edit');
    $r->post('/packages/{id}/delete', 'Admin\PackageController@delete');

    // Reviews
    $r->get('/reviews', 'Admin\ReviewController@index');
    $r->post('/reviews/{id}/moderate', 'Admin\ReviewController@moderate');

    // Categories & Vehicles
    $r->any('/categories', 'Admin\CategoryController@index');
    $r->get('/vehicles', 'Admin\VehicleController@index');
    $r->any('/vehicles/create', 'Admin\VehicleController@create');
    $r->any('/vehicles/{id}/edit', 'Admin\VehicleController@edit');
    $r->post('/vehicles/{id}/delete', 'Admin\VehicleController@delete');

    // Bookings
    $r->get('/bookings', 'Admin\BookingController@index');
    $r->get('/bookings/{id}', 'Admin\BookingController@show');
    $r->any('/bookings/create', 'Admin\BookingController@create');
    $r->post('/bookings/{id}/status', 'Admin\BookingController@updateStatus');

    // Payments & payouts
    $r->get('/payments', 'Admin\PaymentController@index');
    $r->any('/payments/gateways', 'Admin\PaymentController@gateways');
    $r->post('/payments/test-gateway', 'Admin\PaymentController@testGateway');
    $r->post('/payments/{id}/verify', 'Admin\PaymentController@verify');
    $r->get('/payouts', 'Admin\PayoutController@index');
    $r->post('/payouts/{id}/process', 'Admin\PayoutController@process');

    // Reports, coupons, WhatsApp, settings, staff, updates
    $r->get('/reports', 'Admin\ReportController@index');
    $r->any('/coupons', 'Admin\CouponController@index');
    $r->any('/whatsapp', 'Admin\WhatsappController@settings');
    $r->any('/whatsapp/templates', 'Admin\WhatsappController@templates');
    $r->get('/whatsapp/queue', 'Admin\WhatsappController@queue');
    $r->get('/whatsapp/inbox', 'Admin\WhatsappController@inbox');
    $r->post('/whatsapp/test', 'Admin\WhatsappController@test');
    $r->any('/settings', 'Admin\SettingController@index');
    $r->any('/staff', 'Admin\StaffController@index');
    $r->get('/activity', 'Admin\ActivityController@index');
    $r->get('/backup', 'Admin\BackupController@download');
    $r->any('/updates', 'Admin\UpdateController@index');
    $r->post('/updates/check', 'Admin\UpdateController@check');
    $r->post('/updates/migrate', 'Admin\UpdateController@migrate');
    $r->post('/updates/test', 'Admin\UpdateController@testConnection');
    $r->post('/updates/run', 'Admin\UpdateController@run');
});

// --- Shop partner panel (Phase 5) -----------------------------------------
$router->any('/shop/register', 'Shop\RegisterController@register');
$router->any('/shop/login', 'Shop\AuthController@login');
$router->get('/shop/logout', 'Shop\AuthController@logout');
$router->group(['prefix' => '/shop', 'middleware' => ['ShopAuth']], function ($r) {
    $r->get('/', 'Shop\DashboardController@index');
    $r->get('/bookings', 'Shop\DashboardController@bookings');
    $r->get('/qr', 'Shop\DashboardController@qr');
    $r->get('/poster', 'Shop\DashboardController@poster');
    $r->any('/wallet', 'Shop\WalletController@index');
    $r->any('/withdraw', 'Shop\WalletController@withdraw');
    $r->any('/bank', 'Shop\WalletController@bank');
    $r->get('/statement', 'Shop\WalletController@statement');
    $r->any('/profile', 'Shop\DashboardController@profile');
});

// --- Agency panel (Phase 5) -----------------------------------------------
$router->any('/agency/register', 'Agency\RegisterController@register');
$router->any('/agency/login', 'Agency\AuthController@login');
$router->get('/agency/logout', 'Agency\AuthController@logout');
$router->group(['prefix' => '/agency', 'middleware' => ['AgencyAuth']], function ($r) {
    $r->get('/', 'Agency\DashboardController@index');
    $r->get('/vehicles', 'Agency\VehicleController@index');
    $r->post('/vehicles/{id}/toggle', 'Agency\VehicleController@toggle');
    $r->get('/bookings', 'Agency\DashboardController@bookings');
    $r->post('/bookings/{id}/pickup', 'Agency\DashboardController@pickup');
    $r->post('/bookings/{id}/return', 'Agency\DashboardController@markReturn');
    $r->any('/wallet', 'Agency\WalletController@index');
    $r->any('/withdraw', 'Agency\WalletController@withdraw');
    $r->any('/profile', 'Agency\DashboardController@profile');
});

// --- SEO ------------------------------------------------------------------
$router->get('/sitemap.xml', 'Front\SeoController@sitemap');
$router->get('/robots.txt', 'Front\SeoController@robots');

// --- PWA ------------------------------------------------------------------
$router->get('/manifest.webmanifest', 'Front\PwaController@manifest');
$router->get('/sw.js', 'Front\PwaController@serviceWorker');
