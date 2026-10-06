<?php

class FournisseurController
{
    private Fournisseur $fournisseur;

    public function __construct(Fournisseur $fournisseur)
    {
        $this->fournisseur = $fournisseur;
    }

    /**
     * GET : récupérer tous les fournisseurs
     */
    public function index(): void
    {
        try {

            $fournisseurs = $this->fournisseur->getAll();

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'data' => [
                    'fournisseurs' => $fournisseurs
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération des fournisseurs.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * POST : ajouter un fournisseur
     */
    public function ajouter(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $nom = trim($data['nom'] ?? '');
        $adresse = trim($data['adresse'] ?? '');
        $telephone = trim($data['telephone'] ?? '');
        $email = trim($data['email'] ?? '');

        if ($nom === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Le nom du fournisseur est obligatoire.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $email !== ''
            && !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Adresse email invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {

            if (
                $this->fournisseur->existe(
                    $nom,
                    $email
                )
            ) {

                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'Ce fournisseur existe déjà.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $this->fournisseur->ajouter(
                $nom,
                $adresse,
                $telephone,
                $email
            );

            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' => 'Fournisseur ajouté avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l’ajout du fournisseur.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * PUT : modifier un fournisseur
     */
    public function modifier(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $nom = trim($data['nom'] ?? '');
        $adresse = trim($data['adresse'] ?? '');
        $telephone = trim($data['telephone'] ?? '');
        $email = trim($data['email'] ?? '');

        if ($id <= 0 || $nom === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Données invalides.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $email !== ''
            && !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Adresse email invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {

            if (
                $this->fournisseur->existe(
                    $nom,
                    $email,
                    $id
                )
            ) {

                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' => 'Ce fournisseur existe déjà.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }

            $this->fournisseur->modifier(
                $id,
                $nom,
                $adresse,
                $telephone,
                $email
            );

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Fournisseur modifié avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la modification du fournisseur.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * DELETE : supprimer un fournisseur
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

        try {

            $this->fournisseur->supprimer($id);

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Fournisseur supprimé avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Impossible de supprimer ce fournisseur car il est utilisé dans une commande.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}