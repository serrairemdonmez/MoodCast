<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

startSecureSession();
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Giriş gerekli.'], 401);
}

$pdo    = Database::getInstance();
$userId = currentUserId();

// Eksik kolonları güvenli şekilde ekle
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS bio VARCHAR(300) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS fav_genre VARCHAR(50) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch(Exception $e) { /* Kolon zaten varsa hata vermez */ }

// fav_cities tablosu
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS fav_cities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        city VARCHAR(100) NOT NULL,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fav_city (user_id, city),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB');
} catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('
        SELECT id, username, email,
               COALESCE(full_name, "") AS full_name,
               COALESCE(bio, "") AS bio,
               COALESCE(avatar_url, "") AS avatar_url,
               birth_date, 
               COALESCE(country, "") AS country,
               COALESCE(fav_genre, "") AS fav_genre,
               created_at, last_login,
               (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) AS fav_count,
               (SELECT COUNT(*) FROM search_history WHERE user_id = u.id) AS search_count,
               (SELECT COUNT(*) FROM fav_cities WHERE user_id = u.id) AS fav_cities_count
        FROM users u WHERE id = ? LIMIT 1
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['error' => 'Kullanıcı bulunamadı.'], 404);
    unset($user['password']);
    jsonResponse(['success' => true, 'user' => $user]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = sanitizeString($input['action'] ?? '', 30);

    if ($action === 'update') {
        $fullName  = sanitizeString($input['full_name']  ?? '', 100);
        $bio       = sanitizeString($input['bio']        ?? '', 300);
        $country   = sanitizeString($input['country']    ?? '', 100);
        $favGenre  = sanitizeString($input['fav_genre']  ?? '', 50);
        $birthDate = $input['birth_date'] ?? '';
        if ($birthDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) $birthDate = null;

        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if ($email) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $chk->execute([$email, $userId]);
            if ($chk->fetch()) jsonResponse(['error' => 'Bu e-posta zaten kullanımda.'], 409);
        }

        $sql = 'UPDATE users SET full_name=?, bio=?, country=?, fav_genre=?, birth_date=?' . ($email ? ', email=?' : '') . ' WHERE id=?';
        $params = [$fullName, $bio, $country, $favGenre, $birthDate ?: null];
        if ($email) $params[] = $email;
        $params[] = $userId;
        $pdo->prepare($sql)->execute($params);
        jsonResponse(['success' => true, 'message' => 'Profil güncellendi! ✓']);
    }

    if ($action === 'change_password') {
        $current = $input['current_password'] ?? '';
        $new     = $input['new_password']     ?? '';
        $confirm = $input['confirm_password'] ?? '';
        if (empty($current) || empty($new)) jsonResponse(['error' => 'Tüm alanları doldurun.'], 400);
        if ($new !== $confirm) jsonResponse(['error' => 'Yeni şifreler eşleşmiyor.'], 400);
        if (strlen($new) < 8) jsonResponse(['error' => 'Şifre en az 8 karakter olmalı.'], 400);
        if (!preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new))
            jsonResponse(['error' => 'Şifre büyük harf ve rakam içermeli.'], 400);
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!password_verify($current, $row['password'])) jsonResponse(['error' => 'Mevcut şifre hatalı.'], 401);
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')
            ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]), $userId]);
        jsonResponse(['success' => true, 'message' => 'Şifre güncellendi! ✓']);
    }

    if ($action === 'delete_account') {
        $password = $input['password'] ?? '';
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!password_verify($password, $row['password'])) jsonResponse(['error' => 'Şifre hatalı.'], 401);
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
        $_SESSION = []; session_destroy();
        jsonResponse(['success' => true, 'message' => 'Hesabınız silindi.']);
    }
}
jsonResponse(['error' => 'Geçersiz istek.'], 400);
