<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/script.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Некорректный формат запроса.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$topicRaw = $data['topic'] ?? null;
$message = trim((string) ($data['message'] ?? ''));

$topicCode = null;
if (is_int($topicRaw)) {
    $topicCode = $topicRaw;
} elseif (is_string($topicRaw) && $topicRaw !== '' && ctype_digit($topicRaw)) {
    $topicCode = (int) $topicRaw;
} elseif (is_numeric($topicRaw)) {
    $topicCode = (int) $topicRaw;
}

if ($name === '' || cookbook_utf8_strlen($name) > 200) {
    echo json_encode(['ok' => false, 'error' => 'Укажите имя (до 200 символов).'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($email === '' || !cookbook_is_valid_email($email)) {
    echo json_encode(['ok' => false, 'error' => 'Укажите корректный email.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($topicCode === null || !in_array($topicCode, [1, 2, 3], true)) {
    echo json_encode(['ok' => false, 'error' => 'Выберите тему обращения.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'Введите сообщение.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = cookbook_connect_pdo();
    $id = cookbook_insert_feedback($pdo, $name, $email, $topicCode, $message);
    echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить данные. Попробуйте позже.'], JSON_UNESCAPED_UNICODE);
}
