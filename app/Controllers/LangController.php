<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Lang;

class LangController extends Controller
{
    public function switch(array $params): string
    {
        Lang::setLocale($params['locale'] ?? 'en');
        $back = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($back);
    }
}
