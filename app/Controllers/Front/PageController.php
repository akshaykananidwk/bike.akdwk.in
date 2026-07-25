<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Lang;

class PageController extends Controller
{
    public function show(array $params): string
    {
        $page = Database::fetch("SELECT * FROM {p}pages WHERE slug=? AND status='active'", [$params['slug'] ?? '']);
        if (!$page) { http_response_code(404); return $this->view('errors/404', []); }
        $gu = Lang::isGujarati();
        return $this->view('front/page', [
            'title'   => ($gu && $page['title_gu'] ? $page['title_gu'] : $page['title']),
            'heading' => ($gu && $page['title_gu'] ? $page['title_gu'] : $page['title']),
            'body'    => ($gu && $page['content_gu'] ? $page['content_gu'] : $page['content']),
        ], 'front');
    }
}
