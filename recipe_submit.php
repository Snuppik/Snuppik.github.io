<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/script.php';

$title = trim((string) ($_POST['title'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
$short = trim((string) ($_POST['short'] ?? ''));
$body = trim((string) ($_POST['text'] ?? ''));

$category = $category === '' ? null : $category;
$short = $short === '' ? null : $short;

if ($title === '' || cookbook_utf8_strlen($title) > 255) {
    echo json_encode(['ok' => false, 'error' => 'Укажите название рецепта.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($body === '') {
    echo json_encode(['ok' => false, 'error' => 'Опишите ингредиенты и способ приготовления.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$imagePath = null;
$file = $_FILES['photo'] ?? null;
if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Ошибка загрузки файла.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $tmp = (string) $file['tmp_name'];
    $size = (int) ($file['size'] ?? 0);
    if ($size > 5 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'error' => 'Файл слишком большой (максимум 5 МБ).'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        echo json_encode(['ok' => false, 'error' => 'Допустимы только изображения JPG, PNG, GIF, WEBP.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ext = $allowed[$mime];
    $newBasename = 'upload_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destDir = __DIR__ . '/images';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $destPath = $destDir . '/' . $newBasename;
    if (!move_uploaded_file($tmp, $destPath)) {
        echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить файл.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $imagePath = $newBasename;
}

try {
    $pdo = cookbook_connect_pdo();
    $id = cookbook_insert_recipe_submission($pdo, $title, $body, $category, $short, $imagePath);
    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[recipe_submit] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);

    // По умолчанию в ответе — текст исключения (чтобы видеть причину: драйвер, пароль, отсутствие таблицы).
    // Для публичного сервера задайте переменную окружения COOKBOOK_HIDE_DB_ERRORS=1.
    $message = $e->getMessage();
    if (getenv('COOKBOOK_HIDE_DB_ERRORS') === '1') {
        $message = 'Не удалось сохранить данные. Попробуйте позже.';
    }

    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
}
