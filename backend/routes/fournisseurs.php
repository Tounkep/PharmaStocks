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
| Le pharmacien peut consulter les fournisseurs (GET).
| Seul l'administrateur peut les modifier.
*/

$rolesAutorises =
    $_SERVER['REQUEST_METHOD'] === 'GET'
        ? ['ADMIN', 'PHARMACIEN']
        : ['ADMIN'];

if (
    !isset($_SESSION['role'])
    || !in_array($_SESSION['role'], $rolesAutorises, true)
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Accès réservé à l’administrateur.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
|--------------------------------------------------------------------------
| Imports
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Fournisseur.php';
require_once __DIR__ . '/../controllers/FournisseurController.php';

/*
|--------------------------------------------------------------------------
| Connexion + contrôleur
|--------------------------------------------------------------------------
*/

try {

    $database = new Database();

    $db = $database->getConnection();

    $fournisseurModel = new Fournisseur($db);

    $controller = new FournisseurController(
        $fournisseurModel
    );

    /*
    |--------------------------------------------------------------------------
    | Routes selon la méthode HTTP
    |--------------------------------------------------------------------------
    */

    switch ($_SERVER['REQUEST_METHOD']) {

        case 'GET':

            $controller->index();

            break;

        case 'POST':

            $controller->ajouter();

            break;

        case 'PUT':

            $controller->modifier();

            break;

        case 'DELETE':

            $controller->supprimer();

            break;

        default:

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' => 'Méthode HTTP non autorisée.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur.'
    ], JSON_UNESCAPED_UNICODE);
}