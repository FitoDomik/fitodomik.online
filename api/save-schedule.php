<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Некорректный формат данных']);
        exit;
    }
    if (!isset($data['scheduleData']) || !isset($data['type'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые параметры']);
        exit;
    }
    $type = $data['type'];
    if ($type !== 'curtains' && $type !== 'lighting') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Неверный тип расписания']);
        exit;
    }
    $scheduleData = $data['scheduleData'];
    try {
        $pdo->beginTransaction();
        if ($type === 'curtains') {
            $stmt = $pdo->prepare("
                UPDATE schedule 
                SET curtains_schedule = :active 
                WHERE user_id = :user_id AND time = :time
            ");
        } else { 
            $stmt = $pdo->prepare("
                UPDATE schedule 
                SET lighting_schedule = :active 
                WHERE user_id = :user_id AND time = :time
            ");
        }
        foreach ($scheduleData as $item) {
            $stmt->bindParam(':active', $item['active'], PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':time', $item['time'], PDO::PARAM_STR);
            $stmt->execute();
        }
        $pdo->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Расписание успешно обновлено']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении расписания: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
?> 