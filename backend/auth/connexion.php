<?php

session_start();


/* =========================================================
   DÉCONNEXION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "logout"
) {

    $_SESSION = [];

    session_destroy();

    echo json_encode([
        "success" => true
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =========================================================
   CONNEXION À LA BASE DE DONNÉES
========================================================= */

require_once __DIR__ . "/../config/Database.php";

$database = new Database();
$pdo = $database->getConnection();


/* =========================================================
   VÉRIFICATION DE LA MÉTHODE DE REQUÊTE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../../frontend/pages/connexion.html"
    );

    exit;
}


/* =========================================================
   RÉCUPÉRATION DES DONNÉES DU FORMULAIRE
========================================================= */

$email = trim(
    $_POST["email"] ?? ""
);

$motDePasse = trim(
    $_POST["password"] ?? ""
);


/* =========================================================
   VÉRIFICATION DES CHAMPS
========================================================= */

if (
    $email === "" ||
    $motDePasse === ""
) {

    header(
        "Location: ../../frontend/pages/connexion.html?error=champs_vides"
    );

    exit;
}


/* =========================================================
   RECHERCHE DE L'UTILISATEUR
========================================================= */

$sql = "
    SELECT
        u.id,
        u.nom,
        u.prenom,
        u.email,
        u.motDePasse,
        u.role_id,
        r.nom AS role

    FROM Utilisateur u

    INNER JOIN Role r
        ON u.role_id = r.id

    WHERE u.email = :email

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "email" => $email
]);

$utilisateur = $stmt->fetch();


/* =========================================================
   VÉRIFICATION DES IDENTIFIANTS
========================================================= */

if (
    !$utilisateur ||
    $utilisateur["motDePasse"] !== $motDePasse
) {

    header(
        "Location: ../../frontend/pages/connexion.html?error=identifiants"
    );

    exit;
}


/* =========================================================
   CRÉATION DE LA SESSION
========================================================= */

$_SESSION["utilisateur_id"] = $utilisateur["id"];
$_SESSION["nom"] = $utilisateur["nom"];
$_SESSION["prenom"] = $utilisateur["prenom"];
$_SESSION["email"] = $utilisateur["email"];
$_SESSION["role_id"] = $utilisateur["role_id"];
$_SESSION["role"] = $utilisateur["role"];


/* =========================================================
   REDIRECTION SELON LE RÔLE
========================================================= */

if ($utilisateur["role"] === "ADMIN") {

    header(
        "Location: ../../frontend/pages/dashboard-admin.html"
    );

    exit;
}


/* =========================================================
   AUTRES RÔLES
========================================================= */

if ($utilisateur["role"] === "PHARMACIEN") {

    header(
        "Location: ../../frontend/pages/dashboard-pharmacien.html"
    );

    exit;
}


if ($utilisateur["role"] === "EMPLOYE") {

    header(
        "Location: ../../frontend/pages/dashboard-employe.html"
    );

    exit;
}


/* =========================================================
   RÔLE INCONNU
========================================================= */

header(
    "Location: ../../frontend/pages/connexion.html?error=role_inconnu"
);

exit;