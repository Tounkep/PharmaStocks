<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$response = [
    'user' => ['prenom' => 'Thomas', 'role' => 'Employé'],
    'ventes' => [],
    'stocksFaibles' => []
];

try {
    // 1. Utilisateur connecté
    $stmtUser = $pdo->prepare("
        SELECT u.prenom, u.nom, r.nom AS role 
        FROM public.utilisateur u 
        JOIN public.role r ON u.role_id = r.id 
        WHERE u.id = :id
    ");
    $stmtUser->execute(['id' => 1]);
    $userData = $stmtUser->fetch();
    if ($userData) {
        $response['user'] = $userData;
    }

    // 2. Dernières ventes
    $stmtVentes = $pdo->query("
        SELECT id, TO_CHAR(datevente, 'HH24:MI') AS heure, montanttotal 
        FROM public.vente 
        ORDER BY id DESC 
        LIMIT 4
    ");
    $response['ventes'] = $stmtVentes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Produits en stock faible
    $stmtStock = $pdo->query("
        SELECT p.nom, COALESCE(SUM(l.quantite), 0) AS total_quantite, p.seuilminimum
        FROM public.produit p
        LEFT JOIN public.lot l ON p.id = l.produit_id
        GROUP BY p.id, p.nom, p.seuilminimum
        HAVING COALESCE(SUM(l.quantite), 0) <= p.seuilminimum
        LIMIT 4
    ");
    $response['stocksFaibles'] = $stmtStock->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD : ' . $e->getMessage()]);
    exit;
}

echo json_encode($response);