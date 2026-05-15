<?php
session_start();
require_once 'Config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- ЛОГИКА КАЛЕНДАРЯ ---
$view_month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$view_year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// --- ЛОГИКА TOGGLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['habit_id'])) {
    if ($selected_date === $today) {
        $hid = (int)$_POST['habit_id'];
        $checkStmt = $pdo->prepare("SELECT id FROM habit_logs WHERE habit_id = ? AND date = ?");
        $checkStmt->execute([$hid, $today]);
        
        if ($checkStmt->fetch()) {
            $pdo->prepare("DELETE FROM habit_logs WHERE habit_id = ? AND date = ?")->execute([$hid, $today]);
        } else {
            $pdo->prepare("INSERT INTO habit_logs (habit_id, date) VALUES (?, ?)")->execute([$hid, $today]);
        }
        header("Location: Calendar.php?date=$selected_date&m=$view_month&y=$view_year");
        exit();
    }
}

// Навигация
$prev_m = $view_month - 1; $prev_y = $view_year;
if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
$next_m = $view_month + 1; $next_y = $view_year;
if ($next_m > 12) { $next_m = 1; $next_y++; }

$first_day_idx = date('N', strtotime("$view_year-$view_month-01"));
$days_count = cal_days_in_month(CAL_GREGORIAN, $view_month, $view_year);
$months_map = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];

// Данные для правой панели
$stmt = $pdo->prepare("
    SELECT h.*, 
    (SELECT id FROM habit_logs WHERE habit_id = h.id AND date = ?) as completed
    FROM habits h 
    WHERE h.user_id = ? AND DATE(h.created_at) <= ? AND h.is_active = 1
");
$stmt->execute([$selected_date, $user_id, $selected_date]);
$habits = $stmt->fetchAll();

$totalCount = count($habits);
$doneCount = 0;
foreach ($habits as $h) { if ($h['completed']) $doneCount++; }
$progressPercent = $totalCount > 0 ? ($doneCount / $totalCount) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Календарь привычек</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <script>
        // Сброс на "сегодня" при обычном обновлении (F5)
        if (performance.navigation.type === 1 && window.location.search.length > 0) {
            window.location.href = "Calendar.php";
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background-color: #1B1E28; color: #ffffff; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }

        header, .header-main { 
            width: 100% !important; background-color: #AB6CD9 !important; 
            display: flex !important; justify-content: space-between !important; 
            align-items: center !important; padding: 0 50px !important; height: 80px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 100;
        }

        .layout { display: grid; grid-template-columns: 650px 1fr; gap: 40px; width: 1300px; margin-top: 40px; align-items: start; padding-bottom: 50px; }

        /* ЛЕВАЯ ЧАСТЬ */
        .calendar-box { background-color: #2A2D3A; border-radius: 30px; padding: 40px; box-shadow: 0 10px 50px rgba(0,0,0,0.3); }
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .month-name { color: #A7FF0A; font-weight: 900; font-size: 32px; }
        .nav-btn { color: #AB6CD9; text-decoration: none; font-size: 40px; font-weight: 900; }

        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; }
        .weekday { text-align: center; font-weight: 700; color: #555; font-size: 14px; margin-bottom: 10px; }
        
        .day-cell { 
            background: #1B1E28; aspect-ratio: 1/1.1; border-radius: 15px; 
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-decoration: none; color: #fff; border: 2px solid transparent; transition: 0.2s;
            position: relative;
        }

        /* ТЕКУЩАЯ ДАТА */
        .day-cell.today-marker .day-num { color: #A7FF0A; font-weight: 900; }
        .day-cell.today-marker::after {
            content: ''; position: absolute; bottom: 8px; width: 6px; height: 6px;
            background-color: #A7FF0A; border-radius: 50%;
        }

        /* ВЫБРАННАЯ ДАТА */
        .day-cell.selected-day { border-color: #AB6CD9 !important; background-color: #3B3E4A; }
        
        .day-cell:hover { border-color: #555; }
        
        .day-num { font-size: 22px; font-weight: 700; }
        .day-res { font-size: 11px; font-weight: 700; margin-top: 5px; }
        .res-full { color: #A7FF0A; }
        .res-part { color: #AB6CD9; }

        /* ПРАВАЯ ПАНЕЛЬ (Без свечения) */
        .main-display { 
            background-color: #2A2D3A; 
            border-radius: 30px; 
            padding: 40px; 
            /* Эффект свечения убран */
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }

        .progress-bar-container {
            width: 100%; height: 60px; background-color: #1B1E28; border-radius: 35px;
            border: 2px solid #9B4DCC; display: flex; align-items: center; padding: 0 5px; margin-bottom: 40px;
        }
        .progress-fill {
            height: 50px; background-color: #A7FF0A; border-radius: 30px; transition: width 0.6s ease;
            width: <?php echo ($progressPercent > 0) ? "calc($progressPercent% - 10px)" : "50px"; ?>; min-width: 50px;
        }
        .habit-list { display: flex; flex-direction: column; gap: 20px; width: 100%; }
        .habit-card {
            width: 100%; height: 110px; background-color: #1B1E28; border-radius: 20px;
            display: flex; flex-direction: row-reverse; justify-content: flex-end; align-items: center; padding: 0 30px; gap: 25px;
        }
        .habit-title { font-size: 26px; font-weight: 900; color: #ffffff; text-transform: uppercase; }
        .check-btn {
            width: 70px; height: 70px; background-color: #2A2D3A; border-radius: 18px;
            display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; flex-shrink: 0;
        }
    </style>
</head>
<body>

    <?php include 'Components/Header.php'; ?>

    <div class="layout">
        <div class="calendar-box">
            <div class="cal-header">
                <a href="?m=<?= $prev_m ?>&y=<?= $prev_y ?>" class="nav-btn">‹</a>
                <div class="month-name"><?= $months_map[$view_month] ?> <?= $view_year ?></div>
                <a href="?m=<?= $next_m ?>&y=<?= $next_y ?>" class="nav-btn">›</a>
            </div>

            <div class="cal-grid">
                <?php 
                $wdays = ['ПН','ВТ','СР','ЧТ','ПТ','СБ','ВС'];
                foreach($wdays as $w) echo "<div class='weekday'>$w</div>";

                for ($i = 1; $i < $first_day_idx; $i++) echo "<div></div>";

                for ($d = 1; $d <= $days_count; $d++):
                    $cur_loop_date = sprintf('%s-%02d-%02d', $view_year, $view_month, $d);
                    
                    $st = $pdo->prepare("SELECT h.id, (SELECT 1 FROM habit_logs WHERE habit_id = h.id AND date = ?) as done 
                                         FROM habits h WHERE h.user_id = ? AND DATE(h.created_at) <= ? AND h.is_active = 1");
                    $st->execute([$cur_loop_date, $user_id, $cur_loop_date]);
                    $cell_habits = $st->fetchAll();
                    
                    $c_total = count($cell_habits);
                    $c_done = 0;
                    foreach($cell_habits as $ch) if($ch['done']) $c_done++;
                    
                    $is_today = ($cur_loop_date == $today) ? 'today-marker' : '';
                    $is_selected = ($cur_loop_date == $selected_date) ? 'selected-day' : '';
                ?>
                    <a href="?date=<?= $cur_loop_date ?>&m=<?= $view_month ?>&y=<?= $view_year ?>" 
                       class="day-cell <?= $is_today ?> <?= $is_selected ?>">
                        <span class="day-num"><?= $d ?></span>
                        <?php if($c_total > 0): ?>
                            <span class="day-res <?= ($c_done == $c_total) ? 'res-full' : 'res-part' ?>">
                                <?= $c_done ?>/<?= $c_total ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>

        <div class="main-display">
            <div class="progress-bar-container">
                <div class="progress-fill"></div>
            </div>

            <div class="habit-list">
                <?php if (empty($habits)): ?>
                    <div style="color: #444; font-size: 24px; font-weight: 900; margin-top: 40px;">СПИСОК ПУСТ</div>
                <?php else: ?>
                    <?php foreach ($habits as $habit): ?>
                        <div class="habit-card">
                            <div class="habit-title"><?= htmlspecialchars($habit['title']); ?></div>
                            
                            <?php if ($selected_date === $today): ?>
                                <form method="POST">
                                    <input type="hidden" name="habit_id" value="<?= $habit['id'] ?>">
                                    <button type="submit" class="check-btn">
                                        <?php if ($habit['completed']): ?>
                                            <img src="Img/UI/Mark.png" alt="OK">
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="check-btn">
                                    <?php if ($habit['completed']): ?>
                                        <img src="Img/UI/Mark.png" alt="OK">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>