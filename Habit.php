<?php
session_start();
$headerColor = '#AB6CD9'; 
require_once 'Config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: Index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$habit_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $del = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
        $del->execute([$habit_id, $user_id]);
        header("Location: Index.php");
        exit();
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'toggle') {
        $today = date('Y-m-d');
        $check = $pdo->prepare("SELECT id FROM habit_logs WHERE habit_id = ? AND date = ?");
        $check->execute([$habit_id, $today]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM habit_logs WHERE habit_id = ? AND date = ?")->execute([$habit_id, $today]);
        } else {
            $pdo->prepare("INSERT INTO habit_logs (habit_id, date) VALUES (?, ?)")->execute([$habit_id, $today]);
        }
    } else {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $frequency = $_POST['frequency'];
        // Собираем время из полей
        $time = $_POST['h1'] . $_POST['h2'] . ":" . $_POST['m1'] . $_POST['m2'];

        $update = $pdo->prepare("UPDATE habits SET title = ?, description = ?, frequency = ?, reminder_time = ? WHERE id = ? AND user_id = ?");
        $update->execute([$title, $description, $frequency, $time, $habit_id, $user_id]);
    }
    header("Location: Habit.php?id=" . $habit_id);
    exit();
}

$stmt = $pdo->prepare("SELECT h.*, (SELECT id FROM habit_logs WHERE habit_id = h.id AND date = CURDATE()) as completed FROM habits h WHERE h.id = ? AND h.user_id = ?");
$stmt->execute([$habit_id, $user_id]);
$habit = $stmt->fetch();

$time_parts = str_replace(':', '', $habit['reminder_time'] ?? '0000');
$h1 = $time_parts[0] ?? '0'; $h2 = $time_parts[1] ?? '0'; $m1 = $time_parts[2] ?? '0'; $m2 = $time_parts[3] ?? '0';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        
        body { 
            background-color: #1B1E28; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            overflow-x: hidden; /* Убираем горизонтальную прокрутку */
        }

        header { width: 100%; height: 80px; background-color: #AB6CD9; display: flex; justify-content: space-between; align-items: center; padding: 0 50px; }
        
        .main-container {
            width: 1200px; 
            background-color: #2A2D3A; 
            border-bottom-left-radius: 30px; 
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 50px rgba(155, 77, 204, 0.4); 
            padding: 40px; 
            min-height: 500px; position: relative;
            margin-bottom: 40px;
        }

        .back-container { position: absolute; top: 40px; right: 40px; }
        .back-icon { width: auto; height: auto; cursor: pointer; }

        .habit-header { display: flex; align-items: center; gap: 20px; margin-bottom: 50px; margin-top: 40px; }
        
        .check-btn {
            width: 80px; height: 80px; background-color: #1B1E28; border-radius: 20px;
            border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        .title-input {
            background: transparent; border: none; color: #fff; font-size: 42px; 
            font-weight: 900; text-transform: uppercase; outline: none; width: 70%;
        }

        .content-layout { display: grid; grid-template-columns: 421px 1fr; gap: 40px; }
        .label { color: #ffffff; font-size: 16px; display: block; margin-bottom: 15px; }

        .freq-box {
            width: 421px; height: 75px; background-color: #1B1E28; border-radius: 25px;
            display: flex; align-items: center; padding: 0 25px; margin-bottom: 35px;
        }
        .select-ui { width: 100%; background: transparent; border: none; font-size: 18px; color: #fff; outline: none; }
        .select-ui option { background-color: #1B1E28; }

        .time-row { display: flex; align-items: center; gap: 10px; }
        .time-cell {
            width: 90px; height: 160px; background-color: #9B4DCC; 
            display: flex; align-items: center; justify-content: center;
        }
        
        /* Изменено на text-input для исключения прокрутки */
        .time-cell input {
            width: 100%; background: transparent; border: none; text-align: center;
            font-size: 100px; font-weight: 900; color: #fff; outline: none;
            -moz-appearance: textfield;
        }
        /* Убираем стрелочки на всякий случай */
        .time-cell input::-webkit-outer-spin-button,
        .time-cell input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .time-divider { font-size: 80px; font-weight: 900; color: white; }

        .right-side { display: flex; flex-direction: column; }
        .desc-area {
            background-color: #1B1E28; border-radius: 25px; padding: 25px;
            height: 220px; margin-bottom: 20px;
        }
        .desc-area textarea {
            width: 100%; height: 100%; background: transparent; border: none;
            font-size: 18px; color: #fff; outline: none; resize: none;
        }

        .btn-group { display: flex; gap: 20px; }

        .btn-save, .btn-delete {
            flex: 1; border: none; padding: 20px; border-radius: 30px; 
            font-weight: 900; font-size: 20px; cursor: pointer; text-transform: uppercase;
        }

        .btn-save { background-color: #A7FF0A; color: #000; }
        .btn-delete { background-color: #E5484D; color: #000; }
        
    </style>
</head>
<body>

    <?php include 'Components/Header.php'; ?>

    <form class="main-container" method="POST">
        <div class="back-container">
            <a href="Index.php"><img src="Img/UI/No.png" class="back-icon"></a>
        </div>

        <div class="habit-header">
            <button type="submit" name="action" value="toggle" class="check-btn">
                <?php if ($habit['completed']): ?>
                    <img src="Img/UI/Mark.png">
                <?php endif; ?>
            </button>
            <input type="text" name="title" class="title-input" value="<?php echo htmlspecialchars($habit['title']); ?>">
        </div>

        <div class="content-layout">
            <div class="left-side">
                <span class="label">Частота</span>
                <div class="freq-box">
                    <select name="frequency" class="select-ui">
                        <option value="daily" <?php if($habit['frequency']=='daily') echo 'selected'; ?>>Ежедневно</option>
                        <option value="weekly" <?php if($habit['frequency']=='weekly') echo 'selected'; ?>>Еженедельно</option>
                    </select>
                </div>

                <span class="label">Время напоминания</span>
                <div class="time-row">
                    <!-- Заменил type="number" на type="text" -->
                    <div class="time-cell"><input type="text" maxlength="1" name="h1" value="<?php echo $h1; ?>" oninput="nextI(this)" class="no-scroll"></div>
                    <div class="time-cell"><input type="text" maxlength="1" name="h2" value="<?php echo $h2; ?>" oninput="nextI(this)" class="no-scroll"></div>
                    <span class="time-divider">:</span>
                    <div class="time-cell"><input type="text" maxlength="1" name="m1" value="<?php echo $m1; ?>" oninput="nextI(this)" class="no-scroll"></div>
                    <div class="time-cell"><input type="text" maxlength="1" name="m2" value="<?php echo $m2; ?>" oninput="nextI(this)" class="no-scroll"></div>
                </div>
            </div>

            <div class="right-side">
                <span class="label">Описание</span>
                <div class="desc-area">
                    <textarea name="description"><?php echo htmlspecialchars($habit['description']); ?></textarea>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn-save">СОХРАНИТЬ</button>
                    <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Удалить эту привычку?')">УДАЛИТЬ</button>
                </div>
            </div>
        </div>
    </form>

    <script>
    function nextI(el) {
        // Оставляем только цифры
        el.value = el.value.replace(/[^0-9]/g, '');
        
        if (el.value.length === 1) {
            const ins = Array.from(document.querySelectorAll('.time-cell input'));
            const i = ins.indexOf(el);
            if (i < ins.length - 1) ins[i + 1].focus();
        }
    }

    // Дополнительная защита от случайного скролла
    window.addEventListener('keydown', function(e) {
        if([32, 37, 38, 39, 40].indexOf(e.keyCode) > -1) {
            if(e.target.tagName !== "TEXTAREA" && e.target.tagName !== "INPUT") {
                e.preventDefault();
            }
        }
    }, false);
    </script>
</body>
</html>