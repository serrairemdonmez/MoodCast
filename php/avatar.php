<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

startSecureSession();
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) jsonResponse(['error' => 'Giriş gerekli.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'POST gerekli.'], 405);

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$base64 = $input['avatar'] ?? '';

if (empty($base64)) jsonResponse(['error' => 'Resim verisi boş.'], 400);

// Format kontrol
if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,(.+)$/', $base64, $m)) {
    jsonResponse(['error' => 'Geçersiz resim formatı.'], 400);
}

$ext     = ($m[1] === 'jpeg') ? 'jpg' : $m[1];
$imgData = base64_decode($m[2]);
if (!$imgData) jsonResponse(['error' => 'Resim çözümlenemedi.'], 400);
if (strlen($imgData) > 3 * 1024 * 1024) jsonResponse(['error' => 'Resim 3MB\'dan büyük olamaz.'], 400);

// Klasörü oluştur
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Dosyayı kaydet
$filename = 'u' . currentUserId() . '_' . time() . '.' . $ext;
$filepath = $uploadDir . $filename;

if (file_put_contents($filepath, $imgData) === false) {
    // Klasöre yazılamıyorsa base64 olarak DB'ye kaydet
    $pdo = Database::getInstance();
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN avatar_url MEDIUMTEXT");
    } catch(Exception $e) {}
    $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$base64, currentUserId()]);
    jsonResponse(['success' => true, 'avatar_url' => $base64]);
}

// DB'ye web yolunu kaydet
$webPath = BASE_URL . '/uploads/avatars/' . $filename;
$pdo = Database::getInstance();

// Eski dosyayı sil
$old = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ? LIMIT 1');
$old->execute([currentUserId()]);
$oldRow = $old->fetch();
if (!empty($oldRow['avatar_url']) && strpos($oldRow['avatar_url'], 'uploads/avatars/') !== false) {
    $oldFile = $uploadDir . basename($oldRow['avatar_url']);
    if (file_exists($oldFile)) @unlink($oldFile);
}

$pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$webPath, currentUserId()]);
jsonResponse(['success' => true, 'avatar_url' => $webPath]);
