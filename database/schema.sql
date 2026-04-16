-- Схема БД «Кулинарная книга» (PostgreSQL)
--
-- Создание базы (выполнить от имени суперпользователя, при необходимости):
--   CREATE DATABASE cookbook OWNER your_user;
--   \c cookbook
--
-- Применение схемы:
--   psql -U your_user -d cookbook -f database/schema.sql

-- Сообщения формы обратной связи (form.html: имя, email, тема, сообщение)
CREATE TABLE IF NOT EXISTS feedback_submissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(255) NOT NULL,
    topic_code SMALLINT NOT NULL CHECK (topic_code IN (1, 2, 3)),
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_feedback_submissions_created
    ON feedback_submissions (created_at DESC);

COMMENT ON TABLE feedback_submissions IS 'Данные из формы обратной связи';
COMMENT ON COLUMN feedback_submissions.topic_code IS '1 — предложить рецепт, 2 — сообщить об ошибке, 3 — другой вопрос';

-- Заявки на добавление рецепта с формы form_recipe.html
CREATE TABLE IF NOT EXISTS recipe_submissions (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(120),
    short_description TEXT,
    body TEXT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_recipe_submissions_created
    ON recipe_submissions (created_at DESC);

COMMENT ON TABLE recipe_submissions IS 'Рецепты из формы «Добавить рецепт»; отображаются в list.php и recipe.php';
COMMENT ON COLUMN recipe_submissions.image_path IS 'Имя файла в каталоге images после загрузки, либо NULL';
