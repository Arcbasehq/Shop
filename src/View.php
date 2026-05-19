<?php
declare(strict_types=1);

namespace Golders;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $viewsDir = __DIR__ . '/../views';
        $tmpl = $viewsDir . '/' . $template . '.php';
        if (!is_file($tmpl)) {
            throw new \RuntimeException("View not found: $template");
        }
        $e = fn($v) => Security::e((string)$v);
        $csrf = Security::csrfToken();
        $user = Auth::user();
        $cartCount = Cart::count();

        extract($data, EXTR_SKIP);

        ob_start();
        require $tmpl;
        $content = ob_get_clean();

        if ($layout) {
            require $viewsDir . '/' . $layout . '.php';
        } else {
            echo $content;
        }
    }

    public static function money(int $cents, string $currency = 'USD'): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $whole = intdiv($cents, 100);
        $frac = $cents % 100;
        $sym = $currency === 'USD' ? '$' : ($currency . ' ');
        return $sign . $sym . number_format($whole) . '.' . str_pad((string)$frac, 2, '0', STR_PAD_LEFT);
    }
}
