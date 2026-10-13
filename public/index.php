<?php
declare(strict_types=1);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
try {
    $di = require dirname(__DIR__) . '/config/bootstrap.php';
    $app = new Phalcon\Mvc\Application($di);
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $app->handle($uri)->send();
} catch (App\Exceptions\HttpException $e) {
    http_response_code($e->status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => $e->getMessage(), 'fields' => $e->fields], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'No se pudo completar la solicitud. Revise la configuración y el registro del servidor.']);
}
