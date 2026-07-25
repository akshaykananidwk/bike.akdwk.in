<?php
namespace App\Core;

/**
 * Minimal router supporting GET/POST, named params ({code}), and middleware.
 * Routes map to "Controller@method" strings resolved under App\Controllers\.
 */
class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, $handler, array $mw = []): void    { $this->add('GET', $path, $handler, $mw); }
    public function post(string $path, $handler, array $mw = []): void   { $this->add('POST', $path, $handler, $mw); }
    public function any(string $path, $handler, array $mw = []): void
    {
        $this->add('GET', $path, $handler, $mw);
        $this->add('POST', $path, $handler, $mw);
    }

    /** Group routes under a shared prefix and/or middleware. */
    public function group(array $opts, callable $fn): void
    {
        $this->groupStack[] = $opts;
        $fn($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, $handler, array $mw): void
    {
        $prefix = '';
        $groupMw = [];
        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'] ?? '';
            $groupMw = array_merge($groupMw, $g['middleware'] ?? []);
        }
        $full = rtrim($prefix . $path, '/');
        if ($full === '') { $full = '/'; }
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $this->compile($full),
            'handler' => $handler,
            'mw'      => array_merge($groupMw, $mw),
        ];
    }

    private function compile(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $uri = rtrim(Request::uri(), '/');
        if ($uri === '') { $uri = '/'; }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

                // Middleware chain
                foreach ($route['mw'] as $mwName) {
                    $mwClass = 'App\\Middleware\\' . $mwName;
                    if (class_exists($mwClass)) {
                        (new $mwClass())->handle();
                    }
                }
                $this->invoke($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    private function invoke($handler, array $params): void
    {
        if (is_callable($handler)) {
            echo $handler($params);
            return;
        }
        [$class, $action] = explode('@', $handler);
        $fqcn = 'App\\Controllers\\' . $class;
        if (!class_exists($fqcn)) {
            throw new \RuntimeException("Controller not found: {$fqcn}");
        }
        $controller = new $fqcn();
        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Action not found: {$fqcn}::{$action}");
        }
        echo $controller->$action($params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        $tpl = BASE_PATH . '/app/Views/errors/404.php';
        if (is_file($tpl)) { require $tpl; } else { echo '404 Not Found'; }
    }
}
