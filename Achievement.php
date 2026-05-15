<?php
session_start();
require_once 'Config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// БЛОК АВТОМАТИЧЕСКОЙ ВЫДАЧИ ДОСТИЖЕНИЙ
// ==========================================

function giveAchievement($pdo, $user_id, $ach_name) {
    $check = $pdo->prepare("SELECT id FROM achievements WHERE user_id = ? AND name = ?");
    $check->execute([$user_id, $ach_name]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO achievements (user_id, name) VALUES (?, ?)");
        $ins->execute([$user_id, $ach_name]);
    }
}

// УСЛОВИЕ №1 (3.jpg): Первый заход в приложение
giveAchievement($pdo, $user_id, '3');

// УСЛОВИЕ №2 (4.jpg): Первая выполненная привычка
$stmtLogsCount = $pdo->prepare("
    SELECT COUNT(*) FROM habit_logs l
    JOIN habits h ON l.habit_id = h.id
    WHERE h.user_id = ?
");
$stmtLogsCount->execute([$user_id]);
$total_logs = $stmtLogsCount->fetchColumn();

if ($total_logs >= 1) {
    giveAchievement($pdo, $user_id, '4');
}

// УСЛОВИЕ №3 (5.jpg): Выполнены все добавленные привычки в первый день
$stmtFirstDay = $pdo->prepare("SELECT MIN(DATE(created_at)) FROM habits WHERE user_id = ?");
$stmtFirstDay->execute([$user_id]);
$first_day = $stmtFirstDay->fetchColumn();

if ($first_day) {
    $stmtHabitsCount = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = ? AND DATE(created_at) = ?");
    $stmtHabitsCount->execute([$user_id, $first_day]);
    $habits_created = $stmtHabitsCount->fetchColumn();

    if ($habits_created > 0) {
        $stmtLogsCountFirstDay = $pdo->prepare("
            SELECT COUNT(DISTINCT l.habit_id) 
            FROM habit_logs l
            JOIN habits h ON l.habit_id = h.id
            WHERE h.user_id = ? AND DATE(l.date) = ? AND DATE(h.created_at) = ?
        ");
        $stmtLogsCountFirstDay->execute([$user_id, $first_day, $first_day]);
        $logs_created = $stmtLogsCountFirstDay->fetchColumn();

        if ($logs_created === $habits_created) {
            giveAchievement($pdo, $user_id, '5');
        }
    }
}

// ==========================================
// БЛОК ПОЛУЧЕНИЯ ДАННЫХ ДЛЯ ОТОБРАЖЕНИЯ
// ==========================================

$stmt = $pdo->prepare("SELECT name FROM achievements WHERE user_id = ? ORDER BY CAST(name AS UNSIGNED) ASC");
$stmt->execute([$user_id]);
$user_achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_achievements = count($user_achievements);

if ($total_achievements === 0) {
    $display_count = 3;
} else {
    $display_count = ceil($total_achievements / 3) * 3;
}

// Массив с названиями для достижений
$achievement_titles = [
    '3' => 'Первый день в Fix',
    '4' => 'Первая привычка',
    '5' => 'Первый идеальный день'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Достижения</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        
        body { 
            background-color: #1B1E28; 
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        header, .header-main { 
            width: 100% !important; background-color: #AB6CD9 !important; 
            display: flex !important; justify-content: space-between !important; 
            align-items: center !important; padding: 0 50px !important; height: 80px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 100;
        }

        .achievements-wrapper {
            margin-top: 30px; 
            width: 1170px; 
            padding-bottom: 50px;
        }

        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(3, 370px);
            gap: 30px;
        }

        .achievement-block {
            width: 370px;
            height: 300px;
            background-color: #2A2D3A;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            padding: 20px;
        }

        .achievement-block.unlocked {
            background: radial-gradient(circle, #34384a 0%, #222531 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .achievement-block.unlocked:hover {
            transform: translateY(-5px);
            border-color: #A7FF0A; 
            box-shadow: 0 8px 25px rgba(167, 255, 10, 0.2);
        }

        .achievement-block.locked {
            background-color: #20222B;
            border: 2px dashed #2A2D3A;
        }

        .achievement-icon {
            width: 160px; 
            height: 160px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .achievement-block.unlocked:hover .achievement-icon {
            transform: scale(1.05);
        }

        /* Стиль текста названия достижения */
        .achievement-title {
            margin-top: 15px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.5px;
            color: #FFFFFF;
        }

        .locked .achievement-title {
            color: #52586D;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <?php include 'Components/Header.php'; ?>

    <div class="achievements-wrapper">
        <div class="achievements-grid">
            <?php for ($i = 0; $i < $display_count; $i++): ?>
                
                <?php if (isset($user_achievements[$i])): ?>
                    <?php 
                        $ach_name = $user_achievements[$i]['name'];
                        // Берем название из нашего списка или ставим дефолтное, если имя отличается
                        $title = $achievement_titles[$ach_name] ?? 'Новое достижение';
                    ?>
                    <!-- БЛОК ОТКРЫТОГО ДОСТИЖЕНИЯ -->
                    <div class="achievement-block unlocked">
                        <img src="Img/Achievements/<?php echo htmlspecialchars($ach_name); ?>.jpg" 
                             alt="Достижение открыто" 
                             class="achievement-icon">
                        <div class="achievement-title"><?php echo htmlspecialchars($title); ?></div>
                    </div>
                <?php else: ?>
                    <!-- БЛОК СКРЫТОГО ДОСТИЖЕНИЯ -->
                    <div class="achievement-block locked">
                        <div class="achievement-title">Скрыто</div>
                    </div>
                <?php endif; ?>

            <?php endfor; ?>
        </div>
    </div>

</body>
</html>