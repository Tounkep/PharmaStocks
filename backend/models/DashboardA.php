<?php

class Dashboard
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /**
     * =====================================================
     * STATISTIQUES PRINCIPALES
     * =====================================================
     */
    public function getStatistiques(): array
    {
        /*
         * Nombre total de produits
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Produit
        ");

        $totalProduits = (int) $stmt->fetch()['total'];


        /*
         * Nombre total d'utilisateurs
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Utilisateur
        ");

        $totalUtilisateurs = (int) $stmt->fetch()['total'];


        /*
         * Nombre total de fournisseurs
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Fournisseur
        ");

        $totalFournisseurs = (int) $stmt->fetch()['total'];


        /*
         * Nombre total de ventes
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Vente
        ");

        $totalVentes = (int) $stmt->fetch()['total'];


        /*
         * Chiffre d'affaires total
         */
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(montantTotal), 0) AS total
            FROM Vente
        ");

        $chiffreAffaires = (float) $stmt->fetch()['total'];


        /*
         * Quantité totale disponible en stock
         *
         * Le stock n'est pas stocké directement dans Produit.
         * Il est calculé à partir des lots disponibles.
         */
        $stmt = $this->db->query("
            SELECT COALESCE(SUM(quantite), 0) AS total
            FROM Lot
            WHERE statut = 'DISPONIBLE'
        ");

        $stockTotal = (int) $stmt->fetch()['total'];


        /*
         * Nombre de produits dont le stock est inférieur
         * ou égal au seuil minimum.
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM (
                SELECT
                    p.id,
                    p.seuilMinimum,
                    COALESCE(
                        SUM(
                            CASE
                                WHEN l.statut = 'DISPONIBLE'
                                THEN l.quantite
                                ELSE 0
                            END
                        ),
                        0
                    ) AS stock

                FROM Produit p

                LEFT JOIN Lot l
                    ON l.produit_id = p.id

                GROUP BY
                    p.id,
                    p.seuilMinimum

                HAVING stock <= p.seuilMinimum
            ) AS produits_stock_faible
        ");

        $stockFaible = (int) $stmt->fetch()['total'];


        /*
         * Nombre de lots disponibles expirant dans les 30 jours.
         *
         * IMPORTANT :
         * dateExpiration se trouve dans Lot,
         * pas dans Produit.
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Lot
            WHERE statut = 'DISPONIBLE'
              AND dateExpiration >= CURDATE()
              AND dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");

        $expirationsProches = (int) $stmt->fetch()['total'];


        /*
         * Nombre de lots déjà expirés.
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Lot
            WHERE statut <> 'PERIME'
              AND dateExpiration < CURDATE()
        ");

        $lotsExpires = (int) $stmt->fetch()['total'];


        /*
         * Nombre de commandes en attente.
         */
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Commande
            WHERE statut = 'EN_ATTENTE'
        ");

        $commandesEnAttente = (int) $stmt->fetch()['total'];


        return [
            'totalProduits' => $totalProduits,
            'totalUtilisateurs' => $totalUtilisateurs,
            'totalFournisseurs' => $totalFournisseurs,
            'totalVentes' => $totalVentes,
            'chiffreAffaires' => $chiffreAffaires,
            'stockTotal' => $stockTotal,
            'stockFaible' => $stockFaible,
            'expirationsProches' => $expirationsProches,
            'lotsExpires' => $lotsExpires,
            'commandesEnAttente' => $commandesEnAttente
        ];
    }


    /**
     * =====================================================
     * PRODUITS EN STOCK FAIBLE
     * =====================================================
     */
    public function getProduitsStockFaible(): array
    {
        $sql = "
            SELECT
                p.id,
                p.reference,
                p.nom,
                p.seuilMinimum,

                COALESCE(
                    SUM(
                        CASE
                            WHEN l.statut = 'DISPONIBLE'
                            THEN l.quantite
                            ELSE 0
                        END
                    ),
                    0
                ) AS stock

            FROM Produit p

            LEFT JOIN Lot l
                ON l.produit_id = p.id

            GROUP BY
                p.id,
                p.reference,
                p.nom,
                p.seuilMinimum

            HAVING stock <= p.seuilMinimum

            ORDER BY stock ASC, p.nom ASC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * LOTS PROCHES DE L'EXPIRATION
     * =====================================================
     */
    public function getExpirationsProches(): array
    {
        $sql = "
            SELECT
                l.id AS lot_id,
                l.numeroLot,
                l.quantite,
                l.dateReception,
                l.dateExpiration,
                l.statut,

                p.id AS produit_id,
                p.reference,
                p.nom

            FROM Lot l

            INNER JOIN Produit p
                ON p.id = l.produit_id

            WHERE l.statut = 'DISPONIBLE'
              AND l.dateExpiration >= CURDATE()
              AND l.dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)

            ORDER BY
                l.dateExpiration ASC,
                p.nom ASC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * LOTS DEJA EXPIRES
     * =====================================================
     */
    public function getLotsExpires(): array
    {
        $sql = "
            SELECT
                l.id AS lot_id,
                l.numeroLot,
                l.quantite,
                l.dateExpiration,
                l.statut,

                p.id AS produit_id,
                p.reference,
                p.nom

            FROM Lot l

            INNER JOIN Produit p
                ON p.id = l.produit_id

            WHERE l.dateExpiration < CURDATE()

            ORDER BY
                l.dateExpiration ASC,
                p.nom ASC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * DERNIERES VENTES
     * =====================================================
     */
    public function getDernieresVentes(): array
    {
        $sql = "
            SELECT
                v.id,
                v.dateVente,
                v.montantTotal,

                u.id AS utilisateur_id,
                u.nom,
                u.prenom

            FROM Vente v

            INNER JOIN Utilisateur u
                ON u.id = v.utilisateur_id

            ORDER BY
                v.dateVente DESC,
                v.id DESC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * DERNIERS MOUVEMENTS DE STOCK
     * =====================================================
     */
    public function getDerniersMouvements(): array
    {
        $sql = "
            SELECT
                m.id,
                m.type,
                m.quantite,
                m.dateHeure,
                m.motif,

                l.id AS lot_id,
                l.numeroLot,

                p.id AS produit_id,
                p.reference,
                p.nom AS produit,

                u.id AS utilisateur_id,
                u.nom,
                u.prenom

            FROM MouvementStock m

            INNER JOIN Lot l
                ON l.id = m.lot_id

            INNER JOIN Produit p
                ON p.id = l.produit_id

            INNER JOIN Utilisateur u
                ON u.id = m.utilisateur_id

            ORDER BY
                m.dateHeure DESC,
                m.id DESC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * ALERTES
     * =====================================================
     */
    public function getAlertes(): array
    {
        $sql = "
            SELECT
                a.id,
                a.type,
                a.message,
                a.dateCreation,
                a.statut,

                p.id AS produit_id,
                p.reference,
                p.nom AS produit,

                l.id AS lot_id,
                l.numeroLot

            FROM Alerte a

            LEFT JOIN Produit p
                ON p.id = a.produit_id

            LEFT JOIN Lot l
                ON l.id = a.lot_id

            WHERE a.statut = 'NOUVELLE'

            ORDER BY
                a.dateCreation DESC,
                a.id DESC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * ACTIVITE RECENTE
     * =====================================================
     */
    public function getActiviteRecente(): array
    {
        $sql = "
            SELECT
                j.id,
                j.action,
                j.description,
                j.dateHeure,

                u.id AS utilisateur_id,
                u.nom,
                u.prenom,
                u.email

            FROM JournalActivite j

            INNER JOIN Utilisateur u
                ON u.id = j.utilisateur_id

            ORDER BY
                j.dateHeure DESC,
                j.id DESC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * VENTES DES 7 DERNIERS JOURS
     * =====================================================
     */
    public function getVentesDerniersJours(): array
    {
        $sql = "
            SELECT
                dateVente,
                COUNT(*) AS nombreVentes,
                COALESCE(SUM(montantTotal), 0) AS total

            FROM Vente

            WHERE dateVente >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)

            GROUP BY dateVente

            ORDER BY dateVente ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /**
     * =====================================================
     * COMMANDES RECENTES
     * =====================================================
     */
    public function getCommandesRecentes(): array
    {
        $sql = "
            SELECT
                c.id,
                c.dateCommande,
                c.statut,
                c.montantTotal,

                f.id AS fournisseur_id,
                f.nom AS fournisseur,

                u.id AS utilisateur_id,
                u.nom,
                u.prenom

            FROM Commande c

            INNER JOIN Fournisseur f
                ON f.id = c.fournisseur_id

            INNER JOIN Utilisateur u
                ON u.id = c.utilisateur_id

            ORDER BY
                c.dateCommande DESC,
                c.id DESC

            LIMIT 10
        ";

        return $this->db->query($sql)->fetchAll();
    }
}