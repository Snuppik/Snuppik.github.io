// script.js – выполняется после полной загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // --- 1. ОБРАБОТКА ФОРМЫ ОБРАТНОЙ СВЯЗИ (если мы на странице form.html) ---
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(event) {
            event.preventDefault(); // отключаем перезагрузку/переход

            // Получаем значения полей
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const topic = document.getElementById('topic').value; // select
            const message = document.getElementById('message').value.trim();

            // Простейшая валидация
            let errorMessage = '';

            if (name === '') {
                errorMessage += 'Поле "Имя" обязательно.\n';
            }
            if (email === '') {
                errorMessage += 'Поле "Email" обязательно.\n';
            } else if (!isValidEmail(email)) {
                errorMessage += 'Введите корректный email (должен содержать @ и точку).\n';
            }
            if (topic === 'Выберите тему') { // проверка, что выбрана не первая опция
                errorMessage += 'Выберите тему обращения.\n';
            }
            if (message === '') {
                errorMessage += 'Поле "Сообщение" обязательно.\n';
            }

            // Если есть ошибки – показываем модальное окно с ошибкой
            if (errorMessage !== '') {
                showModal('Ошибка ввода', errorMessage.replace(/\n/g, '<br>'));
                return;
            }

            // Данные корректны – выводим в консоль
            console.log('Отправка формы:');
            console.log('Имя:', name);
            console.log('Email:', email);
            console.log('Тема:', topic);
            console.log('Сообщение:', message);

            // Показываем сообщение об успехе
            showModal('Успешно', 'Форма успешно отправлена (данные в консоли).');

            // Очищаем поля (опционально)
            // feedbackForm.reset();
        });
    }

    // --- 2. ПОИСК ПО РЕЦЕПТАМ (если мы на странице list.html) ---
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        const recipeCards = document.querySelectorAll('.recipe-card'); // все карточки

        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim().toLowerCase();

            recipeCards.forEach(function(card) {
                // Находим заголовок и текст внутри карточки
                const titleElem = card.querySelector('.recipe-card-title');
                const textElem = card.querySelector('.recipe-card-text');

                const title = titleElem ? titleElem.textContent.toLowerCase() : '';
                const text = textElem ? textElem.textContent.toLowerCase() : '';

                // Если поисковый запрос пуст или совпадает с title/text – показываем, иначе скрываем
                if (query === '' || title.includes(query) || text.includes(query)) {
                    card.style.display = ''; // показываем (возвращаем стандартное отображение)
                } else {
                    card.style.display = 'none'; // скрываем
                }
            });
        });
    }

    // --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---

    // Проверка email (простая, но достаточная для лабораторной)
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Функция показа модального окна Bootstrap
    function showModal(title, message) {
        const modalTitle = document.getElementById('formModalLabel');
        const modalBody = document.getElementById('formModalBody');

        if (modalTitle && modalBody) {
            modalTitle.textContent = title;
            modalBody.innerHTML = message; // используем innerHTML, чтобы поддержать <br>

            // Создаём объект модального окна Bootstrap и показываем
            const modal = new bootstrap.Modal(document.getElementById('formModal'));
            modal.show();
        } else {
            // Если модальное окно не найдено (например, мы не на form.html) – просто alert
            alert(title + '\n' + message.replace(/<br>/g, '\n'));
        }
    }
});