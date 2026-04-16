<?php
declare(strict_types=1);

require_once __DIR__ . '/script.php';

/** @var list<array<string, mixed>> $recipes */
$recipes = [];
$dbError = null;

try {
    $pdo = cookbook_connect_pdo();
    $recipes = cookbook_fetch_recipe_submissions($pdo);
} catch (Throwable $e) {
    $dbError = 'Не удалось загрузить рецепты. Проверьте настройки подключения к базе данных.';
}

/**
 * @param mixed $value
 */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Кулинарная книга – Рецепты</title>
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
      <!-- Шапка -->
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
                  <a class="nav-link active" aria-current="page" href="list.php"
                    >Рецепты</a
                  >
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

      <!-- Контент -->
      <main class="py-4">
        <div class="container">
          <h2 class="section-title text-center mb-4">Рецепты</h2>

          <div class="row justify-content-center mb-4" id="recipeSearch">
            <div class="col-md-6">
              <input type="search" id="searchInput" class="form-control" placeholder="Поиск по названию или описанию..." autocomplete="off">
            </div>
          </div>

          <?php if ($dbError !== null) : ?>
            <div class="alert alert-warning text-center" role="alert">
              <?php echo h($dbError); ?>
            </div>
          <?php elseif (count($recipes) === 0) : ?>
            <p class="text-center text-muted">Пока нет ни одного рецепта в базе данных.</p>
          <?php else : ?>
            <?php foreach ($recipes as $item) :
                $imgName = (string) ($item['image_path'] ?? '');
                if ($imgName !== '') {
                    $imgSrc = (strpos($imgName, '/') !== false || strpos($imgName, '\\') !== false)
                        ? $imgName
                        : 'images/' . $imgName;
                } else {
                    $imgSrc = 'images/logo.png';
                }
                $title = (string) ($item['title'] ?? '');
                $description = (string) ($item['short_description'] ?? '');
                if ($description === '') {
                    $description = 'Описание не указано.';
                }
                $recipeId = (int) ($item['id'] ?? 0);
                $detailUrl = 'recipe.php?id=' . $recipeId;
                ?>
          <div class="card recipe-card">
            <div class="row g-3 align-items-center">
              <div class="col-md-3">
                <img
                  src="<?php echo h($imgSrc); ?>"
                  alt="<?php echo h($title); ?>"
                  class="recipe-thumb"
                />
              </div>
              <div class="col-md-6">
                <h5 class="recipe-card-title"><?php echo h($title); ?></h5>
                <p class="recipe-card-text">
                  <?php echo h($description); ?>
                </p>
              </div>
              <div class="col-md-3 text-md-end">
                <a href="<?php echo h($detailUrl); ?>" class="btn recipe-details-btn">
                  Подробнее
                </a>
              </div>
            </div>
          </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </main>

      <!-- Футер -->
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
