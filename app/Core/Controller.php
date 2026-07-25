<?php
namespace App\Core;

/**
 * Base controller with view/json/redirect helpers and CSRF verification.
 */
abstract class Controller
{
    /** Render a view (optionally within a layout) and return HTML. */
    protected function view(string $template, array $data = [], ?string $layout = null): string
    {
        return View::render($template, $data, $layout);
    }

    /** Emit a JSON response and stop. */
    protected function json($data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $url): string
    {
        redirect($url);
        return '';
    }

    /** Verify CSRF on POST requests. */
    protected function verifyCsrf(): void
    {
        if (Request::isPost()) {
            Csrf::verify();
        }
    }

    /** Validate input; on failure flash errors and redirect back (or JSON). */
    protected function validate(array $rules, ?string $redirectBack = null): array
    {
        $v = Validator::make(Request::all(), $rules);
        if ($v->fails()) {
            if (Request::wantsJson()) {
                echo $this->json(['ok' => false, 'errors' => $v->errors()], 422);
                exit;
            }
            Session::flash('errors', $v->errors());
            Session::flash('old', Request::all());
            redirect($redirectBack ?? ($_SERVER['HTTP_REFERER'] ?? '/'));
        }
        return Request::all();
    }
}
