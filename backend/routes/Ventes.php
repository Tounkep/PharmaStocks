<?php

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Access-Control-Allow-Origin: *'
);

header(
    'Access-Control-Allow-Methods: GET, POST, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type'
);


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    http_response_code(200);

    exit;
}


require_once __DIR__ .
    '/../auth/Auth.php';

/*
 * Ventes : tous les utilisateurs connectés.
 */
Auth::requireLogin();

require_once __DIR__ .
    '/../config/Database.php';

require_once __DIR__ .
    '/../models/Vente.php';

require_once __DIR__ .
    '/../controllers/VenteController.php';


try {

    $database =
        new Database();

    $db =
        $database->getConnection();


    $controller =
        new VenteController($db);


    $method =
        $_SERVER['REQUEST_METHOD'];


    /* =====================================================
       GET
    ====================================================== */

    if ($method === 'GET') {

        $action =
            $_GET['action'] ??
            'products';


        switch ($action) {

            case 'products':

                $controller->products();

                break;


            case 'history':

                $controller->history();

                break;


            case 'detail':

                $id =
                    filter_var(
                        $_GET['id'] ?? null,
                        FILTER_VALIDATE_INT
                    );


                if (!$id) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'ID de vente invalide.'
                    ]);

                    exit;
                }


                $controller->detail($id);

                break;


            default:

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Action inconnue.'
                ]);

                break;
        }


        exit;
    }



    /* =====================================================
       POST
    ====================================================== */

    if ($method === 'POST') {

        $controller->create();

        exit;
    }



    /* =====================================================
       METHOD NOT ALLOWED
    ====================================================== */

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' =>
            'Méthode HTTP non autorisée.'
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Erreur de connexion à la base de données.'
    ]);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Erreur serveur.'
    ]);
}