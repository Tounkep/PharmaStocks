
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

if (
    !isset($_SESSION['utilisateur_id']) ||
    strtoupper($_SESSION['role'] ?? '') !== 'ADMIN'
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Accès réservé aux administrateurs.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controllers/RapportController.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $controller = new RapportController($db);
    $resultat = $controller->index();

    echo json_encode([
        'success' => true,
        'data' => $resultat
    ], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la génération du rapport.'
    ], JSON_UNESCAPED_UNICODE);
}
