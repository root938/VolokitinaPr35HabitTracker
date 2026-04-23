// Interactive/Registration.js

function toggleForm() {
    const login = document.getElementById("loginForm");
    const register = document.getElementById("registerForm");
    const header = document.getElementById("mainHeader");

    login.classList.toggle("hidden");
    register.classList.toggle("hidden");

    header.style.backgroundColor = register.classList.contains("hidden") ? "#FF3F81" : "#AB6CD9";
}

function showError(msg) {
    document.getElementById('error-text').innerText = msg;
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('error-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('error-modal').style.display = 'none';
}

// Проверка: только буквы (русские или английские)
function isOnlyLetters(str) {
    return /^[a-zA-Zа-яА-ЯёЁ]+$/.test(str);
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

document.querySelectorAll('form').forEach(form => {
    form.onsubmit = async (e) => {
        e.preventDefault();
        
        const action = form.id === 'loginForm' ? 'login' : 'register';
        const email = form.querySelector('input[name="email"]').value.trim();
        const password = form.querySelector('input[name="password"]').value.trim();

        // 1. ПРОВЕРКА ИМЕНИ (только для регистрации)
        if (action === 'register') {
            const username = form.querySelector('input[name="username"]').value.trim();
            if (!isOnlyLetters(username)) {
                return showError("В имени можно использовать только буквы!");
            }
            if (username.length < 2) {
                return showError("Имя слишком короткое");
            }
        }

        // 2. ПРОВЕРКА EMAIL
        if (!isValidEmail(email)) {
            return showError("Введите корректный Email");
        }

        // 3. ПРОВЕРКА ПАРОЛЯ (строго от 6 символов)
        if (password.length < 6) {
            return showError("Пароль должен содержать не менее 6 символов!");
        }

        // ОТПРАВКА
        const formData = new FormData(form);
        formData.append('action', action);

        try {
            const response = await fetch('Logic/Auth.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                window.location.href = 'Index.php';
            } else {
                showError(result.message);
            }
        } catch (err) {
            showError("Ошибка сервера. Проверьте соединение с БД.");
        }
    };
});