<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\ActivityLog;

class CategoryController extends Controller
{
    public function index(): string
    {
        if (Request::isPost()) {
            $this->verifyCsrf();
            $action = Request::post('action');
            if ($action === 'delete') {
                $id = (int)Request::post('id');
                Database::delete('categories', ['id' => $id]);
                ActivityLog::record('category.delete', 'category', $id);
                Session::flash('success', 'Category deleted.');
            } else {
                $name = trim((string)Request::post('name'));
                if ($name === '') { Session::flash('error', 'Name is required.'); return $this->redirect('/admin/categories'); }
                $slug = $this->slug($name);
                $fields = [
                    'name'       => $name,
                    'name_gu'    => Request::post('name_gu') ?: null,
                    'icon'       => Request::post('icon') ?: 'bi-scooter',
                    'sort_order' => (int)Request::post('sort_order', 0),
                    'status'     => Request::post('status', 'active'),
                ];
                $id = (int)Request::post('id');
                if ($id) {
                    Database::update('categories', $fields, ['id' => $id]);
                    ActivityLog::record('category.update', 'category', $id, [], $fields);
                    Session::flash('success', 'Category updated.');
                } else {
                    $fields['slug'] = $slug;
                    // Ensure unique slug
                    $i = 1; $base = $slug;
                    while (Database::scalar("SELECT COUNT(*) FROM {p}categories WHERE slug=?", [$fields['slug']])) {
                        $fields['slug'] = $base . '-' . (++$i);
                    }
                    $newId = Database::insert('categories', $fields);
                    ActivityLog::record('category.create', 'category', $newId, [], $fields);
                    Session::flash('success', 'Category added.');
                }
            }
            return $this->redirect('/admin/categories');
        }

        $categories = Database::fetchAll("SELECT * FROM {p}categories ORDER BY sort_order, id");
        return $this->view('admin/categories/index', ['title' => 'Categories', 'active' => 'categories', 'categories' => $categories], 'admin');
    }

    private function slug(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string)$s, '-') ?: 'category';
    }
}
