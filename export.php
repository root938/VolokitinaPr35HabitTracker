<?php
session_start();
require_once 'Config/db.php'; 

if (!isset($_SESSION['user_id'])) {
    die("Ошибка: Авторизуйтесь.");
}

$user_id = $_SESSION['user_id'];
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$format = $_GET['format'] ?? 'csv';

try {
    // Получаем только выполненные привычки за указанный месяц
    $stmt = $pdo->prepare("
        SELECT DATE(l.date) as log_date, h.title 
        FROM habit_logs l 
        JOIN habits h ON l.habit_id = h.id 
        WHERE h.user_id = ? AND MONTH(l.date) = ? AND YEAR(l.date) = ?
        ORDER BY l.date DESC
    ");
    $stmt->execute([$user_id, $month, $year]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($format === 'csv') {
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Habit_Report_' . sprintf('%02d', $month) . '_' . $year . '.csv"');

        $output = fopen('php://output', 'w');
        
        // BOM для корректного отображения кириллицы в Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Простая шапка
        fputcsv($output, ['ОТЧЕТ ПО ВЫПОЛНЕННЫМ ПРИВЫЧКАМ']);
        fputcsv($output, ['Период:', sprintf('%02d', $month) . '.' . $year]);
        fputcsv($output, ['Всего выполнено за месяц (раз):', count($data)]);
        fputcsv($output, []); // Пустая строка для воздуха

        // Заголовки колонок
        fputcsv($output, ['ДАТА', 'ДЕНЬ НЕДЕЛИ', 'НАЗВАНИЕ ПРИВЫЧКИ', 'СТАТУС']);

        if (!empty($data)) {
            $days_ru = [
                'Sunday' => 'Воскресенье', 'Monday' => 'Понедельник', 
                'Tuesday' => 'Вторник', 'Wednesday' => 'Среда', 
                'Thursday' => 'Четверг', 'Friday' => 'Пятница', 'Saturday' => 'Суббота'
            ];

            foreach ($data as $row) {
                $ts = strtotime($row['log_date']);
                fputcsv($output, [
                    date('d.m.Y', $ts),
                    $days_ru[date('l', $ts)],
                    $row['title'],
                    'Выполнено'
                ]);
            }
        } else {
            fputcsv($output, ['За этот месяц выполненных привычек не найдено.']);
        }

        fclose($output);
        exit();
    }

    if ($format === 'pdf') {
        die("Для генерации PDF используйте кнопку на главной странице (обработка через JS).");
    }

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}