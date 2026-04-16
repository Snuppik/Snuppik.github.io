<?php
declare(strict_types=1);

require_once __DIR__ . '/script.php';

/**
 * @param mixed $value
 */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$recipe = null;
$dbError = null;

try {
    $pdo = cookbook_connect_pdo();
    $recipe = cookbook_fetch_recipe_submission_by_id($pdo, $id);
} catch (Throwable $e) {
    $dbError = 'Не удалось загрузить рецепт. Проверьте настройки подключения к базе данных.';
}

$pageTitle = 'Рецепт';
if ($recipe !== null) {
    $pageTitle = (string) ($recipe['title'] ?? 'Рецепт');
}
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo h($pageTitle); ?> – Кулинарная книга</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
  </head>
  <body>
    <div class="page-wrapper">
      <header>
        <nav class="navbar navbar-expand-lg navbar-custom">
          <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.html">
              <img
                src="images/logo.png"
                alt="Логотип"
                class="navbar-brand-logo-img"
              />
              <span class="navbar-brand-text">Кулинарная книга</span>
            </a>
            <button
              class="navbar-toggler"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#mainNavbar"
              aria-controls="mainNavbar"
              aria-expanded="false"
              aria-label="Toggle navigation"
            >
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
              <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                  <a class="nav-link" href="index.html">Главная</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="list.php">Рецепты</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="form.html">Контакты</a>
                </li>
              </ul>
              <div class="d-none d-lg-block">
                <a class="nav-search-link" href="list.php#recipeSearch" title="Поиск рецептов" aria-label="Поиск по рецептам">
                  <img src="images/search.png" alt="" width="22" height="22" class="nav-search-img" />
                </a>
              </div>
            </div>
          </div>
        </nav>
      </header>

      <main class="py-4">
        <div class="container">
          <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning text-center" role="alert">
              <?php echo h($dbError); ?>
            </div>
            <p class="text-center"><a href="list.php" class="btn back-btn">Назад к рецептам</a></p>
          <?php elseif ($recipe === null) : ?>
            <div class="recipe-detail-card mx-auto col-lg-10">
              <p class="mb-4">Рецепт не найден или указан неверный адрес.</p>
              <a href="list.php" class="btn back-btn">Назад к рецептам</a>
            </div>
          <?php else :
                $rTitle = (string) ($recipe['title'] ?? '');
                $rCategory = (string) ($recipe['category'] ?? '');
                $rShort = (string) ($recipe['short_description'] ?? '');
                $rBody = (string) ($recipe['body'] ?? '');
                $imgName = (string) ($recipe['image_path'] ?? '');
                if ($imgName !== '') {
                    $imgSrc = (strpos($imgName, '/') !== false || strpos($imgName, '\\') !== false)
                        ? $imgName
                        : 'images/' . $imgName;
                } else {
                    $imgSrc = '';
                }
                ?>
          <div class="recipe-detail-card mx-auto col-lg-10">
            <?php if ($imgSrc !== '') : ?>
            <div class="mb-4 text-center">
              <img src="<?php echo h($imgSrc); ?>" alt="<?php echo h($rTitle); ?>" class="img-fluid rounded recipe-detail-hero-img" />
            </div>
            <?php endif; ?>
            <h2 class="recipe-detail-title mb-3"><?php echo h($rTitle); ?></h2>
            <p class="recipe-detail-meta mb-4">
              <?php if ($rShort !== '') : ?>
                <?php echo h($rShort); ?> &nbsp;&nbsp;&nbsp;
              <?php endif; ?>
              <?php if ($rCategory !== '') : ?>
                Категория: &nbsp; <?php echo h($rCategory); ?>
              <?php else : ?>
                Категория не указана
              <?php endif; ?>
            </p>

            <h4 class="mb-3">Текст рецепта</h4>
            <div class="recipe-body mb-4"><?php echo h($rBody); ?></div>

            <a href="list.php" class="btn back-btn">Назад к рецептам</a>
          </div>
          <?php endif; ?>
        </div>
      </main>

      <footer class="site-footer">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-4 mb-4 mb-md-0">
              <h5>Контакты</h5>
              <p class="mb-1">тел.: 8&nbsp;991&nbsp;196&nbsp;68&nbsp;58</p>
              <p class="mb-0">
                email :
                <a href="mailto:ilya.markov.06@list.ru">ilya.markov.06@list.ru</a>
              </p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0 text-center">
              <img src="images/qr_code.png" alt="QR-код" class="qr-img" />
            </div>
            <div class="col-md-4 footer-copy">
              <p class="mb-0">
                © 2026 Кулинарная книга.<br />
                Все права защищены.
              </p>
            </div>
          </div>
        </div>
      </footer>
    </div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
      crossorigin="anonymous"
    ></script>
    <script src="js/script.js"></script>
  </body>
</html>
