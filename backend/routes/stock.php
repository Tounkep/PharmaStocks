<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Stock.php';
require_once __DIR__ . '/../controllers/StockController.php';


/*
|--------------------------------------------------------------------------
| Droits
|--------------------------------------------------------------------------
| Consultation : tous les utilisateurs connectés.
| Modification : administrateur et pharmacien.
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireLogin();
} else {
    $utilisateur = Auth::requireRole([Auth::ADMIN, Auth::PHARMACIEN]);
}


try {

    $database = new Database();

    $controller = new StockController(
        new Stock($database->getConnection())
    );

    switch ($_SERVER['REQUEST_METHOD']) {

        case 'GET':

            $action = $_GET['action'] ?? 'lots';

            if ($action === 'mouvements') {
                $controller->mouvements();
            } elseif ($action === 'produits') {
                $controller->produits();
            } elseif ($action === 'commandes') {
                $controller->commandes();
            } elseif ($action === 'receptions') {
                $controller->receptions();
            } else {
                $controller->index();
            }

            break;


        case 'POST':

            $controller->executer($utilisateur['id']);

            break;


        default:

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' => 'Méthode HTTP non autorisée.'
            ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données.'
    ], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {

    http_response_code(409);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur.'
    ], JSON_UNESCAPED_UNICODE);
}
