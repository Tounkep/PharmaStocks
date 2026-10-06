<?php

class CommandeController
{
    private Commande $commande;

    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
    }


    /**
     * GET /commandes.php
     *
     * Récupérer toutes les commandes
     */
    public function index(): void
    {
        try {

            $commandes =
                $this->commande->getAll();

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'data' => [
                    'commandes' => $commandes
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Erreur lors de la récupération des commandes.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * GET /commandes.php?action=details&id=1
     *
     * Récupérer une commande + ses produits
     */
    public function details(): void
    {
        $id =
            (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Identifiant de commande invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        try {

            $commande =
                $this->commande->getById($id);


            if (!$commande) {

                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Commande introuvable.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }


            $details =
                $this->commande->getDetails($id);


            http_response_code(200);

            echo json_encode([
                'success' => true,
                'data' => [
                    'commande' => $commande,
                    'details' => $details
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Erreur lors de la récupération des détails.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * GET /commandes.php?action=options
     *
     * Récupérer fournisseurs + produits
     * pour le formulaire de création.
     */
    public function options(): void
    {
        try {

            $fournisseurs =
                $this->commande->getFournisseurs();

            $produits =
                $this->commande->getProduits();


            http_response_code(200);

            echo json_encode([
                'success' => true,
                'data' => [
                    'fournisseurs' => $fournisseurs,
                    'produits' => $produits
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Erreur lors de la récupération des données.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * POST /commandes.php
     *
     * Créer une commande
     */
    public function ajouter(): void
    {
        $data =
            json_decode(
                file_get_contents('php://input'),
                true
            );


        $dateCommande =
            trim($data['dateCommande'] ?? '');

        $statut =
            trim($data['statut'] ?? 'EN_ATTENTE');

        $fournisseurId =
            (int) ($data['fournisseur_id'] ?? 0);

        $details =
            $data['details'] ?? [];


        /*
         * Date par défaut
         */
        if ($dateCommande === '') {

            $dateCommande =
                date('Y-m-d');
        }


        /*
         * Statuts autorisés
         */
        $statutsAutorises = [
            'EN_ATTENTE',
            'VALIDEE',
            'RECUE',
            'ANNULEE'
        ];


        if (
            !in_array(
                $statut,
                $statutsAutorises,
                true
            )
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Statut de commande invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        /*
         * Fournisseur obligatoire
         */
        if ($fournisseurId <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Veuillez sélectionner un fournisseur.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        /*
         * Au moins un produit
         */
        if (
            !is_array($details)
            || count($details) === 0
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Une commande doit contenir au moins un produit.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        /*
         * Vérifier le fournisseur
         */
        try {

            if (
                !$this->commande
                    ->fournisseurExiste($fournisseurId)
            ) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Le fournisseur sélectionné n’existe pas.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }


            /*
             * Nettoyer et vérifier les détails
             */
            $detailsValides = [];


            foreach ($details as $detail) {

                $produitId =
                    (int) ($detail['produit_id'] ?? 0);

                $quantite =
                    (int) ($detail['quantite'] ?? 0);

                $prixUnitaire =
                    (float) ($detail['prixUnitaire'] ?? 0);


                if ($produitId <= 0) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Un produit sélectionné est invalide.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                if ($quantite <= 0) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'La quantité doit être supérieure à zéro.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                if ($prixUnitaire < 0) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Le prix unitaire ne peut pas être négatif.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                if (
                    !$this->commande
                        ->produitExiste($produitId)
                ) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Un des produits sélectionnés n’existe pas.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                $detailsValides[] = [
                    'produit_id' =>
                        $produitId,

                    'quantite' =>
                        $quantite,

                    'prixUnitaire' =>
                        $prixUnitaire
                ];
            }


            /*
             * Utilisateur connecté
             */
            $utilisateurId =
                (int) ($_SESSION['utilisateur_id'] ?? 0);


            if ($utilisateurId <= 0) {

                http_response_code(401);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Utilisateur non connecté.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }


            /*
             * Création
             */
            $commandeId =
                $this->commande->ajouter(
                    $dateCommande,
                    $statut,
                    $fournisseurId,
                    $utilisateurId,
                    $detailsValides
                );


            http_response_code(201);

            echo json_encode([
                'success' => true,
                'message' =>
                    'Commande créée avec succès.',
                'data' => [
                    'id' => $commandeId
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Erreur lors de la création de la commande.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * PUT /commandes.php?id=1
     *
     * Modifier une commande complète
     */
    public function modifier(): void
    {
        $id =
            (int) ($_GET['id'] ?? 0);


        $data =
            json_decode(
                file_get_contents('php://input'),
                true
            );


        if ($id <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Identifiant de commande invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        $dateCommande =
            trim($data['dateCommande'] ?? '');

        $statut =
            trim($data['statut'] ?? '');

        $fournisseurId =
            (int) ($data['fournisseur_id'] ?? 0);

        $details =
            $data['details'] ?? [];


        $statutsAutorises = [
            'EN_ATTENTE',
            'VALIDEE',
            'RECUE',
            'ANNULEE'
        ];


        if ($dateCommande === '') {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'La date de commande est obligatoire.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        if (
            !in_array(
                $statut,
                $statutsAutorises,
                true
            )
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Statut de commande invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        if ($fournisseurId <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Veuillez sélectionner un fournisseur.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        if (
            !is_array($details)
            || count($details) === 0
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Une commande doit contenir au moins un produit.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        try {

            if (
                !$this->commande
                    ->fournisseurExiste($fournisseurId)
            ) {

                http_response_code(400);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Le fournisseur sélectionné n’existe pas.'
                ], JSON_UNESCAPED_UNICODE);

                return;
            }


            $detailsValides = [];


            foreach ($details as $detail) {

                $produitId =
                    (int) ($detail['produit_id'] ?? 0);

                $quantite =
                    (int) ($detail['quantite'] ?? 0);

                $prixUnitaire =
                    (float) ($detail['prixUnitaire'] ?? 0);


                if ($produitId <= 0) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Produit invalide.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                if ($quantite <= 0) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'La quantité doit être supérieure à zéro.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                if ($prixUnitaire < 0) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Le prix unitaire ne peut pas être négatif.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                if (
                    !$this->commande
                        ->produitExiste($produitId)
                ) {

                    http_response_code(400);

                    echo json_encode([
                        'success' => false,
                        'message' =>
                            'Un produit sélectionné n’existe pas.'
                    ], JSON_UNESCAPED_UNICODE);

                    return;
                }


                $detailsValides[] = [
                    'produit_id' =>
                        $produitId,

                    'quantite' =>
                        $quantite,

                    'prixUnitaire' =>
                        $prixUnitaire
                ];
            }


            $this->commande->modifier(
                $id,
                $dateCommande,
                $statut,
                $fournisseurId,
                $detailsValides
            );


            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' =>
                    'Commande modifiée avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Erreur lors de la modification de la commande.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * PATCH /commandes.php?id=1
     *
     * Modifier uniquement le statut
     */
    public function modifierStatut(): void
    {
        $id =
            (int) ($_GET['id'] ?? 0);


        $data =
            json_decode(
                file_get_contents('php://input'),
                true
            );


        $statut =
            trim($data['statut'] ?? '');


        $statutsAutorises = [
            'EN_ATTENTE',
            'VALIDEE',
            'RECUE',
            'ANNULEE'
        ];


        if ($id <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Identifiant invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        if (
            !in_array(
                $statut,
                $statutsAutorises,
                true
            )
        ) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Statut invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        try {

            $this->commande
                ->modifierStatut(
                    $id,
                    $statut
                );


            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' =>
                    'Statut de la commande modifié avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Erreur lors de la modification du statut.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * DELETE /commandes.php?id=1
     */
    public function supprimer(): void
    {
        $id =
            (int) ($_GET['id'] ?? 0);


        if ($id <= 0) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Identifiant de commande invalide.'
            ], JSON_UNESCAPED_UNICODE);

            return;
        }


        try {

            $this->commande
                ->supprimer($id);


            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' =>
                    'Commande supprimée avec succès.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Impossible de supprimer cette commande.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}