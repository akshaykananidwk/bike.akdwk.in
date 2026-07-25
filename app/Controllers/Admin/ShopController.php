<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Uploader;
use App\Core\ActivityLog;
use App\Core\Qr;
use App\Services\PosterGenerator;

class ShopController extends Controller
{
    public function index(): string
    {
        $q = Request::query('q', '');
        $where = '1';
        $params = [];
        if ($q !== '') {
            $where = '(name LIKE ? OR code LIKE ? OR mobile LIKE ?)';
            $params = ["%$q%", "%$q%", "%$q%"];
        }
        $shops = Database::fetchAll(
            "SELECT s.*, (SELECT COUNT(*) FROM {p}bookings b WHERE b.shop_id=s.id) AS bookings,
                    (SELECT COUNT(*) FROM {p}shop_scans sc WHERE sc.shop_id=s.id) AS scans,
                    (SELECT balance FROM {p}wallets w WHERE w.owner_type='shop' AND w.owner_id=s.id) AS wallet
             FROM {p}shops s WHERE {$where} ORDER BY s.id DESC",
            $params
        );
        return $this->view('admin/shops/index', [
            'title' => 'Shops', 'active' => 'shops', 'shops' => $shops, 'q' => $q,
        ], 'admin');
    }

    public function create(): string
    {
        if (Request::isPost()) {
            $this->verifyCsrf();
            return $this->save(null);
        }
        return $this->view('admin/shops/form', [
            'title' => 'Add Shop', 'active' => 'shops', 'shop' => null,
            'code' => $this->nextCode(),
        ], 'admin');
    }

    public function edit(array $p): string
    {
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$p['id']]);
        if (!$shop) { Session::flash('error', 'Shop not found.'); return $this->redirect('/admin/shops'); }
        if (Request::isPost()) {
            $this->verifyCsrf();
            return $this->save($shop);
        }
        return $this->view('admin/shops/form', [
            'title' => 'Edit Shop', 'active' => 'shops', 'shop' => $shop, 'code' => $shop['code'],
        ], 'admin');
    }

    private function save(?array $shop): string
    {
        $data = $this->validate([
            'name'   => 'required|max:190',
            'mobile' => 'nullable|mobile',
            'commission_type' => 'required|in:percent,fixed,inherit',
        ]);

        $fields = [
            'name'             => $data['name'],
            'name_gu'          => Request::post('name_gu') ?: null,
            'owner_name'       => Request::post('owner_name') ?: null,
            'mobile'           => Request::post('mobile') ?: null,
            'whatsapp'         => Request::post('whatsapp') ?: Request::post('mobile') ?: null,
            'email'            => Request::post('email') ?: null,
            'address'          => Request::post('address') ?: null,
            'area'             => Request::post('area') ?: null,
            'city'             => Request::post('city') ?: 'Dwarka',
            'commission_type'  => $data['commission_type'],
            'commission_value' => (float)Request::post('commission_value', 0),
            'bank_holder'      => Request::post('bank_holder') ?: null,
            'bank_account'     => Request::post('bank_account') ?: null,
            'bank_ifsc'        => strtoupper((string)Request::post('bank_ifsc')) ?: null,
            'bank_name'        => Request::post('bank_name') ?: null,
            'upi_id'           => Request::post('upi_id') ?: null,
            'kyc_status'       => Request::post('kyc_status', 'pending'),
            'is_verified'      => Request::post('is_verified') ? 1 : 0,
            'status'           => Request::post('status', 'active'),
            'notes'            => Request::post('notes') ?: null,
        ];

        // KYC doc + cheque uploads
        foreach (['kyc_doc' => 'kyc', 'cheque_image' => 'kyc'] as $field => $dir) {
            $file = Request::file($field);
            if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $r = Uploader::handle($file, 'shops/' . $dir);
                if ($r['ok']) { $fields[$field] = $r['path']; }
                else { Session::flash('error', $r['error']); return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/shops'); }
            }
        }

        if ($shop) {
            Database::update('shops', $fields, ['id' => $shop['id']]);
            ActivityLog::record('shop.update', 'shop', $shop['id'], $shop, $fields);
            Session::flash('success', 'Shop updated.');
        } else {
            $fields['code'] = Request::post('code') ?: $this->nextCode();
            $id = Database::insert('shops', $fields);
            // Create wallet
            Database::insert('wallets', ['owner_type' => 'shop', 'owner_id' => $id, 'balance' => 0]);
            ActivityLog::record('shop.create', 'shop', $id, [], $fields);
            Session::flash('success', 'Shop created with code ' . $fields['code'] . '.');
        }
        return $this->redirect('/admin/shops');
    }

    public function delete(array $p): string
    {
        $this->verifyCsrf();
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$p['id']]);
        if ($shop) {
            Database::delete('shops', ['id' => $shop['id']]);
            ActivityLog::record('shop.delete', 'shop', $shop['id'], $shop, []);
            Session::flash('success', 'Shop deleted.');
        }
        return $this->redirect('/admin/shops');
    }

    public function import(): string
    {
        if (Request::isPost()) {
            $this->verifyCsrf();
            $file = Request::file('csv');
            if (!$file || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
                Session::flash('error', 'Please choose a CSV file.');
                return $this->redirect('/admin/shops/import');
            }
            $handle = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($handle);
            $header = array_map(fn($h) => strtolower(trim((string)$h)), $header ?: []);
            $count = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $r = array_combine($header, array_pad($row, count($header), null));
                if (empty($r['name'])) { continue; }
                $code = $r['code'] ?? $this->nextCode();
                if (Database::scalar("SELECT COUNT(*) FROM {p}shops WHERE code=?", [$code])) { continue; }
                $id = Database::insert('shops', [
                    'code'             => $code,
                    'name'             => $r['name'],
                    'name_gu'          => $r['name_gu'] ?? null,
                    'owner_name'       => $r['owner_name'] ?? null,
                    'mobile'           => $r['mobile'] ?? null,
                    'whatsapp'         => $r['whatsapp'] ?? ($r['mobile'] ?? null),
                    'area'             => $r['area'] ?? null,
                    'city'             => $r['city'] ?? 'Dwarka',
                    'commission_type'  => in_array($r['commission_type'] ?? '', ['percent','fixed','inherit'], true) ? $r['commission_type'] : 'inherit',
                    'commission_value' => (float)($r['commission_value'] ?? 0),
                    'status'           => 'active',
                ]);
                Database::insert('wallets', ['owner_type' => 'shop', 'owner_id' => $id, 'balance' => 0]);
                $count++;
            }
            fclose($handle);
            ActivityLog::record('shop.import', 'shop', null, [], ['imported' => $count]);
            Session::flash('success', "Imported {$count} shops.");
            return $this->redirect('/admin/shops');
        }
        return $this->view('admin/shops/import', ['title' => 'Import Shops', 'active' => 'shops'], 'admin');
    }

    /** Download a shop's QR as PNG. */
    public function qr(array $p): string
    {
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$p['id']]);
        if (!$shop) { http_response_code(404); return 'Not found'; }
        $url = rtrim(setting('site_url', '') ?: guess_base_url(), '/') . '/s/' . $shop['code'];
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="qr-' . $shop['code'] . '.png"');
        $tmp = tempnam(sys_get_temp_dir(), 'qr');
        Qr::toFile($url, $tmp, 12, 2);
        readfile($tmp);
        @unlink($tmp);
        return '';
    }

    /** Download a shop's poster (PNG or PDF, A4/A5). */
    public function poster(array $p): string
    {
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$p['id']]);
        if (!$shop) { http_response_code(404); return 'Not found'; }
        $format = Request::query('format', 'pdf');
        $size = strtoupper((string)Request::query('size', 'A4')) === 'A5' ? 'A5' : 'A4';
        $bg = $shop['poster_bg'] ? BASE_PATH . '/uploads/' . $shop['poster_bg'] : null;
        $gen = new PosterGenerator();

        if ($format === 'png') {
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="poster-' . $shop['code'] . '.png"');
            echo $gen->png($shop, $bg);
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="poster-' . $shop['code'] . '-' . $size . '.pdf"');
            echo $gen->pdf($shop, $size, $bg);
        }
        return '';
    }

    /** Bulk-generate all shop posters into a single ZIP. */
    public function bulkPosters(): string
    {
        $shops = Database::fetchAll("SELECT * FROM {p}shops WHERE status='active' ORDER BY code");
        if (!$shops) { Session::flash('error', 'No active shops to generate.'); return $this->redirect('/admin/shops'); }

        $format = Request::query('format', 'pdf');
        $size = strtoupper((string)Request::query('size', 'A4')) === 'A5' ? 'A5' : 'A4';
        $gen = new PosterGenerator();

        $zipPath = BASE_PATH . '/cache/posters_' . date('Ymd_His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Session::flash('error', 'Could not create ZIP.');
            return $this->redirect('/admin/shops');
        }
        foreach ($shops as $shop) {
            $bg = $shop['poster_bg'] ? BASE_PATH . '/uploads/' . $shop['poster_bg'] : null;
            if ($format === 'png') {
                $zip->addFromString('poster-' . $shop['code'] . '.png', $gen->png($shop, $bg));
            } else {
                $zip->addFromString('poster-' . $shop['code'] . '-' . $size . '.pdf', $gen->pdf($shop, $size, $bg));
            }
        }
        $zip->close();
        ActivityLog::record('shop.bulk_posters', 'shop', null, [], ['count' => count($shops), 'format' => $format]);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="dwarka-posters-' . date('Ymd') . '.zip"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
        return '';
    }

    /** Generate the next SHOP-DWK-### code. */
    private function nextCode(): string
    {
        $last = Database::scalar(
            "SELECT code FROM {p}shops WHERE code LIKE 'SHOP-DWK-%' ORDER BY id DESC LIMIT 1"
        );
        $n = 0;
        if ($last && preg_match('/(\d+)$/', $last, $m)) { $n = (int)$m[1]; }
        return sprintf('SHOP-DWK-%03d', $n + 1);
    }
}
