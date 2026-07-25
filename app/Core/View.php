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

        // Use collision-proof internal names — templates may define $data/$vars/etc.
        $__viewData = array_merge(self::$shared, $data);

        // Render inside an isolated closure so template-scope variables never
        // clobber this method's locals.
        $__render = static function (string $__file, array $__scope): string {
            extract($__scope, EXTR_SKIP);
            ob_start();
            require $__file;
            return (string) ob_get_clean();
        };

        $content = $__render($file, $__viewData);

        if ($layout !== null) {
            $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
            if (!is_file($layoutFile)) {
                throw new \RuntimeException("Layout not found: {$layout}");
            }
            $__viewData['content'] = $content;
            $content = $__render($layoutFile, $__viewData);
        }
        return $content;
    }

    /** Include a partial from within a template. */
    public static function partial(string $template, array $data = []): void
    {
        echo self::render($template, $data);
    }
}
