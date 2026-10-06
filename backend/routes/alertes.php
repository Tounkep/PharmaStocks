<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Stock.php';
require_once __DIR__ . '/../models/Alerte.php';
require_once __DIR__ . '/../controllers/AlerteController.php';


/*
|--------------------------------------------------------------------------
| Droits
|--------------------------------------------------------------------------
| Consultation : tous les utilisateurs connectés.
| Changement de statut : administrateur et pharmacien.
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireLogin();
} else {
    $utilisateur = Auth::requireRole([Auth::ADMIN, Auth::PHARMACIEN]);
}


try {

    $database = new Database();
    $db = $database->getConnection();

    $controller = new AlerteController(
        new Alerte($db),
        new Stock($db)
    );

    switch ($_SERVER['REQUEST_METHOD']) {

        case 'GET':

            $controller->index();

            break;


        case 'PATCH':

            $controller->changerStatut($utilisateur['id']);

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

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur.'
    ], JSON_UNESCAPED_UNICODE);
}
