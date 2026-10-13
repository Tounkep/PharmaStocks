<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Stock.php';
require_once __DIR__ . '/../models/Alerte.php';
require_once __DIR__ . '/../models/DashboardPharmacien.php';
require_once __DIR__ . '/../controllers/DashboardPharmacienController.php';


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Méthode HTTP non autorisée.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$utilisateur = Auth::requireRole([Auth::PHARMACIEN, Auth::ADMIN]);


try {

    $database = new Database();
    $db = $database->getConnection();

    $controller = new DashboardPharmacienController(
        new DashboardPharmacien($db),
        new Alerte($db),
        new Stock($db)
    );

    $controller->index($utilisateur);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur.'
    ], JSON_UNESCAPED_UNICODE);
}
