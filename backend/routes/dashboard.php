<?php

header('Content-Type: application/json; charset=utf-8');


/*
 * =====================================================
 * CORS
 * =====================================================
 *
 * Pour le développement avec XAMPP.
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


/*
 * Gestion des requêtes OPTIONS.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


/*
 * Le dashboard utilise uniquement GET.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Méthode HTTP non autorisée.'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
 * Chargement des fichiers.
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/DashboardA.php';
require_once __DIR__ . '/../controllers/DashboardAController.php';


try {

    /*
     * Connexion MySQL.
     */
    $database = new Database();

    $db = $database->getConnection();


    /*
     * Création du modèle.
     */
    $dashboardModel = new Dashboard($db);


    /*
     * Création du contrôleur.
     */
    $controller = new DashboardController($dashboardModel);


    /*
     * Exécution.
     */
    $controller->index();

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Erreur de connexion à la base de données.'
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Erreur interne du serveur.'
        ],
        JSON_UNESCAPED_UNICODE
    );
}