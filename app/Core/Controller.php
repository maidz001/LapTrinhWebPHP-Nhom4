<?php
declare(strict_types=1);

/**
 * app/Core/Controller.php
 * Lớp cha cho mọi Controller: chỉ điều phối (nhận request -> gọi Model
 * -> chọn View), không chứa SQL và không in HTML trực tiếp.
 */
abstract class Controller
{
    /**
     * Render 1 view trong app/Views/. Ví dụ: view('auth/login', [...])
     * sẽ nạp app/Views/auth/login.php với các biến trong $data.
     */
    protected function view(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException("Không tìm thấy view: {$view}");
        }
        extract($data, EXTR_SKIP);
        require $viewFile;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    /** Lấy dữ liệu từ POST trước, không có thì lấy GET. */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
