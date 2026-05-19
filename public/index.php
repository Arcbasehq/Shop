<?php
declare(strict_types=1);

/**
 * Front controller. All HTTP requests funnel through here via the .htaccess
 * rewrite rule. The router below dispatches to controller actions.
 */

use Golders\Auth;
use Golders\BlockBeeClient;
use Golders\Controllers\AccountController;
use Golders\Controllers\AdminController;
use Golders\Controllers\CartController;
use Golders\Controllers\CheckoutController;
use Golders\Controllers\ShopController;
use Golders\Controllers\WebhookController;
use Golders\Security;
use Golders\View;

$boot = require __DIR__ . '/../src/bootstrap.php';
$config = $boot['config'];

$blockbee = new BlockBeeClient($config['blockbee']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri    = '/' . trim($uri, '/');

// State-changing methods must carry a valid CSRF token, except the BlockBee
// callback which is authenticated by its URL token + a /logs re-check.
$exemptFromCsrf = ['/webhook/blockbee'];
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
    && !in_array($uri, $exemptFromCsrf, true)) {
    Security::requireCsrf();
}

try {
    switch (true) {
        // ---- Shop --------------------------------------------------------
        case $method === 'GET' && $uri === '/':
            (new ShopController())->index(); break;
        case $method === 'GET' && preg_match('#^/product/([a-z0-9\-]+)$#', $uri, $m):
            (new ShopController())->show($m[1]); break;

        // ---- Cart --------------------------------------------------------
        case $method === 'GET'  && $uri === '/cart':        (new CartController())->show(); break;
        case $method === 'POST' && $uri === '/cart/add':    (new CartController())->add(); break;
        case $method === 'POST' && $uri === '/cart/update': (new CartController())->update(); break;
        case $method === 'POST' && $uri === '/cart/remove': (new CartController())->remove(); break;

        // ---- Checkout / payment -----------------------------------------
        case $method === 'GET'  && $uri === '/checkout': (new CheckoutController($blockbee, $config))->show(); break;
        case $method === 'POST' && $uri === '/checkout': (new CheckoutController($blockbee, $config))->place(); break;
        case $method === 'GET'  && preg_match('#^/order/([A-Z0-9]{26})$#', $uri, $m):
            (new CheckoutController($blockbee, $config))->view($m[1]); break;
        case $method === 'GET'  && preg_match('#^/order/([A-Z0-9]{26})/pay$#', $uri, $m):
            (new CheckoutController($blockbee, $config))->pay($m[1]); break;
        case $method === 'POST' && preg_match('#^/order/([A-Z0-9]{26})/pay$#', $uri, $m):
            (new CheckoutController($blockbee, $config))->selectCoin($m[1]); break;

        // ---- Account -----------------------------------------------------
        case $method === 'GET'  && $uri === '/login':    (new AccountController())->loginForm(); break;
        case $method === 'POST' && $uri === '/login':    (new AccountController())->login(); break;
        case $method === 'GET'  && $uri === '/register': (new AccountController())->registerForm(); break;
        case $method === 'POST' && $uri === '/register': (new AccountController())->register(); break;
        case $method === 'POST' && $uri === '/logout':   (new AccountController())->logout(); break;
        case $method === 'GET'  && $uri === '/account':  (new AccountController())->dashboard(); break;

        // ---- Admin -------------------------------------------------------
        case $method === 'GET'  && $uri === '/admin':              (new AdminController())->dashboard(); break;
        case $method === 'GET'  && $uri === '/admin/orders':       (new AdminController())->orders(); break;
        case $method === 'POST' && $uri === '/admin/orders/ship':  (new AdminController())->markShipped(); break;
        case $method === 'GET'  && $uri === '/admin/products':     (new AdminController())->products(); break;
        case $method === 'POST' && $uri === '/admin/products':     (new AdminController())->saveProduct(); break;

        // ---- Webhook (URL-token authenticated, no CSRF) ------------------
        case ($method === 'POST' || $method === 'GET') && $uri === '/webhook/blockbee':
            (new WebhookController($blockbee, $config))->blockbee(); break;

        default:
            http_response_code(404);
            View::render('errors/404');
    }
} catch (\Throwable $e) {
    error_log('[Router] ' . $e->getMessage());
    http_response_code(500);
    View::render('errors/500', ['message' => $e->getMessage()]);
}
