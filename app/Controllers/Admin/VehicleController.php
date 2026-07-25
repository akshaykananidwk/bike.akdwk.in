<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\ActivityLog;

class VehicleController extends Controller
{
    public function index(): string
    {
        $vehicles = Database::fetchAll(
            "SELECT v.*, c.name AS category_name, a.name AS agency_name
             FROM {p}vehicles v
             LEFT JOIN {p}categories c ON c.id=v.category_id
             LEFT JOIN {p}agencies a ON a.id=v.agency_id
             ORDER BY v.id DESC"
        );
        return $this->view('admin/vehicles/index', ['title' => 'Vehicles', 'active' => 'vehicles', 'vehicles' => $vehicles], 'admin');
    }

    public function create(): string
    {
        if (Request::isPost()) { $this->verifyCsrf(); return $this->save(null); }
        return $this->view('admin/vehicles/form', $this->formData(null) + ['title' => 'Add Vehicle', 'active' => 'vehicles'], 'admin');
    }

    public function edit(array $p): string
    {
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$p['id']]);
        if (!$vehicle) { Session::flash('error', 'Vehicle not found.'); return $this->redirect('/admin/vehicles'); }
        if (Request::isPost()) { $this->verifyCsrf(); return $this->save($vehicle); }
        return $this->view('admin/vehicles/form', $this->formData($vehicle) + ['title' => 'Edit Vehicle', 'active' => 'vehicles'], 'admin');
    }

    private function formData(?array $vehicle): array
    {
        return [
            'vehicle'    => $vehicle,
            'categories' => Database::fetchAll("SELECT * FROM {p}categories WHERE status='active' ORDER BY sort_order"),
            'agencies'   => Database::fetchAll("SELECT * FROM {p}agencies WHERE status='active' ORDER BY name"),
            'images'     => $vehicle ? Database::fetchAll("SELECT * FROM {p}vehicle_images WHERE vehicle_id=? ORDER BY sort_order", [$vehicle['id']]) : [],
        ];
    }

    private function save(?array $vehicle): string
    {
        $data = $this->validate([
            'name'      => 'required|max:190',
            'price_day' => 'required|numeric|min_val:0',
        ]);
        $fields = [
            'agency_id'        => Request::post('agency_id') ?: null,
            'category_id'      => Request::post('category_id') ?: null,
            'name'             => $data['name'],
            'name_gu'          => Request::post('name_gu') ?: null,
            'brand'            => Request::post('brand') ?: null,
            'model'            => Request::post('model') ?: null,
            'reg_number'       => Request::post('reg_number') ?: null,
            'transmission'     => Request::post('transmission', 'na'),
            'fuel'             => Request::post('fuel', 'petrol'),
            'seats'            => Request::post('seats') ?: null,
            'units'            => max(1, (int)Request::post('units', 1)),
            'price_hour'       => (float)Request::post('price_hour', 0),
            'price_day'        => (float)$data['price_day'],
            'price_week'       => (float)Request::post('price_week', 0),
            'price_month'      => (float)Request::post('price_month', 0),
            'deposit'          => (float)Request::post('deposit', 0),
            'commission_type'  => Request::post('commission_type', 'inherit'),
            'commission_value' => (float)Request::post('commission_value', 0),
            'description'      => Request::post('description') ?: null,
            'status'           => Request::post('status', 'active'),
            'sort_order'       => (int)Request::post('sort_order', 0),
        ];

        // Main image
        $main = Request::file('main_image');
        if ($main && ($main['error'] ?? 1) === UPLOAD_ERR_OK) {
            $r = Uploader::handle($main, 'vehicles', ['jpg','jpeg','png','webp']);
            if ($r['ok']) { $fields['main_image'] = $r['path']; }
            else { Session::flash('error', $r['error']); return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/vehicles'); }
        }

        if ($vehicle) {
            Database::update('vehicles', $fields, ['id' => $vehicle['id']]);
            $vehicleId = (int)$vehicle['id'];
            ActivityLog::record('vehicle.update', 'vehicle', $vehicleId);
            Session::flash('success', 'Vehicle updated.');
        } else {
            $vehicleId = Database::insert('vehicles', $fields);
            ActivityLog::record('vehicle.create', 'vehicle', $vehicleId);
            Session::flash('success', 'Vehicle created.');
        }

        // Gallery images (multiple)
        $gallery = Request::file('gallery');
        if ($gallery && is_array($gallery['name'])) {
            $count = count($gallery['name']);
            for ($i = 0; $i < $count; $i++) {
                if (($gallery['error'][$i] ?? 1) !== UPLOAD_ERR_OK) { continue; }
                $one = [
                    'name' => $gallery['name'][$i], 'type' => $gallery['type'][$i],
                    'tmp_name' => $gallery['tmp_name'][$i], 'error' => $gallery['error'][$i], 'size' => $gallery['size'][$i],
                ];
                $r = Uploader::handle($one, 'vehicles', ['jpg','jpeg','png','webp']);
                if ($r['ok']) {
                    Database::insert('vehicle_images', ['vehicle_id' => $vehicleId, 'image' => $r['path'], 'sort_order' => $i]);
                }
            }
        }

        return $this->redirect('/admin/vehicles');
    }

    public function delete(array $p): string
    {
        $this->verifyCsrf();
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$p['id']]);
        if ($vehicle) {
            Database::delete('vehicles', ['id' => $vehicle['id']]);
            ActivityLog::record('vehicle.delete', 'vehicle', $vehicle['id']);
            Session::flash('success', 'Vehicle deleted.');
        }
        return $this->redirect('/admin/vehicles');
    }
}
