
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Medicament.php';
require_once __DIR__ . '/../controllers/MedicamentController.php';

/*
 * Consultation : tous les utilisateurs connectés.
 * Modification : administrateur et pharmacien.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::requireLogin();
} else {
    Auth::requireRole([Auth::ADMIN, Auth::PHARMACIEN]);
}

try {

    $database = new Database();

    $db = $database->getConnection();

    $model = new Medicament($db);

    $controller = new MedicamentController($model);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        if (isset($_GET['id'])) {

            $id = filter_input(
                INPUT_GET,
                'id',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id <= 0) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' => 'ID du médicament invalide.'
                ]);

                exit;
            }

            $controller->show($id);

            exit;
        }

        if (isset($_GET['categories'])) {

            $controller->categories();

            exit;
        }

        $controller->index();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = is_array($input) ? ($input['action'] ?? '') : '';

        if ($action === 'createLot') {
            $controller->createLot();
            exit;
        }

        if ($action === 'stockMovement') {
            $controller->createMovement();
            exit;
        }

        $controller->create();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

        $input = json_decode(file_get_contents('php://input'), true);
        $action = is_array($input) ? ($input['action'] ?? '') : '';

        if ($action === 'updateLot') {
            $controller->updateLot();
            exit;
        }

        if ($action === 'stockMovement') {
            $controller->createMovement();
            exit;
        }

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID du médicament manquant.'
            ]);
            exit;
        }

        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID du médicament invalide.'
            ]);
            exit;
        }

        $controller->update();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID du médicament manquant.'
            ]);
            exit;
        }

        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID du médicament invalide.'
            ]);
            exit;
        }

        $controller->delete();
        exit;
    }

    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données.'
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}