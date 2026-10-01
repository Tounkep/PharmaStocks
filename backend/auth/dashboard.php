<?php
// =========================================================
// BACKEND PHP : Traitement des données et de la session
// =========================================================
session_start();

// Traitement de l'ajout d'une nouvelle vente via le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_sale') {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $new_id = rand(4583, 4999);
        $new_sale = [
            'id' => '#4' . $new_id,
            'amount' => number_format($amount, 2, ',', ' ') . ' €',
            'time' => date('H:i')
        ];
        $_SESSION['sales'][] = $new_sale;
    }
    header('Location: index.php');
    exit;
}

// Données initiales de la session par défaut
if (!isset($_SESSION['sales'])) {
    $_SESSION['sales'] = [
        ['id' => '#4582', 'amount' => '24,50 €', 'time' => '14:21'],
        ['id' => '#4581', 'amount' => '42,80 €', 'time' => '14:10'],
        ['id' => '#4580', 'amount' => '12,30 €', 'time' => '13:52'],
        ['id' => '#4579', 'amount' => '8,90 €', 'time' => '12:37'],
    ];
}

// Utilisateur connecté
$user = [
    'name' => 'Thomas',
    'role' => 'Employé',
    'notifications' => 5
];

// Statistiques d'informations rapides
$quick_info = [
    'low_stock' => 8,
    'out_of_stock' => 5,
    'expiring_soon' => 3
];
?>