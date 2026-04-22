<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST gerekli.'], 405);

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = sanitizeString($input['action'] ?? '', 20);
$pdo    = Database::getInstance();

// Token tablosunu oluştur
$pdo->exec('CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB');

if ($action === 'request') {
    $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$email) jsonResponse(['error' => 'Geçersiz e-posta adresi.'], 400);

    $u = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $u->execute([$email]);

    if ($u->fetch()) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
        $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)')->execute([$email, $token, $expires]);

        $resetLink = BASE_URL . '/reset.php?token=' . $token;

        jsonResponse([
            'success'   => true,
            'message'   => 'Sıfırlama bağlantısı oluşturuldu.',
            'dev_token' => $token,
            'dev_link'  => $resetLink,
        ]);
    }

    // Kullanıcı yoksa da aynı mesaj (güvenlik)
    jsonResponse(['success' => true, 'message' => 'Eğer bu e-posta kayıtlıysa sıfırlama bağlantısı oluşturuldu.']);
}

if ($action === 'reset') {
    $token    = sanitizeString($input['token'] ?? '', 64);
    $password = $input['password'] ?? '';
    $confirm  = $input['confirm']  ?? '';

    if (empty($token)) jsonResponse(['error' => 'Token gerekli.'], 400);
    if (strlen($password) < 8) jsonResponse(['error' => 'Şifre en az 8 karakter olmalı.'], 400);
    if ($password !== $confirm) jsonResponse(['error' => 'Şifreler eşleşmiyor.'], 400);
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password))
        jsonResponse(['error' => 'Şifre büyük harf ve rakam içermeli.'], 400);

    $r = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0 LIMIT 1');
    $r->execute([$token]);
    $row = $r->fetch();
    if (!$row) jsonResponse(['error' => 'Token geçersiz veya süresi dolmuş.'], 400);

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([$hash, $row['email']]);
    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')->execute([$token]);

    jsonResponse(['success' => true, 'message' => 'Şifreniz başarıyla güncellendi! Giriş yapabilirsiniz.']);
}

jsonResponse(['error' => 'Geçersiz işlem.'], 400);
