<?php

class UtilisateurController
{
    private Utilisateur $utilisateur;

    public function __construct(Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;
    }

    /**
     * Afficher les utilisateurs et les rôles
     */
    public function index(): void
    {
        try {

            $utilisateurs = $this->utilisateur->getAll();
            $roles = $this->utilisateur->getRoles();

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'data' => [
                    'utilisateurs' => $utilisateurs,
                    'roles' => $roles
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Ajouter un utilisateur
     */
    public function ajouter(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $email = trim($data['email'] ?? '');
        $motDePasse = $data['motDePasse'] ?? '';
        $roleId = (int) ($data['role_id'] ?? 0);

        if (
            $nom === '' ||
            $prenom === '' ||
            $email === '' ||
            $motDePasse === '' ||
            $roleId <= 0
        ) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Tous les champs sont obligatoires.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Adresse email invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {

            if ($this->utilisateur->emailExiste($email)) {

                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'Cette adresse email est déjà utilisée.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $this->utilisateur->ajouter(
                $nom,
                $prenom,
                $email,
                $motDePasse,
                $roleId
            );

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur ajouté avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l’ajout de l’utilisateur.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Modifier un utilisateur
     */
    public function modifier(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $email = trim($data['email'] ?? '');
        $motDePasse = $data['motDePasse'] ?? '';
        $roleId = (int) ($data['role_id'] ?? 0);

        if (
            $id <= 0 ||
            $nom === '' ||
            $prenom === '' ||
            $email === '' ||
            $roleId <= 0
        ) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Données invalides.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Adresse email invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {

            if ($this->utilisateur->emailExiste($email, $id)) {

                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'Cette adresse email est déjà utilisée.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $this->utilisateur->modifier(
                $id,
                $nom,
                $prenom,
                $email,
                $roleId,
                $motDePasse
            );

            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur modifié avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la modification de l’utilisateur.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function supprimer(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Identifiant invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        // Empêcher l'administrateur de supprimer son propre compte
        if (
            isset($_SESSION['utilisateur_id']) &&
            (int) $_SESSION['utilisateur_id'] === $id
        ) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {

            $this->utilisateur->supprimer($id);

            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l’utilisateur.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}