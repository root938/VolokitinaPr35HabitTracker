<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация/вход</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="Appearance/Registration.css">
</head>
<body>

    <header id="mainHeader">
        <img src="Img/UI/Name.png" alt="Name" class="logo-icon">
    </header>

    <div id="modal-overlay" onclick="closeModal()"></div>
    <div id="error-modal">
        <h3 style="color: #FF006E; margin-bottom: 10px;">ОЙ!</h3>
        <p id="error-text"></p>
        <button onclick="closeModal()">ПОНЯТНО</button>
    </div>

    <div class="container">
        <form class="blob-form login" id="loginForm">
            <h2>Вход</h2>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <button type="submit">Войти</button>
            <div class="switch">
                Нет аккаунта? <span onclick="toggleForm()" tabindex="0">Регистрация</span>
            </div>
        </form>

        <form class="blob-form register hidden" id="registerForm">
            <h2>Регистрация</h2>
            <div class="input-group">
                <input type="text" name="username" placeholder="Имя" required>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <button type="submit">Создать аккаунт</button>
            <div class="switch">
                Уже есть аккаунт? <span onclick="toggleForm()" tabindex="0">Войти</span>
            </div>
        </form>
    </div>

    <script src="Interactive/Registration.js"></script>
</body>
</html>