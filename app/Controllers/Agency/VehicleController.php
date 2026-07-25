<?php
namespace App\Controllers\Agency;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;

class VehicleController extends Controller
{
    public function index(): string
    {
        $u = Auth::user();
        $agencyId = (int)($u['agency_id'] ?? 0);
        $vehicles = Database::fetchAll(
            "SELECT v.*, c.name AS category_name FROM {p}vehicles v LEFT JOIN {p}categories c ON c.id=v.category_id
             WHERE v.agency_id=? ORDER BY v.id DESC", [$agencyId]
        );
        return $this->view('agency/vehicles', [
            'title' => 'My Vehicles', 'base' => '/agency',
            'nav' => DashboardController::nav('vehicles'), 'vehicles' => $vehicles,
        ], 'panel');
    }

    /** Toggle availability on/off. */
    public function toggle(array $p): string
    {
        $this->verifyCsrf();
        $u = Auth::user();
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=? AND agency_id=?", [$p['id'], $u['agency_id'] ?? 0]);
        if ($vehicle) {
            $new = $vehicle['status'] === 'active' ? 'inactive' : 'active';
            Database::update('vehicles', ['status' => $new], ['id' => $vehicle['id']]);
            Session::flash('success', 'Vehicle ' . ($new === 'active' ? 'enabled' : 'disabled') . '.');
        }
        return $this->redirect('/agency/vehicles');
    }
}
