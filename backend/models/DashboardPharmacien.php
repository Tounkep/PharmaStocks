<?php

class DashboardPharmacien
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* =====================================================
       STATISTIQUES
    ====================================================== */

    public function getStatistiques(): array
    {
        $produits = $this->db->query("
            SELECT
                COUNT(*) AS totalProduits,
                COALESCE(SUM(stock), 0) AS stockTotal,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS ruptures,
                SUM(CASE WHEN stock > 0 AND stock <= seuilMinimum THEN 1 ELSE 0 END) AS stockFaible

            FROM (
                SELECT
                    p.id,
                    p.seuilMinimum,
                    COALESCE(SUM(
                        CASE
                            WHEN l.statut = 'DISPONIBLE'
                             AND l.dateExpiration >= CURDATE()
                            THEN l.quantite
                            ELSE 0
                        END
                    ), 0) AS stock

                FROM Produit p

                LEFT JOIN Lot l
                    ON l.produit_id = p.id

                GROUP BY
                    p.id,
                    p.seuilMinimum
            ) AS stocks
        ")->fetch();

        $ventesJour = $this->db->query("
            SELECT
                COUNT(*) AS nombre,
                COALESCE(SUM(montantTotal), 0) AS montant
            FROM Vente
            WHERE dateVente = CURDATE()
        ")->fetch();

        $ventesMois = $this->db->query("
            SELECT COALESCE(SUM(montantTotal), 0) AS montant
            FROM Vente
            WHERE dateVente >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ")->fetch();

        $lots = $this->db->query("
            SELECT
                SUM(
                    CASE
                        WHEN statut = 'DISPONIBLE'
                         AND quantite > 0
                         AND dateExpiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        THEN 1 ELSE 0
                    END
                ) AS expirationsProches,

                SUM(
                    CASE
                        WHEN quantite > 0
                         AND dateExpiration < CURDATE()
                        THEN 1 ELSE 0
                    END
                ) AS lotsPerimes
            FROM Lot
        ")->fetch();

        $commandes = $this->db->query("
            SELECT COUNT(*) AS total
            FROM Commande
            WHERE statut IN ('EN_ATTENTE', 'VALIDEE')
        ")->fetch();

        return [
            'totalProduits' => (int)$produits['totalProduits'],
            'stockTotal' => (int)$produits['stockTotal'],
            'ruptures' => (int)$produits['ruptures'],
            'stockFaible' => (int)$produits['stockFaible'],
            'ventesJour' => (int)$ventesJour['nombre'],
            'chiffreAffairesJour' => (float)$ventesJour['montant'],
            'chiffreAffairesMois' => (float)$ventesMois['montant'],
            'expirationsProches' => (int)$lots['expirationsProches'],
            'lotsPerimes' => (int)$lots['lotsPerimes'],
            'commandesEnCours' => (int)$commandes['total']
        ];
    }


    /* =====================================================
       VENTES DES 7 DERNIERS JOURS (JOURS SANS VENTE INCLUS)
    ====================================================== */

    public function getVentes7Jours(): array
    {
        $rows = $this->db->query("
            SELECT
                dateVente,
                COUNT(*) AS nombre,
                COALESCE(SUM(montantTotal), 0) AS total
            FROM Vente
            WHERE dateVente >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY dateVente
        ")->fetchAll();

        $parJour = [];

        foreach ($rows as $row) {
            $parJour[$row['dateVente']] = $row;
        }

        $jours = [];

        for ($i = 6; $i >= 0; $i--) {

            $date = date('Y-m-d', strtotime("-{$i} day"));

            $jours[] = [
                'date' => $date,
                'nombre' => (int)($parJour[$date]['nombre'] ?? 0),
                'total' => (float)($parJour[$date]['total'] ?? 0)
            ];
        }

        return $jours;
    }


    /* =====================================================
       PRODUITS À RÉAPPROVISIONNER
    ====================================================== */

    public function getProduitsAReapprovisionner(): array
    {
        return $this->db->query("
            SELECT *
            FROM (
                SELECT
                    p.id,
                    p.reference,
                    p.nom,
                    p.seuilMinimum,
                    COALESCE(SUM(
                        CASE
                            WHEN l.statut = 'DISPONIBLE'
                             AND l.dateExpiration >= CURDATE()
                            THEN l.quantite
                            ELSE 0
                        END
                    ), 0) AS stock

                FROM Produit p

                LEFT JOIN Lot l
                    ON l.produit_id = p.id

                GROUP BY
                    p.id,
                    p.reference,
                    p.nom,
                    p.seuilMinimum
            ) AS stocks

            WHERE stock <= seuilMinimum

            ORDER BY
                stock ASC,
                nom ASC

            LIMIT 8
        ")->fetchAll();
    }


    /* =====================================================
       LOTS QUI EXPIRENT BIENTÔT OU SONT PÉRIMÉS
    ====================================================== */

    public function getLotsAExpiration(): array
    {
        return $this->db->query("
            SELECT
                l.id,
                l.numeroLot,
                l.quantite,
                l.dateExpiration,
                DATEDIFF(l.dateExpiration, CURDATE()) AS joursRestants,
                p.id AS produit_id,
                p.nom AS produit

            FROM Lot l

            INNER JOIN Produit p
                ON p.id = l.produit_id

            WHERE l.quantite > 0
              AND l.statut <> 'EPUISE'
              AND l.dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)

            ORDER BY l.dateExpiration ASC

            LIMIT 8
        ")->fetchAll();
    }


    /* =====================================================
       DERNIERS MOUVEMENTS
    ====================================================== */

    public function getDerniersMouvements(): array
    {
        return $this->db->query("
            SELECT
                m.id,
                m.type,
                m.quantite,
                m.dateHeure,
                m.motif,
                l.numeroLot,
                p.nom AS produit,
                u.prenom,
                u.nom

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

            LIMIT 8
        ")->fetchAll();
    }


    /* =====================================================
       COMMANDES EN COURS
    ====================================================== */

    public function getCommandesEnCours(): array
    {
        return $this->db->query("
            SELECT
                c.id,
                c.dateCommande,
                c.statut,
                c.montantTotal,
                f.nom AS fournisseur

            FROM Commande c

            INNER JOIN Fournisseur f
                ON f.id = c.fournisseur_id

            WHERE c.statut IN ('EN_ATTENTE', 'VALIDEE')

            ORDER BY
                c.dateCommande ASC,
                c.id ASC

            LIMIT 6
        ")->fetchAll();
    }
}
