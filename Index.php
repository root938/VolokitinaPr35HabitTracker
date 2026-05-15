<?php
session_start();
require_once 'Config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ЛОГИКА TOGGLE (ОТМЕТИТЬ / СНЯТЬ ОТМЕТКУ)
if (isset($_GET['check'])) {
    $hid = (int)$_GET['check'];
    $today = date('Y-m-d');

    // Проверяем, есть ли уже отметка на сегодня
    $checkStmt = $pdo->prepare("SELECT id FROM habit_logs WHERE habit_id = ? AND date = ?");
    $checkStmt->execute([$hid, $today]);
    $existingLog = $checkStmt->fetch();

    if ($existingLog) {
        // Если есть — удаляем (отмена выполнения)
        $deleteStmt = $pdo->prepare("DELETE FROM habit_logs WHERE habit_id = ? AND date = ?");
        $deleteStmt->execute([$hid, $today]);
    } else {
        // Если нет — добавляем
        $insertStmt = $pdo->prepare("INSERT INTO habit_logs (habit_id, date) VALUES (?, ?)");
        $insertStmt->execute([$hid, $today]);
    }

    header("Location: Index.php");
    exit();
}

// Получаем привычки
$stmt = $pdo->prepare("
    SELECT h.*, 
    (SELECT id FROM habit_logs WHERE habit_id = h.id AND date = CURDATE()) as completed
    FROM habits h 
    WHERE h.user_id = ? AND h.is_active = 1
    ORDER BY h.reminder_time ASC
");
$stmt->execute([$user_id]);
$habits = $stmt->fetchAll();

// Расчет прогресса
$totalHabits = count($habits);
$completedCount = 0;
foreach ($habits as $h) { 
    if ($h['completed']) $completedCount++; 
}
$progressPercent = $totalHabits > 0 ? ($completedCount / $totalHabits) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Habit Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: #1B1E28;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* Шапка: Название Слева, Меню Справа */
        header, .header-main { 
            width: 100% !important; 
            background-color: #AB6CD9 !important; 
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 0 50px !important; 
            height: 80px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 100;
        }

        /* ОСНОВНОЙ БЛОК */
        .main-container {
            width: 1200px;
            background-color: #2A2D3A;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 50px rgba(155, 77, 204, 0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 20px; 
            min-height: <?php echo empty($habits) ? '420px' : 'auto'; ?>;
            margin-bottom: 50px;
        }

        .progress-bar-container {
            width: 1100px;
            height: 60px;
            background-color: #1B1E28;
            border-radius: 35px;
            border: 2px solid #9B4DCC;
            display: flex;
            align-items: center;
            padding: 0 5px;
            margin-bottom: 40px;
        }

        .progress-fill {
            height: 50px;
            background-color: #A7FF0A;
            border-radius: 30px;
            transition: width 0.6s ease;
            width: <?php echo ($progressPercent > 0) ? "calc($progressPercent% - 10px)" : "50px"; ?>;
            min-width: 50px;
        }

        .habit-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 1100px;
        }

        .habit-card {
            width: 1100px;
            height: 130px;
            background-color: #1B1E28;
            border-radius: 20px;
            display: flex;
            /* Разворачиваем, чтобы кнопка была слева */
            flex-direction: row-reverse; 
            justify-content: flex-end; 
            align-items: center;
            padding: 0 40px;
            gap: 30px; /* Отступ между кнопкой и текстом */
        }

        .habit-title {
            font-size: 36px;
            font-weight: 900;
            color: #ffffff;
        }

        .check-btn {
            width: 80px;
            height: 80px;
            background-color: #2A2D3A;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            flex-shrink: 0;
        }

        .check-btn img {
            width: auto;
            height: auto;
            object-fit: cover;
        }

        .add-habit-fixed {
            position: fixed;
            bottom: 40px;
            right: 40px;
            z-index: 1000;
        }

        .add-habit-fixed img {
            width: 90px;
        }

        .empty-text {
            color: #444;
            font-size: 24px;
            font-weight: 900;
            margin-top: 60px;
        }
    </style>
</head>
<body>

    <!-- Шапка: Название слева, Меню справа -->
    <?php include 'Components/Header.php'; ?>

    <div class="main-container">
        
        <div class="progress-bar-container">
            <div class="progress-fill"></div>
        </div>

        <div class="habit-list">
            <?php if (empty($habits)): ?>
                <div class="empty-text">СПИСОК ПУСТ</div>
            <?php else: ?>
                <?php foreach ($habits as $habit): ?>
                    <div class="habit-card">
                        <!-- Название будет идти после кнопки из-за row-reverse + flex-end -->
                        <div class="habit-title">
    <a href="Habit.php?id=<?php echo $habit['id']; ?>" style="color: inherit; text-decoration: none;">
        <?php echo htmlspecialchars($habit['title']); ?>
    </a>
</div>
                        
                        <!-- Кнопка будет слева -->
                        <a href="?check=<?php echo $habit['id']; ?>" class="check-btn">
                            <?php if ($habit['completed']): ?>
                                <img src="Img/UI/Mark.png" alt="OK">
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <a href="CreateHabit.php" class="add-habit-fixed">
        <img src="Img/UI/Add.png" alt="+">
    </a>

</body>
</html>