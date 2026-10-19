<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode HTTP non autorisée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../controllers/EmployeeController.php';

try {
    $database = new Database();
    $model = new Employee($database->getConnection());
    $controller = new EmployeeController($model);
    $controller->index();
} catch (Throwable $e) {
    error_log('Route employee: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur lors du chargement du dashboard.'], JSON_UNESCAPED_UNICODE);
}
