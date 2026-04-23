<?php
// Используем переменную для цвета, если она задана, иначе стандартный розовый
$bgColor = isset($headerColor) ? $headerColor : '#FF3F81';
?>

<style>
    /* Шапка */
    header {
        height: 80px;
        background-color: <?php echo $bgColor; ?>;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 30px;
        position: relative;
        z-index: 100;
    }

    /* Значки в шапке — увеличиваем размеры */
    .menu-icon {
        width: 50px; /* Было маленькое, увеличили */
        height: auto;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .menu-icon:hover { transform: scale(1.1); }

    .logo-icon {
        height: 50px; /* Увеличили высоту логотипа */
        width: auto;
    }

    /* Модальное окно (фон) */
    .modal-overlay {
        display: none; 
        position: fixed; 
        top: 0; left: 0; 
        width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        justify-content: center;
        align-items: center;
    }

    /* Само меню */
    .modal-menu {
        width: 800px;
        height: 600px;
        background-color: #2A2D3A;
        border: 3px solid #FF3F81;
        border-radius: 50px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 0 40px rgba(255, 63, 129, 0.4);
    }

    /* Кнопка закрытия внутри меню */
    .close-btn-img {
        position: absolute;
        top: 30px;
        right: 40px;
        width: 45px;
        height: 45px;
        cursor: pointer;
    }

    /* Список ссылок */
    .menu-list {
        list-style: none;
        padding: 0;
        margin-left: 80px;
    }

    .menu-list li {
        margin: 15px 0;
    }

    .menu-list a {
        color: white;
        font-size: 32px; /* Крупный шрифт для меню */
        font-weight: 900;
        text-decoration: none;
        text-transform: uppercase;
        font-family: 'Montserrat', sans-serif;
        transition: color 0.3s;
    }

    .menu-list a:hover {
        color: #A7FF0A; /* Твой фирменный салатовый при наведении */
    }
</style>

<div class="modal-overlay" id="menuOverlay">
    <div class="modal-menu">
        <img src="Img/UI/No.png" alt="Close" class="close-btn-img" onclick="toggleMenu()">
        
        <ul class="menu-list">
            <li><a href="profile.php">Профиль</a></li>
            <li><a href="index.php">Главная</a></li>
            <li><a href="add_habit.php">Создание привычек</a></li>
            <li><a href="calendar.php">Календарь</a></li>
            <li><a href="statistics.php">Статистика</a></li>
            <li><a href="achievements.php">Достижения</a></li>
            <li><a href="history.php">История</a></li>
        </ul>
    </div>
</div>

<header>
    <img src="Img/UI/Menu.png" alt="Menu" class="menu-icon" onclick="toggleMenu()">
    <img src="Img/UI/Name.png" alt="Title" class="logo-icon">
</header>

<script>
function toggleMenu() {
    const overlay = document.getElementById('menuOverlay');
    if (overlay.style.display === 'flex') {
        overlay.style.display = 'none';
    } else {
        overlay.style.display = 'flex';
    }
}

// Закрытие при клике на темный фон (вне меню)
window.onclick = function(event) {
    const overlay = document.getElementById('menuOverlay');
    if (event.target == overlay) {
        overlay.style.display = "none";
    }
}
</script>