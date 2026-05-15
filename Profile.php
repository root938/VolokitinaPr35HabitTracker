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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль пользователя</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;900&display=swap" rel="stylesheet">
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
        }

        .columns-container {
            display: flex;
            padding: 40px;
            gap: 30px;
            align-items: flex-start;
        }

        /* Фото теперь квадратное 210x210 */
        .photo-block {
            width: 210px; 
            height: 210px;
            background-color: #1B1E28;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0; /* Чтобы фото не сжималось */
        }

        .photo-block img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-photo {
            font-weight: 900;
            color: #333;
            font-size: 80px;
        }

        /* Информация теперь занимает всё оставшееся место */
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
            gap: 20px; /* Отступ между лейблом и полем */
        }

        .label {
            width: 120px; /* Фиксированная ширина для выравнивания */
            font-size: 30px;
            font-weight: 900;
            flex-shrink: 0;
        }

        .field {
            flex: 1; /* Поле растягивается на всю длину */
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
        }

        .badge-slot img {
            width: 180px;
            height: 180px;
        }
    </style>
</head>
<body>

    <?php include 'Components/Header.php'; ?>

    <div class="main-wrapper">
        <div class="columns-container">

            <!-- Квадратное фото -->
            <div class="photo-block">
                <?php if(file_exists("Img/Users/{$user_id}.jpg")): ?>
                    <img src="Img/Users/<?php echo $user_id; ?>.jpg" alt="Аватар">
                <?php else: ?>
                    <div class="no-photo">?</div>
                <?php endif; ?>
            </div>

            <!-- Растянутый блок информации -->
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
                ?>
                    <div class="badge-slot">
                        <img src="Img/Badges/achievement.png" alt="Значок">
                        <span style="font-size: 14px; margin-top: 10px; font-weight: 900;">
                            <?php echo htmlspecialchars($ach['name']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>

                <?php for ($i = $count; $i < 3; $i++): ?>
                    <div class="badge-slot"></div>
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