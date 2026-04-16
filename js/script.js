// script.js – выполняется после полной загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    function focusRecipeSearch() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) {
            return;
        }
        searchInput.focus({ preventScroll: false });
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function maybeFocusRecipeSearchFromHash() {
        if (window.location.hash === '#recipeSearch') {
            focusRecipeSearch();
        }
    }

    maybeFocusRecipeSearchFromHash();
    window.addEventListener('hashchange', maybeFocusRecipeSearchFromHash);

    document.querySelectorAll('a.nav-search-link').forEach(function (link) {
        link.addEventListener('click', function () {
            window.setTimeout(focusRecipeSearch, 0);
        });
    });
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
            if (topic === '' || topic === 'Выберите тему') {
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

            console.log('Отправка формы:', { name: name, email: email, topic: topic, message: message });

            const topicNum = parseInt(topic, 10);
            fetch('feedback_submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    topic: topicNum,
                    message: message
                })
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, error: 'Некорректный ответ сервера.' };
                    });
                })
                .then(function (data) {
                    if (data.ok) {
                        showModal('Успешно', 'Сообщение сохранено в базе данных.');
                        feedbackForm.reset();
                    } else {
                        showModal('Ошибка', data.error || 'Не удалось сохранить данные.');
                    }
                })
                .catch(function () {
                    showModal('Ошибка', 'Нет соединения с сервером или PHP не настроен.');
                });
        });
    }

    // --- Форма «Добавить рецепт» (form_recipe.html) ---
    const recipeForm = document.getElementById('recipeForm');
    if (recipeForm) {
        const categoryDisplay = document.getElementById('recipeCategoryDisplay');
        const categoryHidden = document.getElementById('recipeCategoryHidden');
        const categoryPanel = document.getElementById('recipeCategoryPanel');
        const categoryToggle = document.getElementById('recipeCategoryToggle');
        const categoryOtherWrap = document.getElementById('recipeCategoryOtherWrap');
        const categoryOther = document.getElementById('recipeCategoryOther');
        let categoryIsOtherMode = false;

        function resetRecipeCategoryPicker() {
            categoryIsOtherMode = false;
            if (categoryHidden) {
                categoryHidden.value = '';
            }
            if (categoryDisplay) {
                categoryDisplay.value = '';
                categoryDisplay.setAttribute('aria-expanded', 'false');
            }
            if (categoryToggle) {
                categoryToggle.setAttribute('aria-expanded', 'false');
            }
            if (categoryOtherWrap) {
                categoryOtherWrap.classList.add('d-none');
            }
            if (categoryOther) {
                categoryOther.value = '';
            }
            if (categoryPanel) {
                categoryPanel.classList.remove('is-open');
                categoryPanel.setAttribute('aria-hidden', 'true');
            }
        }

        if (categoryPanel && categoryHidden && categoryDisplay) {
            const optionButtons = categoryPanel.querySelectorAll('.recipe-category-option[data-value]');

            function setCategoryOpen(open) {
                categoryPanel.classList.toggle('is-open', open);
                categoryPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
                categoryDisplay.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (categoryToggle) {
                    categoryToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
            }

            function closeCategoryPanel() {
                setCategoryOpen(false);
            }

            function chooseCategory(value) {
                if (value === '__other__') {
                    categoryIsOtherMode = true;
                    categoryHidden.value = '';
                    categoryDisplay.value = 'Другое';
                    if (categoryOtherWrap) {
                        categoryOtherWrap.classList.remove('d-none');
                    }
                    if (categoryOther) {
                        categoryOther.focus();
                    }
                    return;
                }
                categoryIsOtherMode = false;
                if (categoryOtherWrap) {
                    categoryOtherWrap.classList.add('d-none');
                }
                if (categoryOther) {
                    categoryOther.value = '';
                }
                categoryHidden.value = value;
                categoryDisplay.value = value;
                closeCategoryPanel();
            }

            categoryDisplay.addEventListener('click', function () {
                setCategoryOpen(!categoryPanel.classList.contains('is-open'));
            });

            if (categoryToggle) {
                categoryToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    setCategoryOpen(!categoryPanel.classList.contains('is-open'));
                });
            }

            optionButtons.forEach(function (btn) {
                btn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    const val = btn.getAttribute('data-value');
                    if (val) {
                        chooseCategory(val);
                    }
                });
            });

            if (categoryOther) {
                categoryOther.addEventListener('input', function () {
                    if (categoryIsOtherMode) {
                        categoryHidden.value = categoryOther.value.trim();
                    }
                });
            }

            categoryPanel.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            document.addEventListener('click', function (event) {
                if (!categoryPanel.classList.contains('is-open')) {
                    return;
                }
                const field = categoryDisplay.closest('.recipe-category-field');
                if (field && field.contains(event.target)) {
                    return;
                }
                closeCategoryPanel();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && categoryPanel.classList.contains('is-open')) {
                    closeCategoryPanel();
                }
            });
        }

        recipeForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const title = document.getElementById('recipeTitle').value.trim();
            const text = document.getElementById('recipeText').value.trim();

            let err = '';
            if (title === '') {
                err += 'Укажите название рецепта.\n';
            }
            if (text === '') {
                err += 'Заполните описание приготовления.\n';
            }

            if (categoryHidden) {
                if (categoryIsOtherMode && categoryOther) {
                    categoryHidden.value = categoryOther.value.trim();
                }
                if (!categoryHidden.value.trim()) {
                    err += 'Выберите категорию из списка. Для пункта «Другое» введите название категории.\n';
                }
            }

            if (err !== '') {
                showModal('Ошибка ввода', err.replace(/\n/g, '<br>'));
                return;
            }

            const formData = new FormData(recipeForm);

            fetch('recipe_submit.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return { ok: false, error: 'Некорректный ответ сервера.' };
                    });
                })
                .then(function (data) {
                    if (data.ok) {
                        showModal('Успешно', 'Рецепт сохранён в базе данных.');
                        recipeForm.reset();
                        resetRecipeCategoryPicker();
                    } else {
                        showModal('Ошибка', data.error || 'Не удалось сохранить данные.');
                    }
                })
                .catch(function () {
                    showModal('Ошибка', 'Нет соединения с сервером или PHP не настроен.');
                });
        });
    }

    // --- 2. ПОИСК ПО РЕЦЕПТАМ (страница list.php) ---
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
