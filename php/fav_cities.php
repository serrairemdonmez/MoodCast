<?php
/**
 * MoodCast - Favori Şehirler
 * GET  → liste
 * POST → ekle / sil
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

startSecureSession();
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Giriş gerekli.'], 401);
}

$pdo = Database::getInstance();

// Tablo yoksa oluştur
$pdo->exec('CREATE TABLE IF NOT EXISTS fav_cities (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    city    VARCHAR(100) NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fav_city (user_id, city),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = $pdo->prepare('SELECT id, city, added_at FROM fav_cities WHERE user_id = ? ORDER BY added_at DESC');
    $s->execute([currentUserId()]);
    jsonResponse(['success' => true, 'cities' => $s->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = sanitizeString($input['action'] ?? '', 10);
    $city   = sanitizeString($input['city'] ?? '', 100);
    if (!$city) jsonResponse(['error' => 'Şehir adı gerekli.'], 400);

    if ($action === 'add') {
        $pdo->prepare('INSERT IGNORE INTO fav_cities (user_id, city) VALUES (?, ?)')->execute([currentUserId(), $city]);
        jsonResponse(['success' => true]);
    }
    if ($action === 'remove') {
        $pdo->prepare('DELETE FROM fav_cities WHERE user_id = ? AND city = ?')->execute([currentUserId(), $city]);
        jsonResponse(['success' => true]);
    }
}
jsonResponse(['error' => 'Geçersiz istek.'], 400);
