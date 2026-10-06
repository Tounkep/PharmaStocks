<?php

session_start();

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| Vérification de connexion
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['utilisateur_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Vérification du rôle
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role'])
    || $_SESSION['role'] !== 'ADMIN'
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' =>
            'Accès réservé à l’administrateur.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Imports
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/Database.php';

require_once __DIR__ . '/../models/Commande.php';

require_once __DIR__ . '/../controllers/CommandeController.php';


/*
|--------------------------------------------------------------------------
| Connexion à la base
|--------------------------------------------------------------------------
*/

try {

    $database =
        new Database();

    $db =
        $database->getConnection();


    /*
     * Création du modèle
     */
    $commandeModel =
        new Commande($db);


    /*
     * Création du contrôleur
     */
    $controller =
        new CommandeController(
            $commandeModel
        );


    /*
    |--------------------------------------------------------------------------
    | ROUTES
    |--------------------------------------------------------------------------
    */

    switch ($_SERVER['REQUEST_METHOD']) {


        /*
        |--------------------------------------------------------------------------
        | GET
        |--------------------------------------------------------------------------
        */

        case 'GET':

            $action =
                $_GET['action'] ?? 'index';


            if ($action === 'details') {

                $controller->details();

            } elseif ($action === 'options') {

                $controller->options();

            } else {

                $controller->index();
            }

            break;


        /*
        |--------------------------------------------------------------------------
        | POST
        |--------------------------------------------------------------------------
        */

        case 'POST':

            $controller->ajouter();

            break;


        /*
        |--------------------------------------------------------------------------
        | PUT
        |--------------------------------------------------------------------------
        */

        case 'PUT':

            $controller->modifier();

            break;


        /*
        |--------------------------------------------------------------------------
        | PATCH
        |--------------------------------------------------------------------------
        */

        case 'PATCH':

            $controller->modifierStatut();

            break;


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        case 'DELETE':

            $controller->supprimer();

            break;


        /*
        |--------------------------------------------------------------------------
        | Méthode inconnue
        |--------------------------------------------------------------------------
        */

        default:

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Méthode HTTP non autorisée.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Erreur de connexion à la base de données.'
    ], JSON_UNESCAPED_UNICODE);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Erreur interne du serveur.'
    ], JSON_UNESCAPED_UNICODE);
}