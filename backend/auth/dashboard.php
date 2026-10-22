<?php
// Ancien endpoint conservé pour éviter les erreurs de chemins dans d'éventuels liens historiques.
// Les statistiques du dashboard sont désormais chargées depuis la base via routes/employee-dashboard.php.
require_once __DIR__ . '/Auth.php';
$user = Auth::user();
if ($user === null) {
    header('Location: ../../frontend/pages/connexion.html?error=session');
    exit;
}
header('Location: ../../frontend/employee.html');
exit;