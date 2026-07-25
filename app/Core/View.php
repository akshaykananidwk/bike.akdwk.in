<?php
namespace App\Core;

/**
 * Simple PHP-template view renderer with layout support.
 * Templates live in app/Views and receive $data extracted into scope.
 */
class View
{
    private static array $shared = [];

    /** Data available to every view (e.g. current user, settings). */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $file = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        $vars = array_merge(self::$shared, $data);
        extract($vars, EXTR_SKIP);

        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
            if (!is_file($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layout}");
            }
            $vars['content'] = $content;
            extract($vars, EXTR_SKIP);
            ob_start();
            require $layoutFile;
            $content = ob_get_clean();
        }
        return $content;
    }

    /** Include a partial from within a template. */
    public static function partial(string $template, array $data = []): void
    {
        echo self::render($template, $data);
    }
}
