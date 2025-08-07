<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';
require_once '../config/headers.php'; 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Auth-Token');
set_ajax_cache_headers(true, 300); 
function log_message($message) {
    $log_dir = dirname(__FILE__) . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    error_log("[" . date("Y-m-d H:i:s") . "] get-thresholds.php: " . $message . "\n", 3, $log_dir . "/api_activity.log");
}
try {
    $headers = getallheaders();
    $token = isset($headers['X-Auth-Token']) ? $headers['X-Auth-Token'] : '';
    log_message("Получен токен: " . $token);
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Отсутствует токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    log_message("Найден пользователь: " . ($user ? "ID: " . $user['id'] : "Пользователь не найден"));
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Неверный токен авторизации']);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'alarm_thresholds'
    ");
    $stmt->execute();
    $thresholdsTableExists = (bool) $stmt->fetchColumn();
    if (!$thresholdsTableExists) {
        log_message("Таблица alarm_thresholds не существует, создаем...");
        $pdo->exec("
            CREATE TABLE `alarm_thresholds` (
              `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `parameter_type` ENUM('temperature', 'humidity_air', 'humidity_soil', 'co2') NOT NULL,
              `min_limit` DECIMAL(8,2) NOT NULL,
              `max_limit` DECIMAL(8,2) NOT NULL,
              `target_value` DECIMAL(8,2) DEFAULT NULL COMMENT 'Целевое значение (если необходимо)',
              `tolerance` DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Допустимое отклонение',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `user_parameter_unique` (`user_id`, `parameter_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $defaultThresholds = [
            ['temperature', 15.0, 30.0, 25.0, 1.0],
            ['humidity_air', 40.0, 70.0, 60.0, 5.0],
            ['humidity_soil', 30.0, 70.0, 50.0, 5.0],
            ['co2', 600.0, 1500.0, 1000.0, 100.0]
        ];
        $stmt = $pdo->prepare("
            INSERT INTO alarm_thresholds 
            (user_id, parameter_type, min_limit, max_limit, target_value, tolerance) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($defaultThresholds as $threshold) {
            $stmt->execute([$user['id'], $threshold[0], $threshold[1], $threshold[2], $threshold[3], $threshold[4]]);
        }
        log_message("Таблица alarm_thresholds создана и заполнена данными по умолчанию");
    }
    $stmt = $pdo->prepare("
        SELECT parameter_type, min_limit, max_limit, target_value, tolerance 
        FROM alarm_thresholds 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $thresholds = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $thresholds[$row['parameter_type']] = [
            'min_limit' => (float)$row['min_limit'],
            'max_limit' => (float)$row['max_limit'],
            'target_value' => (float)$row['target_value'],
            'tolerance' => (float)$row['tolerance']
        ];
    }
    if (empty($thresholds)) {
        log_message("Пороговые значения не найдены для пользователя ID: " . $user['id'] . ", создаем по умолчанию");
        $defaultThresholds = [
            ['temperature', 15.0, 30.0, 25.0, 1.0],
            ['humidity_air', 40.0, 70.0, 60.0, 5.0],
            ['humidity_soil', 30.0, 70.0, 50.0, 5.0],
            ['co2', 600.0, 1500.0, 1000.0, 100.0]
        ];
        $stmt = $pdo->prepare("
            INSERT INTO alarm_thresholds 
            (user_id, parameter_type, min_limit, max_limit, target_value, tolerance) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($defaultThresholds as $threshold) {
            $stmt->execute([$user['id'], $threshold[0], $threshold[1], $threshold[2], $threshold[3], $threshold[4]]);
            $thresholds[$threshold[0]] = [
                'min_limit' => (float)$threshold[1],
                'max_limit' => (float)$threshold[2],
                'target_value' => (float)$threshold[3],
                'tolerance' => (float)$threshold[4]
            ];
        }
    }
    log_message("Отправлен ответ с пороговыми значениями для пользователя ID: " . $user['id']);
    echo json_encode($thresholds);
} catch (PDOException $e) {
    log_message("PDO ошибка: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    log_message("Общая ошибка: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}
?> 