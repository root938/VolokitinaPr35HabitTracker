<?php 
    // Устанавливаем розовый цвет шапки
    $headerColor = '#FF3F81'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новая привычка</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@200;400;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="Appearance/CreateHabit.css">
</head>

<body>

    <?php include 'Components/Header.php'; ?>

    <div class="wrapper">
        <div class="form">
            <div class="title">Новая привычка</div>
            <form action="Logic/SaveHabit.php" method="POST">
                <div class="columns">
                    <div class="col">
                        <div class="label">Название (макс 100)</div>
                        <input class="input" name="title" maxlength="100" required placeholder="Например: Пробежка">
                        <div class="label">Описание</div>
                        <textarea class="textarea" name="description" placeholder="Например: Бегать 30 минут утром"></textarea>
                    </div>

                    <div class="col">
                        <div class="label">Частота</div>
                        <select class="select" name="frequency" required>
                            <option value="daily">Ежедневно</option>
                            <option value="weekly">Еженедельно</option>
                        </select>

                        <div class="label">Время напоминания</div>
                        <div class="time-display">
                            <div class="digit"><input type="number" name="h1" min="0" max="2" required oninput="limitAndNext(this)"></div>
                            <div class="digit"><input type="number" name="h2" min="0" max="9" required oninput="limitAndNext(this)"></div>
                            <span class="colon">:</span>
                            <div class="digit"><input type="number" name="m1" min="0" max="5" required oninput="limitAndNext(this)"></div>
                            <div class="digit"><input type="number" name="m2" min="0" max="9" required oninput="limitAndNext(this)"></div>
                        </div>
                    </div>
                </div>

                <div class="button">
                    <button type="submit">Создать</button>
                </div>
            </form>
        </div>
    </div>

    <script src="Interactive/CreateHabit.js"></script>

</body>
</html>