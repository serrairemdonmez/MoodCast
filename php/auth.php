<?php
/**
 * MoodCast - Kimlik Doğrulama
 * POST /php/auth.php  (action=register | login | logout)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

startSecureSession();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Yalnızca POST metodu kabul edilir.'], 405);
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = sanitizeString($input['action'] ?? '', 20);

// CSRF kontrolü devre dışı — geliştirme ortamı (localhost)
// Üretimde tekrar aktif edin

$pdo = Database::getInstance();

// ═══════════════════════════════════════════════════════════════
// KAYIT
// ═══════════════════════════════════════════════════════════════
if ($action === 'register') {
    $username = sanitizeString($input['username'] ?? '', 50);
    $email    = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $input['password'] ?? '';

    $errors = [];
    if (strlen($username) < 3)  $errors[] = 'Kullanıcı adı en az 3 karakter olmalı.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçersiz e-posta adresi.';
    if (strlen($password) < 8)  $errors[] = 'Şifre en az 8 karakter olmalı.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Şifre en az bir büyük harf içermeli.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Şifre en az bir rakam içermeli.';

    if ($errors) jsonResponse(['error' => implode(' ', $errors)], 422);

    $chk = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $chk->execute([$username, $email]);
    if ($chk->fetch()) {
        jsonResponse(['error' => 'Bu kullanıcı adı veya e-posta zaten kayıtlı.'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $ins = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $ins->execute([$username, $email, $hash]);

    jsonResponse(['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.']);
}

// ═══════════════════════════════════════════════════════════════
// GİRİŞ
// ═══════════════════════════════════════════════════════════════
if ($action === 'login') {
    $username = sanitizeString($input['username'] ?? '', 50);
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'Kullanıcı adı ve şifre gereklidir.'], 400);
    }

    $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $dummyHash = '$2y$12$invalidhashinvalidhashinvalidha';
    $valid = $user ? password_verify($password, $user['password']) : password_verify($password, $dummyHash);

    if (!$user || !$valid) {
        jsonResponse(['error' => 'Kullanıcı adı veya şifre hatalı.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];

    $upd = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $upd->execute([$user['id']]);

    jsonResponse([
        'success'  => true,
        'message'  => 'Giriş başarılı!',
        'username' => $user['username'],
    ]);
}

// ═══════════════════════════════════════════════════════════════
// ÇIKIŞ
// ═══════════════════════════════════════════════════════════════
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Çıkış yapıldı.']);
}

jsonResponse(['error' => 'Geçersiz işlem.'], 400);
