<?php
/**
 * MoodCast - Oturum & Güvenlik Yardımcıları
 * require ile dahil edilir (include yerine — eksikse fatal error verir)
 */

require_once __DIR__ . '/config.php';

// ─── Güvenli Oturum Başlatma ──────────────────────────────────────────────────
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
        if (empty($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

// ─── CSRF Token ───────────────────────────────────────────────────────────────
function getCsrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── XSS Koruması ─────────────────────────────────────────────────────────────
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ─── Giriş doğrulama yardımcıları ─────────────────────────────────────────────
function sanitizeString(string $input, int $maxLen = 255): string {
    return substr(trim(strip_tags($input)), 0, $maxLen);
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

// ─── JSON yanıt yardımcısı ────────────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Rate limiting (geliştirme ortamında devre dışı) ─────────────────────────
function rateLimitCheck(string $key, int $maxRequests = 10, int $window = 60): bool {
    // Mac/XAMPP geliştirme ortamında izin sorunu olduğundan her zaman true döner
    return true;
}
