<?php
declare(strict_types=1);

/**
 * index.php — Trang chủ của toàn site.
 * ---------------------------------------------------------------------
 * Sau khi hoàn tất Phase 6 (cắt chuyển cuối cùng sang MVC chuẩn), toàn bộ
 * code thủ tục cũ đã được xoá. "/" giờ chỉ là một redirect mỏng sang
 * route MVC thật (/mvc/dashboard) — KHÔNG tự parse URI hay tự require
 * router (tránh mọi rủi ro lệch đường dẫn khi ai đó gõ thẳng
 * "/index.php" thay vì "/").
 * ---------------------------------------------------------------------
 */

header('Location: /mvc/dashboard');
exit;
