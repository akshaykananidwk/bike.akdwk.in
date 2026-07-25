<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): string
    {
        $today = date('Y-m-d');

        $stats = [
            'today_bookings'  => (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE DATE(created_at)=?", [$today]),
            'today_revenue'   => (float)Database::scalar("SELECT COALESCE(SUM(paid_amount),0) FROM {p}bookings WHERE DATE(created_at)=?", [$today]),
            'pending_payouts' => (int)Database::scalar("SELECT COUNT(*) FROM {p}payout_requests WHERE status='pending'"),
            'active_vehicles' => (int)Database::scalar("SELECT COUNT(*) FROM {p}vehicles WHERE status='active'"),
            'total_bookings'  => (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings"),
            'total_revenue'   => (float)Database::scalar("SELECT COALESCE(SUM(paid_amount),0) FROM {p}bookings"),
            'shops'           => (int)Database::scalar("SELECT COUNT(*) FROM {p}shops WHERE status='active'"),
            'agencies'        => (int)Database::scalar("SELECT COUNT(*) FROM {p}agencies WHERE status='active'"),
        ];

        // 7-day revenue series for the chart.
        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date'    => date('d M', strtotime($d)),
                'revenue' => (float)Database::scalar("SELECT COALESCE(SUM(paid_amount),0) FROM {p}bookings WHERE DATE(created_at)=?", [$d]),
            ];
        }

        // Top shops by bookings + conversion (scan -> booking).
        $topShops = Database::fetchAll(
            "SELECT s.id, s.name, s.code,
                    (SELECT COUNT(*) FROM {p}shop_scans sc WHERE sc.shop_id=s.id) AS scans,
                    (SELECT COUNT(*) FROM {p}bookings b WHERE b.shop_id=s.id) AS bookings
             FROM {p}shops s
             ORDER BY bookings DESC, scans DESC
             LIMIT 8"
        );

        $recent = Database::fetchAll(
            "SELECT b.code, b.customer_name, b.total_amount, b.paid_amount, b.status, b.created_at,
                    v.name AS vehicle_name, s.name AS shop_name
             FROM {p}bookings b
             LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             LEFT JOIN {p}shops s ON s.id=b.shop_id
             ORDER BY b.id DESC LIMIT 10"
        );

        return $this->view('admin/dashboard', [
            'title'    => 'Dashboard',
            'active'   => 'dashboard',
            'stats'    => $stats,
            'series'   => $series,
            'topShops' => $topShops,
            'recent'   => $recent,
        ], 'admin');
    }
}
