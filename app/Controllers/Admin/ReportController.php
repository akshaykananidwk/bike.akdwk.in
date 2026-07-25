<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class ReportController extends Controller
{
    public function index(): string
    {
        $from = Request::query('from', date('Y-m-01'));
        $to   = Request::query('to', date('Y-m-d'));
        $type = Request::query('type', 'summary');
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        $data = $this->build($type, $range);

        if (Request::query('export') === 'csv') {
            return $this->exportCsv($type, $data['rows'], $data['columns']);
        }

        return $this->view('admin/reports/index', [
            'title' => 'Reports', 'active' => 'reports',
            'from' => $from, 'to' => $to, 'type' => $type,
            'report' => $data, 'summary' => $this->summaryCards($range),
        ], 'admin');
    }

    private function build(string $type, array $range): array
    {
        switch ($type) {
            case 'shop':
                return [
                    'title' => 'Shop-wise Report', 'columns' => ['Shop', 'Code', 'Bookings', 'Revenue', 'Commission'],
                    'rows' => Database::fetchAll(
                        "SELECT s.name, s.code, COUNT(b.id) bookings, COALESCE(SUM(b.paid_amount),0) revenue,
                                COALESCE((SELECT SUM(CASE WHEN cl.entry_type='reversal' THEN -cl.amount ELSE cl.amount END) FROM {p}commission_ledger cl WHERE cl.beneficiary_type='shop' AND cl.beneficiary_id=s.id),0) commission
                         FROM {p}shops s LEFT JOIN {p}bookings b ON b.shop_id=s.id AND b.created_at BETWEEN ? AND ?
                         GROUP BY s.id ORDER BY revenue DESC", $range),
                ];
            case 'agency':
                return [
                    'title' => 'Agency-wise Report', 'columns' => ['Agency', 'Code', 'Bookings', 'Revenue', 'Settlement'],
                    'rows' => Database::fetchAll(
                        "SELECT a.name, a.code, COUNT(b.id) bookings, COALESCE(SUM(b.paid_amount),0) revenue,
                                COALESCE((SELECT SUM(CASE WHEN cl.entry_type='reversal' THEN -cl.amount ELSE cl.amount END) FROM {p}commission_ledger cl WHERE cl.beneficiary_type='agency' AND cl.beneficiary_id=a.id),0) settlement
                         FROM {p}agencies a LEFT JOIN {p}bookings b ON b.agency_id=a.id AND b.created_at BETWEEN ? AND ?
                         GROUP BY a.id ORDER BY revenue DESC", $range),
                ];
            case 'vehicle':
                return [
                    'title' => 'Vehicle Utilisation', 'columns' => ['Vehicle', 'Category', 'Bookings', 'Hours', 'Revenue'],
                    'rows' => Database::fetchAll(
                        "SELECT v.name, c.name category, COUNT(b.id) bookings, COALESCE(SUM(b.duration_hours),0) hours, COALESCE(SUM(b.paid_amount),0) revenue
                         FROM {p}vehicles v LEFT JOIN {p}categories c ON c.id=v.category_id
                         LEFT JOIN {p}bookings b ON b.vehicle_id=v.id AND b.created_at BETWEEN ? AND ?
                         GROUP BY v.id ORDER BY bookings DESC", $range),
                ];
            case 'gst':
                return [
                    'title' => 'GST Report', 'columns' => ['Booking', 'Date', 'Base', 'GST', 'Total'],
                    'rows' => Database::fetchAll(
                        "SELECT code, DATE(created_at) date, base_amount base, tax_amount gst, total_amount total
                         FROM {p}bookings WHERE tax_amount>0 AND created_at BETWEEN ? AND ? ORDER BY id DESC", $range),
                ];
            case 'monthly':
                return [
                    'title' => 'Monthly Revenue', 'columns' => ['Month', 'Bookings', 'Revenue'],
                    'rows' => Database::fetchAll(
                        "SELECT DATE_FORMAT(created_at,'%Y-%m') month, COUNT(*) bookings, COALESCE(SUM(paid_amount),0) revenue
                         FROM {p}bookings WHERE created_at BETWEEN ? AND ? GROUP BY month ORDER BY month DESC", $range),
                ];
            default: // daily summary
                return [
                    'title' => 'Daily Revenue', 'columns' => ['Date', 'Bookings', 'Revenue'],
                    'rows' => Database::fetchAll(
                        "SELECT DATE(created_at) date, COUNT(*) bookings, COALESCE(SUM(paid_amount),0) revenue
                         FROM {p}bookings WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date DESC", $range),
                ];
        }
    }

    private function summaryCards(array $range): array
    {
        return [
            'bookings' => (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE created_at BETWEEN ? AND ?", $range),
            'revenue'  => (float)Database::scalar("SELECT COALESCE(SUM(paid_amount),0) FROM {p}bookings WHERE created_at BETWEEN ? AND ?", $range),
            'commission'=> (float)Database::scalar("SELECT COALESCE(SUM(CASE WHEN entry_type='reversal' THEN -amount ELSE amount END),0) FROM {p}commission_ledger cl JOIN {p}bookings b ON b.id=cl.booking_id WHERE cl.beneficiary_type='shop' AND b.created_at BETWEEN ? AND ?", $range),
            'gst'      => (float)Database::scalar("SELECT COALESCE(SUM(tax_amount),0) FROM {p}bookings WHERE created_at BETWEEN ? AND ?", $range),
        ];
    }

    private function exportCsv(string $type, array $rows, array $columns): string
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report-' . $type . '-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel + Gujarati
        fputcsv($out, $columns, ',', '"', '');
        foreach ($rows as $r) {
            fputcsv($out, array_values($r), ',', '"', '');
        }
        fclose($out);
        return '';
    }
}
