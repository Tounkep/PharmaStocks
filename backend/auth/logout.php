<?php

/*
|--------------------------------------------------------------------------
| Déconnexion
|--------------------------------------------------------------------------
|
| POST (fetch) : renvoie du JSON.
| GET (lien)   : redirige vers la page de connexion.
*/

require_once __DIR__ . '/Auth.php';

Auth::startSession();

$_SESSION = [];

session_destroy();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => true
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

header('Location: ../../frontend/pages/connexion.html');

exit;
