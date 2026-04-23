<?php
session_start(); // Запускаем сессию, чтобы получить ID пользователя
require_once __DIR__ . '/../Config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ВРЕМЕННО: Если сессии еще нет, поставим ID 1 (убедись, что в таблице users есть юзер с ID 1)
    // Когда сделаешь авторизацию, здесь будет: $user_id = $_SESSION['user_id'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

    try {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $frequency = $_POST['frequency'];
        
        $h1 = $_POST['h1']; $h2 = $_POST['h2'];
        $m1 = $_POST['m1']; $m2 = $_POST['m2'];
        $reminder_time = $h1 . $h2 . ':' . $m1 . $m2;

        // Добавляем user_id в запрос
        $sql = "INSERT INTO habits (user_id, title, description, frequency, reminder_time) 
                VALUES (:user_id, :title, :description, :frequency, :reminder_time)";
        
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':user_id'       => $user_id, // Передаем ID владельца
            ':title'         => $title,
            ':description'   => $description,
            ':frequency'     => $frequency,
            ':reminder_time' => $reminder_time
        ]);

        header("Location: ../Index.php");
        exit();

    } catch (PDOException $e) {
        die("Ошибка сохранения в базу данных: " . $e->getMessage());
    }
}