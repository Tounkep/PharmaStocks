<?php

/*
|--------------------------------------------------------------------------
| Utilisateur connecté
|--------------------------------------------------------------------------
|
| GET : renvoie l'utilisateur de la session (tous les rôles).
| Utilisé par les pages partagées (médicaments, ventes, commandes,
| fournisseurs) pour afficher le profil et adapter le menu au rôle.
*/

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Méthode HTTP non autorisée.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$utilisateur = Auth::requireLogin();

echo json_encode([
    'success' => true,
    'data' => [
        'utilisateur' => $utilisateur
    ]
], JSON_UNESCAPED_UNICODE);
