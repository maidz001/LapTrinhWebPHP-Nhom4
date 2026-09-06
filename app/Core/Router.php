<?php
declare(strict_types=1);

/**
 * app/Core/Router.php
 * ---------------------------------------------------------------------
 * Router tối giản, chỉ phục vụ các route đã được migrate sang MVC
 * (nằm dưới tiền tố /mvc/...). KHÔNG đụng tới hệ thống định tuyến theo
 * file vật lý hiện có (auth/login.php, rooms/index.php, ...) — hai cơ
 * chế chạy song song, độc lập với nhau.
 * ---------------------------------------------------------------------
 */
final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][trim($path, '/')] = $handler;
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][trim($path, '/')] = $handler;
    }

    public function dispatch(string $path, string $method): void
    {
        $path = trim($path, '/');
        $method = strtoupper($method);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">'
                . '<title>404 - Không tìm thấy</title></head><body style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;">'
                . '<h2>404 - Không tìm thấy trang</h2>'
                . '<p>Đường dẫn <code>/mvc/' . htmlspecialchars($path) . '</code> chưa được migrate hoặc không tồn tại.</p>'
                . '<p><a href="/mvc/dashboard">&larr; Về trang chủ</a></p>'
                . '</body></html>';
            return;
        }

        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass();
        $controller->$action();
    }
}
