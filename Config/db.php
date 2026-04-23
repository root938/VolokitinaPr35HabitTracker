<?php

// Параметры подключения (на основе твоего дампа)
$host = '127.0.0.1';        // Хост (обычно localhost)
$db   = 'habittracker';     // Имя базы данных из твоего SQL
$user = 'root';            // Твое имя пользователя в phpMyAdmin (по умолчанию root)
$pass = '';                // Твой пароль (в OpenServer/XAMPP по умолчанию пустой)
$charset = 'utf8mb4';      // Кодировка для корректного отображения кириллицы

// Настройка строки DSN (Data Source Name) для подключения к MySQL через PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Опции для безопасного и удобного подключения
$options = [
    // Режим вывода ошибок: исключения (Exception). Поможет сразу увидеть ошибку в SQL.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Формат получения данных: Ассоциативный массив. 
    // Вместо $row[0] будешь писать $row['username'].
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Отключаем эмуляцию подготовленных запросов для реальной защиты от взлома.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Создаем объект подключения
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Если подключение не удалось, скрипт остановится и выведет ошибку
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
