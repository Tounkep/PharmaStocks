<?php

class Commande
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer toutes les commandes
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                c.id,
                c.dateCommande,
                c.statut,
                c.montantTotal,
                c.fournisseur_id,
                f.nom AS fournisseur,
                c.utilisateur_id,
                CONCAT(u.prenom, ' ', u.nom) AS utilisateur
            FROM Commande c
            INNER JOIN Fournisseur f
                ON c.fournisseur_id = f.id
            INNER JOIN Utilisateur u
                ON c.utilisateur_id = u.id
            ORDER BY c.id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }


    /**
     * Récupérer une commande précise
     */
    public function getById(int $id): ?array
    {
        $sql = "
            SELECT
                c.id,
                c.dateCommande,
                c.statut,
                c.montantTotal,
                c.fournisseur_id,
                f.nom AS fournisseur,
                c.utilisateur_id,
                CONCAT(u.prenom, ' ', u.nom) AS utilisateur
            FROM Commande c
            INNER JOIN Fournisseur f
                ON c.fournisseur_id = f.id
            INNER JOIN Utilisateur u
                ON c.utilisateur_id = u.id
            WHERE c.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $commande = $stmt->fetch();

        return $commande ?: null;
    }


    /**
     * Récupérer les détails d'une commande
     */
    public function getDetails(int $commandeId): array
    {
        $sql = "
            SELECT
                dc.id,
                dc.commande_id,
                dc.produit_id,
                p.reference,
                p.nom AS produit,
                dc.quantite,
                dc.prixUnitaire,
                (dc.quantite * dc.prixUnitaire) AS sousTotal
            FROM DetailCommande dc
            INNER JOIN Produit p
                ON dc.produit_id = p.id
            WHERE dc.commande_id = :commande_id
            ORDER BY dc.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'commande_id' => $commandeId
        ]);

        return $stmt->fetchAll();
    }


    /**
     * Récupérer les fournisseurs
     *
     * Utilisé par le futur formulaire de commande.
     */
    public function getFournisseurs(): array
    {
        $sql = "
            SELECT
                id,
                nom
            FROM Fournisseur
            ORDER BY id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }


    /**
     * Récupérer les produits
     *
     * Utilisé par le futur formulaire de commande.
     */
    public function getProduits(): array
    {
        $sql = "
            SELECT
                id,
                reference,
                nom,
                prixAchat,
                prixVente
            FROM Produit
            ORDER BY id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }


    /**
     * Vérifier qu'un fournisseur existe
     */
    public function fournisseurExiste(int $fournisseurId): bool
    {
        $sql = "
            SELECT id
            FROM Fournisseur
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $fournisseurId
        ]);

        return $stmt->fetch() !== false;
    }


    /**
     * Vérifier qu'un produit existe
     */
    public function produitExiste(int $produitId): bool
    {
        $sql = "
            SELECT id
            FROM Produit
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $produitId
        ]);

        return $stmt->fetch() !== false;
    }


    /**
     * Ajouter une commande avec ses produits
     *
     * $details contient :
     *
     * [
     *     [
     *         'produit_id' => 1,
     *         'quantite' => 10,
     *         'prixUnitaire' => 5.50
     *     ],
     *     ...
     * ]
     */
    public function ajouter(
        string $dateCommande,
        string $statut,
        int $fournisseurId,
        int $utilisateurId,
        array $details
    ): int {

        $this->db->beginTransaction();

        try {

            /*
             * Calcul du montant total
             * côté serveur.
             */
            $montantTotal = 0;

            foreach ($details as $detail) {

                $quantite = (int) $detail['quantite'];

                $prixUnitaire =
                    (float) $detail['prixUnitaire'];

                $montantTotal +=
                    $quantite * $prixUnitaire;
            }

            /*
             * Création de la commande
             */
            $sql = "
                INSERT INTO Commande
                (
                    dateCommande,
                    statut,
                    montantTotal,
                    fournisseur_id,
                    utilisateur_id
                )
                VALUES
                (
                    :dateCommande,
                    :statut,
                    :montantTotal,
                    :fournisseur_id,
                    :utilisateur_id
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'dateCommande' => $dateCommande,
                'statut' => $statut,
                'montantTotal' => $montantTotal,
                'fournisseur_id' => $fournisseurId,
                'utilisateur_id' => $utilisateurId
            ]);

            $commandeId =
                (int) $this->db->lastInsertId();


            /*
             * Ajout des produits de la commande
             */
            $sqlDetail = "
                INSERT INTO DetailCommande
                (
                    quantite,
                    prixUnitaire,
                    commande_id,
                    produit_id
                )
                VALUES
                (
                    :quantite,
                    :prixUnitaire,
                    :commande_id,
                    :produit_id
                )
            ";

            $stmtDetail =
                $this->db->prepare($sqlDetail);


            foreach ($details as $detail) {

                $stmtDetail->execute([
                    'quantite' =>
                        (int) $detail['quantite'],

                    'prixUnitaire' =>
                        (float) $detail['prixUnitaire'],

                    'commande_id' =>
                        $commandeId,

                    'produit_id' =>
                        (int) $detail['produit_id']
                ]);
            }


            $this->db->commit();

            return $commandeId;

        } catch (Throwable $e) {

            $this->db->rollBack();

            throw $e;
        }
    }


    /**
     * Modifier une commande complète
     */
    public function modifier(
        int $id,
        string $dateCommande,
        string $statut,
        int $fournisseurId,
        array $details
    ): bool {

        $this->db->beginTransaction();

        try {

            /*
             * Recalcul du montant total
             */
            $montantTotal = 0;

            foreach ($details as $detail) {

                $quantite =
                    (int) $detail['quantite'];

                $prixUnitaire =
                    (float) $detail['prixUnitaire'];

                $montantTotal +=
                    $quantite * $prixUnitaire;
            }


            /*
             * Modification de la commande
             */
            $sql = "
                UPDATE Commande
                SET
                    dateCommande = :dateCommande,
                    statut = :statut,
                    montantTotal = :montantTotal,
                    fournisseur_id = :fournisseur_id
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'id' => $id,
                'dateCommande' => $dateCommande,
                'statut' => $statut,
                'montantTotal' => $montantTotal,
                'fournisseur_id' => $fournisseurId
            ]);


            /*
             * Suppression des anciens détails
             */
            $sqlDelete = "
                DELETE FROM DetailCommande
                WHERE commande_id = :commande_id
            ";

            $stmtDelete =
                $this->db->prepare($sqlDelete);

            $stmtDelete->execute([
                'commande_id' => $id
            ]);


            /*
             * Ajout des nouveaux détails
             */
            $sqlDetail = "
                INSERT INTO DetailCommande
                (
                    quantite,
                    prixUnitaire,
                    commande_id,
                    produit_id
                )
                VALUES
                (
                    :quantite,
                    :prixUnitaire,
                    :commande_id,
                    :produit_id
                )
            ";

            $stmtDetail =
                $this->db->prepare($sqlDetail);


            foreach ($details as $detail) {

                $stmtDetail->execute([
                    'quantite' =>
                        (int) $detail['quantite'],

                    'prixUnitaire' =>
                        (float) $detail['prixUnitaire'],

                    'commande_id' =>
                        $id,

                    'produit_id' =>
                        (int) $detail['produit_id']
                ]);
            }


            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            $this->db->rollBack();

            throw $e;
        }
    }


    /**
     * Modifier uniquement le statut
     */
    public function modifierStatut(
        int $id,
        string $statut
    ): bool {

        $sql = "
            UPDATE Commande
            SET statut = :statut
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'statut' => $statut
        ]);
    }


    /**
     * Supprimer une commande
     *
     * Les DetailCommande sont supprimés automatiquement
     * grâce au ON DELETE CASCADE de ta base.
     */
    public function supprimer(int $id): bool
    {
        $sql = "
            DELETE FROM Commande
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id
        ]);
    }
}