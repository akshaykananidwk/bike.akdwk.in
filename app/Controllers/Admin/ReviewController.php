<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\ActivityLog;

class ReviewController extends Controller
{
    public function index(): string
    {
        $status = Request::query('status', 'pending');
        $where = '1'; $params = [];
        if (in_array($status, ['pending','approved','rejected'], true)) {
            $where = 'r.status=?'; $params = [$status];
        }
        $reviews = Database::fetchAll(
            "SELECT r.*, b.code AS booking_code, v.name AS vehicle_name, p.name AS package_name
             FROM {p}reviews r
             LEFT JOIN {p}bookings b ON b.id=r.booking_id
             LEFT JOIN {p}vehicles v ON v.id=r.vehicle_id
             LEFT JOIN {p}packages p ON p.id=r.package_id
             WHERE {$where} ORDER BY r.id DESC LIMIT 200",
            $params
        );
        $counts = [
            'pending'  => (int)Database::scalar("SELECT COUNT(*) FROM {p}reviews WHERE status='pending'"),
            'approved' => (int)Database::scalar("SELECT COUNT(*) FROM {p}reviews WHERE status='approved'"),
            'rejected' => (int)Database::scalar("SELECT COUNT(*) FROM {p}reviews WHERE status='rejected'"),
        ];
        return $this->view('admin/reviews/index', [
            'title' => 'Reviews', 'active' => 'reviews', 'reviews' => $reviews, 'status' => $status, 'counts' => $counts,
        ], 'admin');
    }

    public function moderate(array $p): string
    {
        $this->verifyCsrf();
        $id = (int)$p['id'];
        $action = Request::post('action');
        if ($action === 'delete') {
            Database::delete('reviews', ['id' => $id]);
            Session::flash('success', 'Review deleted.');
        } elseif (in_array($action, ['approve','reject'], true)) {
            Database::update('reviews', ['status' => $action === 'approve' ? 'approved' : 'rejected'], ['id' => $id]);
            Session::flash('success', 'Review ' . $action . 'd.');
        } elseif ($action === 'reply') {
            Database::update('reviews', ['admin_reply' => Request::post('admin_reply') ?: null], ['id' => $id]);
            Session::flash('success', 'Reply saved.');
        }
        ActivityLog::record('review.' . $action, 'review', $id);
        return $this->redirect('/admin/reviews');
    }
}
