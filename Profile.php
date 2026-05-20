<?php
session_start();
require_once 'Config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Registration.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT name FROM achievements WHERE user_id = ? LIMIT 10");
$stmt->execute([$user_id]);
$achievements = $stmt->fetchAll();

// Массив сопоставления ID достижений с их красивыми названиями
$achievement_titles = [
    '3' => 'Первый день в Fix',
    '4' => 'Первая привычка',
    '5' => 'Первый идеальный день'
];

// Живой рандом: картинка выбирается случайно из диапазона от 1 до 20 при каждой загрузке страницы.
// Если в будущем добавишь новые аватарки (например, до 50), просто замени 20 на нужное число.
$random_avatar_num = rand(1, 20);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль пользователя</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #1B1E28;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
        }

        .main-wrapper {
            width: 1200px;
            background-color: #2A2D3A;
            margin: 0 auto;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 50px rgba(255, 0, 110, 0.4);
            padding-bottom: 40px;
            margin-bottom: 40px;
        }

        .columns-container {
            display: flex;
            padding: 40px;
            gap: 30px;
            align-items: flex-start;
        }

        .photo-block {
            width: 210px; 
            height: 210px;
            background-color: #1B1E28;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .photo-block img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .info-block {
            flex: 1; 
            height: 210px;
            background-color: #1B1E28;
            border-radius: 30px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .data-row {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .label {
            width: 120px;
            font-size: 30px;
            font-weight: 900;
            flex-shrink: 0;
        }

        .field {
            flex: 1;
            height: 60px;
            background-color: #2A2D3A;
            border-radius: 30px;
            display: flex;
            align-items: center;
            padding: 0 25px;
            font-size: 24px;
            color: White;
            overflow: hidden;
        }

        /* Достижения */
        .achievements-section {
            width: 1120px;
            height: 410px;
            background-color: #1B1E28;
            border-radius: 30px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        .achievements-title {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 30px;
        }

        .scroll-container {
            display: flex;
            gap: 80px;
            overflow-x: auto;
            padding-bottom: 15px;
        }

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background: #FF006E;
            border-radius: 10px;
        }

        .achievement-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .badge-slot {
            min-width: 260px;
            height: 260px;
            background-color: #2A2D3A;
            border-radius: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            padding: 15px;
        }

        .achievement-link:hover .badge-slot {
            transform: translateY(-5px);
            border-color: #A7FF0A;
            box-shadow: 0 5px 20px rgba(167, 255, 10, 0.15);
            cursor: pointer;
        }

        .badge-slot img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .achievement-link:hover .badge-slot img {
            transform: scale(1.05);
        }

        .badge-slot.empty {
            background-color: #20222B;
            border: 2px dashed #2A2D3A;
        }
    </style>
</head>
<body>

    <?php include 'Components/Header.php'; ?>

    <div class="main-wrapper">
        <div class="columns-container">

            <!-- Квадратное фото пользователя или рандомная заглушка -->
            <div class="photo-block">
                <?php if(file_exists("Img/Users/{$user_id}.jpg")): ?>
                    <!-- Если загружено личное фото -->
                    <img src="Img/Users/<?php echo $user_id; ?>.jpg" alt="Аватар">
                <?php else: ?>
                    <!-- Если личного фото нет, подтягиваем динамический случайный аватар -->
                    <img src="Img/Avatars/<?php echo $random_avatar_num; ?>.png" alt="Стандартный аватар">
                <?php endif; ?>
            </div>

            <!-- Блок информации -->
            <div class="info-block">
                <div class="data-row">
                    <div class="label">Имя</div>
                    <div class="field">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                </div>

                <div class="data-row">
                    <div class="label">Email</div>
                    <div class="field">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="achievements-section">
            <div class="achievements-title">Достижения</div>
            <div class="scroll-container">
                <?php
                $count = 0;
                foreach ($achievements as $ach):
                    $count++;
                    $ach_name = $ach['name'];
                    $title = $achievement_titles[$ach_name] ?? 'Достижение ' . $ach_name;
                ?>
                    <a href="achievements.php" class="achievement-link">
                        <div class="badge-slot">
                            <img src="Img/Achievements/<?php echo htmlspecialchars($ach_name); ?>.jpg" alt="Значок">
                            <span style="font-size: 14px; margin-top: 12px; font-weight: 700; line-height: 1.2;">
                                <?php echo htmlspecialchars($title); ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php for ($i = $count; $i < 3; $i++): ?>
                    <div class="badge-slot empty"></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <script>
        const scrollContainer = document.querySelector('.scroll-container');
        scrollContainer.addEventListener('wheel', (evt) => {
            evt.preventDefault();
            scrollContainer.scrollLeft += evt.deltaY;
        });
    </script>
</body>
</html>