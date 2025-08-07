<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ запрещен - ФитоДомик</title>
    <meta http-equiv="refresh" content="10;url=index.php">
    <style>
        body {
            background-color: #ff0000;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .error-container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
        }
        h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            margin-bottom: 30px;
        }
        .countdown {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background-color: #000;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #333;
        }
        @media (max-width: 480px) {
            h1 {
                font-size: 28px;
            }
            p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Доступ запрещен</h1>
        <p>У вас недостаточно прав для доступа к этой странице.</p>
        <div class="countdown">Перенаправление через <span id="timer">10</span> секунд</div>
        <a href="index.php" class="btn">Вернуться на главную страницу</a>
    </div>

    <script>
        // Скрипт обратного отсчета
        let timeLeft = 10;
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(function() {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
            }
        }, 1000);
    </script>
</body>
</html>