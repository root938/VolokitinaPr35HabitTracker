<?php
session_start();
// Подключение к БД (создай этот файл в папке Config)
require_once '../Config/Db.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($action === 'register') {
        $username = trim($_POST['username']);
        
        // Проверяем, есть ли уже такой пользователь
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Этот Email уже занят']);
            exit;
        }

        // Хешируем пароль для безопасности
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$username, $email, $hash])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            echo json_encode(['success' => true]);
        }
    } 
    
    elseif ($action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверный Email или пароль']);
        }
    }
}