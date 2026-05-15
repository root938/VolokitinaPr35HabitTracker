<?php
session_start();
require_once 'Config/db.php';
$headerColor = '#AB6CD9'; 
if (!isset($_SESSION['user_id'])) {
    header("Location: Registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- ЛОГИКА НАВИГАЦИИ И РАСЧЕТОВ (Остается неизменной) ---
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }
if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; }

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// --- 1. БАЛЛЫ (Сумма всех выполненных привычек за всё время) ---
$stmtPoints = $pdo->prepare("
    SELECT COUNT(l.id) 
    FROM habit_logs l 
    JOIN habits h ON l.habit_id = h.id 
    WHERE h.user_id = ?
");
$stmtPoints->execute([$user_id]);
$total_points = $stmtPoints->fetchColumn() ?: 0;

// 2. Сегодня
$stT_Today = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = ? AND is_active = 1 AND DATE(created_at) <= ?");
$stT_Today->execute([$user_id, $today]);
$total_h_today = $stT_Today->fetchColumn() ?: 0;

$stD_Today = $pdo->prepare("SELECT COUNT(*) FROM habit_logs l JOIN habits h ON l.habit_id = h.id WHERE h.user_id = ? AND l.date = ?");
$stD_Today->execute([$user_id, $today]);
$done_h_today = $stD_Today->fetchColumn() ?: 0;

// --- 3. % ЗА МЕСЯЦ (Точный расчет без округления) ---

// 1. Считаем ПЛАН: сколько активных привычек сейчас
$stH = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = ? AND is_active = 1");
$stH->execute([$user_id]);
$activeHabitsCount = $stH->fetchColumn() ?: 0;

// Итоговый план на весь месяц (например, 3 * 31 = 93)
$totalPossibleMonth = $activeHabitsCount * $daysInMonth;

// 2. Считаем ФАКТ: сколько реально выполнено в этом месяце
$stF = $pdo->prepare("
    SELECT COUNT(*) FROM habit_logs l 
    JOIN habits h ON l.habit_id = h.id 
    WHERE h.user_id = ? 
    AND MONTH(l.date) = ? 
    AND YEAR(l.date) = ?
");
$stF->execute([$user_id, $currentMonth, $currentYear]);
$totalDoneInMonth = $stF->fetchColumn() ?: 0;

// 3. РАСЧЕТ (БЕЗ ОКРУГЛЕНИЯ)
// Форматируем до 2 знаков после запятой
if ($totalPossibleMonth > 0) {
    $monthPercent = number_format(($totalDoneInMonth / $totalPossibleMonth) * 100, 2);
} else {
    $monthPercent = "0.00";
}

// 4. Streak
$stmtDates = $pdo->prepare("SELECT DISTINCT date FROM habit_logs l JOIN habits h ON l.habit_id = h.id WHERE h.user_id = ? ORDER BY date DESC");
$stmtDates->execute([$user_id]);
$logDates = $stmtDates->fetchAll(PDO::FETCH_COLUMN);
$streak = 0;
if (count($logDates) > 0) {
    $checkDate = new DateTime($today);
    if (!in_array($today, $logDates)) { $checkDate->modify('-1 day'); }
    foreach ($logDates as $dateStr) {
        if ($dateStr === $checkDate->format('Y-m-d')) { $streak++; $checkDate->modify('-1 day'); } else { break; }
    }
}
$img_idx = ($streak >= 5) ? 5 : ($streak > 0 ? $streak : 1);

// 5. График
$graphLabels = []; $graphValues = [];
for ($i = 1; $i <= $daysInMonth; $i++) {
    $graphLabels[] = $i;
    $dateLoop = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $i);
    if ($dateLoop > $today) { $graphValues[] = null; } 
    else {
        $stD = $pdo->prepare("SELECT COUNT(*) FROM habit_logs l JOIN habits h ON l.habit_id = h.id WHERE h.user_id = ? AND l.date = ?");
        $stD->execute([$user_id, $dateLoop]);
        $graphValues[] = $stD->fetchColumn() ?: 0;
    }
}

//6.фото streak
// --- ОБНОВЛЕННАЯ ЛОГИКА ФОТО STREAK ---
if ($streak <= 1) {
    $img_idx = 1; 
} elseif ($streak == 2) {
    $img_idx = 2; 
} elseif ($streak >= 3 && $streak <= 6) {
    $img_idx = 3; // Теперь при 3, 4, 5, 6 будет фото 3.png
} elseif ($streak >= 7 && $streak <= 14) {
    $img_idx = 4; 
} else {
    $img_idx = 5; 
}

$months = ["", "ЯНВАРЬ", "ФЕВРАЛЬ", "МАРТ", "АПРЕЛЬ", "МАЙ", "ИЮНЬ", "ИЮЛЬ", "АВГУСТ", "СЕНТЯБРЬ", "ОКТЯБРЬ", "НОЯБРЬ", "ДЕКАБРЬ"];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика - Новая сетка</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg: #13151A; --card: #2A2D3A; --neon: #A7FF0A; --purple: #9B4DCC; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background: var(--bg); color: #fff; }
        .main-container { width: 1200px; margin: 0 auto; padding: 40px 0; }
        
        .stats-layout { display: flex; gap: 30px; align-items: flex-start; }

        /* ЛЕВАЯ ЧАСТЬ: Навигация, График, Кнопки */
        .left-col { flex: 1; display: flex; flex-direction: column; gap: 20px; }
        .month-nav { background: var(--card); border-radius: 20px; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .month-nav h2 { font-weight: 900; text-transform: uppercase; font-size: 20px; }
        .arrow { background: none; border: none; color: var(--purple); font-size: 30px; cursor: pointer; font-weight: 900; }
        
        .chart-box { background: var(--card); border-radius: 30px; padding: 30px; height: 450px; }
        
        .export-row {
    display: flex;
    gap: 15px;          /* Отступ между кнопками */
    margin-top: 2px;
    justify-content: center; /* Центрируем кнопки */
}
.btn-export {
    padding: 20px 100px;
    border-radius: 12px;
    border: none;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none; /* Убираем подчеркивание у ссылки */
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}        
        .btn-pdf {
    background: #9B4DCC;
    color: white;
}
        /* Кнопка CSV (твоя стандартная или зеленая) */
.btn-export:not(.btn-pdf) {
    background: #A7FF0A; /* Твой неоновый */
    color: #000;
}
        .btn-export:hover {
    opacity: 0.8;
    transform: translateY(-2px); /* Легкий эффект при наведении */
}
        /* ПРАВАЯ ЧАСТЬ: 4 блока, Фото, Сводка */
        .right-col { width: 420px; display: flex; flex-direction: column; gap: 20px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .stat-rect { height: 100px; background: var(--card); border-radius: 25px; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .stat-rect h3 { font-size: 11px; color: #8E8E93; text-transform: uppercase; margin-bottom: 4px; font-weight: 900; }
        .stat-rect .value { font-size: 24px; font-weight: 900; color: var(--neon); }

        .streak-card {
    height: 260px;          /* Фиксированная высота блока */
    background: var(--card);
    border-radius: 30px;
    overflow: hidden;       /* Чтобы картинка не вылезала за скругленные углы */
    display: flex;          /* Центрируем картинку, если она меньше блока */
    justify-content: center;
    align-items: center;
}

.streak-card img {
    width: 200px;            /* Растягиваем по ширине */
    height: 200px;           /* Растягиваем по высоте */
    object-fit: contain;
}

        .week-strip { background: var(--card); border-radius: 30px; padding: 30px 15px; display: flex; justify-content: space-between; }
        .day-box { text-align: center; flex: 1; }
        .day-name { color: #8E8E93; font-weight: 900; font-size: 13px; margin-bottom: 10px; display: block; }
        .day-val { font-weight: 900; font-size: 18px; display: block; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>

<?php include 'Components/Header.php'; ?>

<div class="main-container">
    <div class="stats-layout">
        
        <!-- ЛЕВАЯ КОЛОНКА -->
        <div class="left-col">
            <div class="month-nav">
                <button class="arrow" onclick="navM(-1)">❮</button>
                <h2><?= $months[$currentMonth] ?> <?= $currentYear ?></h2>
                <button class="arrow" onclick="navM(1)">❯</button>
            </div>

            <div class="chart-box">
                <canvas id="mainChart"></canvas>
            </div>

            <div class="export-row">
                <button class="btn-export btn-pdf" onclick="downloadChartPDF()">Экспорт PDF</button>
                <a href="export.php?format=csv&month=<?= $currentMonth ?>&year=<?= $currentYear ?>" class="btn-export">Экспорт CSV</a>
            </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА -->
        <div class="right-col">
            <div class="stats-grid">
                <div class="stat-rect">
                    <h3>Сегодня</h3>
                    <span class="value" style="color:<?= ($done_h_today >= $total_h_today && $total_h_today > 0) ? '#A7FF0A' : '#9B4DCC' ?>"><?= $done_h_today ?>/<?= $total_h_today ?></span>
                </div>
                <div class="stat-rect">
                    <h3>Баллы</h3>
                    <span class="value"><?= $total_points ?></span>
                </div>
                <div class="stat-rect">
                    <h3>Месяц</h3>
                    <span class="value"><?= $monthPercent ?>%</span>
                </div>
                <div class="stat-rect">
                    <h3>Streak</h3>
                    <span class="value"><?= $streak ?></span>
                </div>
            </div>

            <div class="streak-card">
                <img src="Img/Streaks/<?= $img_idx ?>.png" alt="Streak Image">
            </div>

            <div class="week-strip">
                <?php 
                $shortDays = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
                for($i=0; $i<7; $i++): 
                    $dateW = date('Y-m-d', strtotime("Monday this week +$i days"));
                    $stT = $pdo->prepare("SELECT COUNT(*) FROM habits WHERE user_id = ? AND is_active = 1 AND DATE(created_at) <= ?");
                    $stT->execute([$user_id, $dateW]);
                    $tw = $stT->fetchColumn() ?: 0;

                    $stD = $pdo->prepare("SELECT COUNT(*) FROM habit_logs l JOIN habits h ON l.habit_id = h.id WHERE h.user_id = ? AND l.date = ?");
                    $stD->execute([$user_id, $dateW]);
                    $dw = $stD->fetchColumn() ?: 0;
                ?>
                <div class="day-box">
                    <span class="day-name"><?= $shortDays[$i] ?></span>
                    <span class="day-val" style="color: <?= ($dateW > $today) ? '#444' : (($dw >= $tw && $tw > 0) ? '#A7FF0A' : '#9B4DCC') ?>">
                        <?= $dw ?>/<?= $tw ?>
                    </span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>
</div>

<script>
    function navM(s) {
        let m = <?= $currentMonth ?> + s;
        let y = <?= $currentYear ?>;
        if(m > 12) { m = 1; y++; }
        if(m < 1) { m = 12; y--; }
        window.location.href = `?month=${m}&year=${y}`;
    }

    const ctx = document.getElementById('mainChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($graphLabels) ?>,
            datasets: [{
                data: <?= json_encode($graphValues) ?>,
                borderColor: '#A7FF0A', borderWidth: 5, tension: 0.4, fill: true,
                backgroundColor: 'rgba(167, 255, 10, 0.05)', pointRadius: 4, pointBackgroundColor: '#9B4DCC'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Чтобы график тянулся красиво
    scales: {
    x: {
        grid: { 
            color: 'rgba(255, 255, 255, 0.1)' // Делаем сетку слегка видимой
        }, 
        ticks: {
            autoSkip: false,
            maxRotation: 0,         // Запрещаем поворачивать текст (чтобы было ровно)
            minRotation: 0,
            color: '#ffffff',       // Чистый белый цвет для дней недели/чисел
            padding: 10,            // Отодвигаем дни от самого графика, чтобы их было видно
            font: {
                size: 11,           // Делаем шрифт крупнее
                weight: 'bold',     // Делаем текст жирным
                family: 'sans-serif'
            }
        }
    },
    y: {
        grid: { 
            color: 'rgba(255, 255, 255, 0.1)' 
        },
        beginAtZero: true,          // График всегда начинается с 0
        ticks: {
            color: '#ffffff',       // Чистый белый цвет для количества привычек
            stepSize: 1,            // КЛЮЧЕВОЕ: Шаг строго равен 1 (никаких 1.5 или 2.4)
            precision: 0,           // КЛЮЧЕВОЕ: Запрещаем знаки после запятой
            font: {
                size: 15,           // Крупный шрифт для оси привычек
                weight: 'bold',
                family: 'sans-serif'
            }
        }
    }
}
}
    });
    function downloadChartPDF() {
    const canvas = document.getElementById('mainChart');
    if (!canvas) {
        alert('График не найден!');
        return;
    }

    const chartImage = canvas.toDataURL('image/png', 1.0);
    const printArea = document.createElement('div');
    
    // Задаем четкие размеры и внутренние отступы для всего листа
    printArea.style.cssText = `
        padding: 40px; 
        background-color: #1a1a1a; 
        box-sizing: border-box;
    `;

    const monthTitle = document.querySelector('.month-nav h2')?.innerText || 'Отчет';

    // Добавляем margin-bottom к заголовку, чтобы он не слипался с графиком
    printArea.innerHTML = `
        <div style="text-align: center; background-color: #1a1a1a;">
            <h1 style="color: #A7FF0A; font-family: sans-serif; font-size: 28px; margin-top: 0; margin-bottom: 40px; padding-bottom: 10px;">
                Статистика за ${monthTitle}
            </h1>
            <div style="width: 100%; display: block;">
                <img src="${chartImage}" style="width: 100%; height: auto; display: block;">
            </div>
        </div>
    `;

    const opt = {
        margin: [10, 15, 10, 15], // [верх, правый, низ, левый] отступы самого листа PDF
        filename: `Habit_Report_${monthTitle}.pdf`,
        image: { type: 'jpeg', quality: 1.0 },
        html2canvas: { 
            scale: 2, 
            letterRendering: true, 
            useCORS: true,
            backgroundColor: '#1a1a1a' 
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf().set(opt).from(printArea).save();
}
</script>
</body>
</html>