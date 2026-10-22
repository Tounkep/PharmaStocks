
<?php

class Rapport
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Statistiques générales sur les ventes et le stock.
     */
    public function getStatistiques(?string $dateDebut = null, ?string $dateFin = null): array
    {
        [$venteDateSql, $dateParams] = $this->dateFilter('dateVente', $dateDebut, $dateFin);
        // Chiffre d'affaires et nombre de ventes
        $sqlVentes = "
            SELECT
                COALESCE(SUM(montantTotal), 0) AS chiffreAffaires,
                COUNT(*) AS nombreVentes
            FROM Vente
            WHERE 1 = 1 {$venteDateSql}
        ";

        $stmt = $this->db->prepare($sqlVentes);
        $stmt->execute($dateParams);

        $ventes = $stmt->fetch();

        // Quantité totale disponible dans les lots
        $sqlStock = "
            SELECT COALESCE(SUM(quantite), 0) AS quantiteStock
            FROM Lot
            WHERE statut = 'DISPONIBLE'
              AND dateExpiration >= CURDATE()
        ";

        $quantiteStock = $this->db->query($sqlStock)->fetchColumn();

        // Commandes en attente
        [$commandeDateSql, $dateParams] = $this->dateFilter('dateCommande', $dateDebut, $dateFin);
        $sqlCommandes = "
            SELECT COUNT(*)
            FROM Commande
            WHERE statut = 'EN_ATTENTE'
              {$commandeDateSql}
        ";

        $stmt = $this->db->prepare($sqlCommandes);
        $stmt->execute($dateParams);

        $commandesEnAttente = $stmt->fetchColumn();

        // Produits sous leur seuil minimum
        $sqlStockFaible = "
            SELECT COUNT(*)
            FROM Produit p
            WHERE p.seuilMinimum >= (
                SELECT COALESCE(SUM(l.quantite), 0)
                FROM Lot l
                WHERE l.produit_id = p.id
                  AND l.statut = 'DISPONIBLE'
                  AND l.dateExpiration >= CURDATE()
            )
        ";

        $stockFaible = $this->db->query($sqlStockFaible)->fetchColumn();

        // Lots dont la date d'expiration approche dans les 30 jours
        $sqlExpirations = "
            SELECT COUNT(*)
            FROM Lot
            WHERE statut = 'DISPONIBLE'
              AND dateExpiration BETWEEN CURDATE()
                  AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";

        $expirationsProches = $this->db->query($sqlExpirations)->fetchColumn();

        // Lots déjà expirés
        $sqlExpires = "
            SELECT COUNT(*)
            FROM Lot
            WHERE dateExpiration < CURDATE()
        ";

        $lotsExpires = $this->db->query($sqlExpires)->fetchColumn();

        return [
            'chiffreAffaires' => (float) $ventes['chiffreAffaires'],
            'nombreVentes' => (int) $ventes['nombreVentes'],
            'quantiteStock' => (int) $quantiteStock,
            'commandesEnAttente' => (int) $commandesEnAttente,
            'stockFaible' => (int) $stockFaible,
            'expirationsProches' => (int) $expirationsProches,
            'lotsExpires' => (int) $lotsExpires
        ];
    }

    /**
     * Chiffre d'affaires mensuel pour le graphique.
     */
    public function getEvolutionVentes(?string $dateDebut = null, ?string $dateFin = null): array
    {
        [$dateSql, $params] = $this->dateFilter('dateVente', $dateDebut, $dateFin);
        $sql = "
            SELECT
                DATE_FORMAT(dateVente, '%Y-%m') AS mois,
                COALESCE(SUM(montantTotal), 0) AS chiffreAffaires
            FROM Vente
            WHERE 1 = 1 {$dateSql}
            GROUP BY DATE_FORMAT(dateVente, '%Y-%m')
            ORDER BY mois ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Nombre de commandes par statut.
     */
    public function getCommandesParStatut(
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): array {
        [$dateSql, $params] = $this->dateFilter('dateCommande', $dateDebut, $dateFin);
        $sql = "
            SELECT statut, COUNT(*) AS nombre
            FROM Commande
            WHERE 1 = 1 {$dateSql}
            GROUP BY statut
            ORDER BY statut ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getEvolutionMouvements(?string $dateDebut = null, ?string $dateFin = null): array
    {
        [$dateSql, $params] = $this->dateFilter('DATE(dateHeure)', $dateDebut, $dateFin);
        $stmt = $this->db->prepare("SELECT DATE_FORMAT(dateHeure, '%Y-%m') AS mois,
                SUM(CASE WHEN type = 'ENTREE' THEN quantite ELSE 0 END) AS entrees,
                SUM(CASE WHEN type = 'SORTIE' THEN quantite ELSE 0 END) AS sorties
            FROM MouvementStock
            WHERE 1 = 1 {$dateSql}
            GROUP BY DATE_FORMAT(dateHeure, '%Y-%m') ORDER BY mois ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDerniersMouvements(?string $dateDebut = null, ?string $dateFin = null): array
    {
        [$dateSql, $params] = $this->dateFilter('DATE(m.dateHeure)', $dateDebut, $dateFin);
        $stmt = $this->db->prepare("SELECT m.type, m.quantite, m.dateHeure, m.motif,
                p.nom AS produit, l.numeroLot, u.nom, u.prenom
            FROM MouvementStock m
            INNER JOIN Lot l ON l.id = m.lot_id
            INNER JOIN Produit p ON p.id = l.produit_id
            INNER JOIN Utilisateur u ON u.id = m.utilisateur_id
            WHERE 1 = 1 {$dateSql}
            ORDER BY m.dateHeure DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function dateFilter(string $column, ?string $dateDebut, ?string $dateFin): array
    {
        if ($dateDebut === null || $dateFin === null) {
            return ['', []];
        }

        return [" AND {$column} BETWEEN :dateDebut AND :dateFin", [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]];
    }
}
