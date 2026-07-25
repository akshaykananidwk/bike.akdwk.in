<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\ActivityLog;

class PackageController extends Controller
{
    public function index(): string
    {
        $packages = Database::fetchAll(
            "SELECT p.*, a.name AS agency_name,
                    (SELECT COUNT(*) FROM {p}bookings b WHERE b.package_id=p.id) AS bookings
             FROM {p}packages p LEFT JOIN {p}agencies a ON a.id=p.agency_id
             ORDER BY p.sort_order, p.id"
        );
        return $this->view('admin/packages/index', [
            'title' => 'Tour Packages', 'active' => 'packages', 'packages' => $packages,
        ], 'admin');
    }

    public function create(): string
    {
        if (Request::isPost()) { $this->verifyCsrf(); return $this->save(null); }
        return $this->view('admin/packages/form', $this->formData(null) + ['title' => 'Add Package', 'active' => 'packages'], 'admin');
    }

    public function edit(array $p): string
    {
        $pkg = Database::fetch("SELECT * FROM {p}packages WHERE id=?", [$p['id']]);
        if (!$pkg) { Session::flash('error', 'Package not found.'); return $this->redirect('/admin/packages'); }
        if (Request::isPost()) { $this->verifyCsrf(); return $this->save($pkg); }
        return $this->view('admin/packages/form', $this->formData($pkg) + ['title' => 'Edit Package', 'active' => 'packages'], 'admin');
    }

    private function formData(?array $pkg): array
    {
        return [
            'pkg'      => $pkg,
            'agencies' => Database::fetchAll("SELECT id,name FROM {p}agencies WHERE status='active' ORDER BY name"),
            'code'     => $pkg['code'] ?? $this->nextCode(),
        ];
    }

    private function save(?array $pkg): string
    {
        $name = trim((string)Request::post('name'));
        if ($name === '') {
            Session::flash('error', 'Package name is required.');
            return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/packages');
        }

        $fields = [
            'name'            => $name,
            'type'            => in_array(Request::post('type'), ['darshan','outstation','transfer','custom'], true) ? Request::post('type') : 'darshan',
            'short_desc'      => Request::post('short_desc') ?: null,
            'description'     => Request::post('description') ?: null,
            'places'          => Request::post('places') ?: null,
            'from_location'   => Request::post('from_location') ?: null,
            'to_location'     => Request::post('to_location') ?: null,
            'duration_text'   => Request::post('duration_text') ?: null,
            'included_km'     => Request::post('included_km') !== '' ? (int)Request::post('included_km') : null,
            'extra_km_rate'   => (float)Request::post('extra_km_rate', 0),
            'vehicle_type'    => Request::post('vehicle_type') ?: null,
            'seats'           => Request::post('seats') !== '' ? (int)Request::post('seats') : null,
            'price'           => (float)Request::post('price', 0),
            'strike_price'    => Request::post('strike_price') !== '' ? (float)Request::post('strike_price') : null,
            'advance_percent' => (float)Request::post('advance_percent', 100),
            'agency_id'       => Request::post('agency_id') ?: null,
            'inclusions'      => Request::post('inclusions') ?: null,
            'exclusions'      => Request::post('exclusions') ?: null,
            'sort_order'      => (int)Request::post('sort_order', 0),
            'status'          => Request::post('status', 'active'),
        ];

        $img = Request::file('image');
        if ($img && ($img['error'] ?? 1) === UPLOAD_ERR_OK) {
            $r = Uploader::handle($img, 'packages', ['jpg','jpeg','png','webp']);
            if ($r['ok']) { $fields['image'] = $r['path']; }
        }

        if ($pkg) {
            Database::update('packages', $fields, ['id' => $pkg['id']]);
            ActivityLog::record('package.update', 'package', $pkg['id']);
            Session::flash('success', 'Package updated.');
        } else {
            $fields['code'] = $this->nextCode();
            $fields['slug'] = $this->uniqueSlug($name);
            $id = Database::insert('packages', $fields);
            ActivityLog::record('package.create', 'package', $id);
            Session::flash('success', 'Package created.');
        }
        return $this->redirect('/admin/packages');
    }

    public function delete(array $p): string
    {
        $this->verifyCsrf();
        Database::delete('packages', ['id' => (int)$p['id']]);
        ActivityLog::record('package.delete', 'package', $p['id']);
        Session::flash('success', 'Package deleted.');
        return $this->redirect('/admin/packages');
    }

    private function nextCode(): string
    {
        $last = Database::scalar("SELECT code FROM {p}packages WHERE code LIKE 'PKG-%' ORDER BY id DESC LIMIT 1");
        $n = 0;
        if ($last && preg_match('/(\d+)$/', $last, $m)) { $n = (int)$m[1]; }
        return sprintf('PKG-%03d', $n + 1);
    }

    private function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'package';
        $slug = $base; $i = 1;
        while (Database::scalar("SELECT COUNT(*) FROM {p}packages WHERE slug=?", [$slug])) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
