<?php
declare(strict_types=1);

/**
 * Подключение к БД и выборка данных для сайта «Кулинарная книга».
 * Схема: PostgreSQL (файл database/schema.sql).
 *
 * Параметры подключения: переменные окружения COOKBOOK_DB_* и/или файл config.local.php
 * (скопируйте config.local.php.example → config.local.php).
 *
 * Использование в других скриптах:
 *   require_once __DIR__ . '/script.php';
 *   $pdo = cookbook_connect_pdo();
 *   $recipes = cookbook_fetch_recipe_submissions($pdo);
 */

/**
 * @return array{host: string, port: string, dbname: string, user: string, password: string}
 */
function cookbook_db_config(): array
{
    $local = [];
    $localPath = __DIR__ . '/config.local.php';
    if (is_file($localPath)) {
        $loaded = require $localPath;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    return [
        'host' => getenv('COOKBOOK_DB_HOST') ?: ($local['host'] ?? 'localhost'),
        'port' => getenv('COOKBOOK_DB_PORT') ?: ($local['port'] ?? '5432'),
        'dbname' => getenv('COOKBOOK_DB_NAME') ?: ($local['dbname'] ?? 'cookbook'),
        'user' => getenv('COOKBOOK_DB_USER') ?: ($local['user'] ?? 'postgres'),
        'password' => getenv('COOKBOOK_DB_PASSWORD') !== false
            ? (string) getenv('COOKBOOK_DB_PASSWORD')
            : ($local['password'] ?? ''),
    ];
}

/**
 * Устанавливает соединение с PostgreSQL через PDO.
 *
 * @throws PDOException при ошибке подключения
 */
function cookbook_connect_pdo(): PDO
{
    if (!extension_loaded('pdo_pgsql')) {
        throw new RuntimeException(
            'Не загружено расширение PHP pdo_pgsql. Включите в php.ini строки extension=pgsql и extension=pdo_pgsql и перезапустите веб-сервер.'
        );
    }

    $c = cookbook_db_config();
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $c['host'],
        $c['port'],
        $c['dbname']
    );

    return new PDO($dsn, $c['user'], $c['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/**
 * Рецепты, добавленные через форму (таблица recipe_submissions), новые сверху.
 *
 * @return list<array<string, mixed>>
 */
function cookbook_fetch_recipe_submissions(PDO $pdo): array
{
    $sql = <<<'SQL'
        SELECT
            id,
            title,
            short_description,
            image_path,
            category,
            created_at
        FROM recipe_submissions
        ORDER BY created_at DESC, id DESC
        SQL;

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll();
}

/**
 * Один рецепт по id (страница подробностей).
 *
 * @return array<string, mixed>|null
 */
function cookbook_fetch_recipe_submission_by_id(PDO $pdo, int $id): ?array
{
    if ($id < 1) {
        return null;
    }

    $sql = <<<'SQL'
        SELECT
            id,
            title,
            short_description,
            image_path,
            category,
            body,
            created_at
        FROM recipe_submissions
        WHERE id = :id
        LIMIT 1
        SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Последние сообщения обратной связи (для админки или отладки).
 * Лимит по умолчанию — 50 записей.
 *
 * @return list<array<string, mixed>>
 */
function cookbook_fetch_feedback_recent(PDO $pdo, int $limit = 50): array
{
    $limit = max(1, min($limit, 500));
    $sql = sprintf(
        <<<'SQL'
            SELECT id, name, email, topic_code, message, created_at
            FROM feedback_submissions
            ORDER BY created_at DESC
            LIMIT %d
            SQL,
        $limit
    );

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll();
}

/**
 * Сохранение сообщения формы обратной связи.
 *
 * @throws InvalidArgumentException при неверном коде темы
 */
function cookbook_insert_feedback(
    PDO $pdo,
    string $name,
    string $email,
    int $topicCode,
    string $message
): int {
    if (!in_array($topicCode, [1, 2, 3], true)) {
        throw new InvalidArgumentException('Некорректный код темы.');
    }

    $stmt = $pdo->prepare(
        <<<'SQL'
            INSERT INTO feedback_submissions (name, email, topic_code, message)
            VALUES (:name, :email, :topic_code, :message)
            RETURNING id
            SQL
    );
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'topic_code' => $topicCode,
        'message' => $message,
    ]);

    return (int) $stmt->fetchColumn();
}

/**
 * Сохранение заявки с формы «Добавить рецепт».
 * image_path — имя файла в каталоге images или null.
 */
function cookbook_insert_recipe_submission(
    PDO $pdo,
    string $title,
    string $body,
    ?string $category = null,
    ?string $shortDescription = null,
    ?string $imagePath = null
): int {
    $stmt = $pdo->prepare(
        <<<'SQL'
            INSERT INTO recipe_submissions (title, category, short_description, body, image_path)
            VALUES (:title, :category, :short_description, :body, :image_path)
            RETURNING id
            SQL
    );
    $stmt->execute([
        'title' => $title,
        'category' => $category,
        'short_description' => $shortDescription,
        'body' => $body,
        'image_path' => $imagePath,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false || !isset($row['id'])) {
        throw new RuntimeException('INSERT не вернул id новой записи.');
    }

    return (int) $row['id'];
}

/**
 * Простая проверка email для серверной валидации.
 */
function cookbook_is_valid_email(string $email): bool
{
    return (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
}

/**
 * Длина строки в символах UTF-8. Не требует mbstring (есть запасной вариант через PCRE).
 */
function cookbook_utf8_strlen(string $s): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($s, 'UTF-8');
    }
    if (preg_match_all('/./u', $s, $matches) !== false) {
        return count($matches[0]);
    }

    return strlen($s);
}
