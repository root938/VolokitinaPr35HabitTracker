<?php
session_start();
require_once 'Config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT action_type, description, created_at FROM user_activity_logs WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getActionStyle($description) {
    $desc = mb_strtolower($description);
    if (str_contains($desc, 'создана')) return ['label' => 'СОЗДАНИЕ', 'color' => '#A7FF0A'];
    if (str_contains($desc, 'изменена')) return ['label' => 'ИЗМЕНЕНИЕ', 'color' => '#8000FF'];
    if (str_contains($desc, 'удалена')) return ['label' => 'УДАЛЕНИЕ', 'color' => '#E5484D'];
    return ['label' => 'ДЕЙСТВИЕ', 'color' => '#FF3F81'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>HabitTracker — Activity</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        
        body { 
            background-color: #1B1E28; 
            color: #ffffff; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh;
            /* Отступ 30px от конца всей страницы до блока */
            padding-bottom: 30px; 
        }

        header, .header-main { 
            width: 100% !important; 
            background-color: #FF3F81 !important; 
            display: flex !important; 
            justify-content: space-between !important; 
            align-items: center !important; 
            padding: 0 50px !important; 
            height: 80px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 10;
        }

        /* Основной блок 1200px. Растягивается автоматически */
        .history-container {
            width: 1200px;
            background-color: #2A2D3A;
            /* Минимальная высота, чтобы блок не был слишком коротким */
            min-height: 300px; 
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
            padding-bottom: 30px; /* Внутренний отступ снизу */
            border-bottom-left-radius: 30px;  
            border-bottom-right-radius: 30px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            /* Позволяет блоку расти вниз без ограничений */
            height: auto; 
        }

        /* Контейнер для карточек 1100px */
        .log-container { 
            display: flex; 
            flex-direction: column; 
            /* Расстояние между блоками истории 30px */
            gap: 20px; 
            width: 1100px; 
        }

        .log-card {
            background-color: #1B1E28;
            border-radius: 15px;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 8px solid #ccc;
            transition: 0.2s ease-in-out;
        }

        .log-card:hover { 
            background-color: #222631;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .log-main-info { display: flex; flex-direction: column; gap: 5px; }

        .log-meta {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .log-description {
            font-size: 20px;
            font-weight: 700;
        }

        .log-timestamp {
            text-align: right;
        }

        .date-text {
            font-size: 16px;
            font-weight: 900;
            color: #fff;
            display: block;
        }

        .time-text {
            font-size: 13px;
            color: #8E8E93;
        }

        .empty-state {
            margin-top: 40px;
            font-size: 20px;
            font-weight: 700;
            color: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>

    <?php include 'Components/Header.php'; ?>

    <div class="history-container">
        <div class="log-container">
            <?php if (empty($logs)): ?>
                <div class="empty-state">ИСТОРИЯ ПУСТА</div>
            <?php else: ?>
                <?php foreach ($logs as $log): 
                    $style = getActionStyle($log['description']); 
                ?>
                    <div class="log-card" style="border-left-color: <?= $style['color'] ?>;">
                        <div class="log-main-info">
                            <span class="log-meta" style="color: <?= $style['color'] ?>;">
                                <?= $style['label'] ?>
                            </span>
                            <span class="log-description"><?= htmlspecialchars($log['description']) ?></span>
                        </div>
                        <div class="log-timestamp">
                            <span class="date-text"><?= date('d.m.Y', strtotime($log['created_at'])) ?></span>
                            <span class="time-text"><?= date('H:i', strtotime($log['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>