<?php

class Employee
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getRecentSales(int $userId, int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));
        $sql = "SELECT id, dateVente, montantTotal
                FROM Vente
                WHERE utilisateur_id = :user_id
                ORDER BY id DESC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuickInfo(): array
    {
        $low = $this->db->query("SELECT COUNT(*) FROM (
            SELECT p.id, p.seuilMinimum,
                   COALESCE(SUM(CASE WHEN l.statut = 'DISPONIBLE' AND l.dateExpiration >= CURDATE() THEN l.quantite ELSE 0 END), 0) AS stock
            FROM Produit p LEFT JOIN Lot l ON l.produit_id = p.id
            GROUP BY p.id, p.seuilMinimum
            HAVING stock > 0 AND stock <= p.seuilMinimum
        ) x")->fetchColumn();

        $out = $this->db->query("SELECT COUNT(*) FROM (
            SELECT p.id, p.seuilMinimum,
                   COALESCE(SUM(CASE WHEN l.statut = 'DISPONIBLE' AND l.dateExpiration >= CURDATE() THEN l.quantite ELSE 0 END), 0) AS stock
            FROM Produit p LEFT JOIN Lot l ON l.produit_id = p.id
            GROUP BY p.id, p.seuilMinimum
            HAVING stock = 0
        ) x")->fetchColumn();

        $expiring = $this->db->query("SELECT COUNT(*) FROM Lot
            WHERE statut = 'DISPONIBLE' AND dateExpiration >= CURDATE()
            AND dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

        return [
            'low_stock' => (int) $low,
            'out_of_stock' => (int) $out,
            'expiring_soon' => (int) $expiring,
        ];
    }

    public function getNotifications(): array
    {
        $rows = [];
        $stmt = $this->db->query("SELECT p.nom, COALESCE(SUM(CASE WHEN l.statut = 'DISPONIBLE' AND l.dateExpiration >= CURDATE() THEN l.quantite ELSE 0 END), 0) AS stock, p.seuilMinimum
            FROM Produit p LEFT JOIN Lot l ON l.produit_id = p.id
            GROUP BY p.id, p.nom, p.seuilMinimum
            HAVING stock <= p.seuilMinimum
            ORDER BY stock ASC, p.nom ASC LIMIT 10");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'type' => ((int) $row['stock'] === 0) ? 'rupture' : 'stock_faible',
                'message' => ((int) $row['stock'] === 0 ? 'Rupture de stock : ' : 'Stock faible : ') . $row['nom'],
            ];
        }
        $stmt = $this->db->query("SELECT p.nom, l.dateExpiration FROM Lot l JOIN Produit p ON p.id = l.produit_id
            WHERE l.statut = 'DISPONIBLE' AND l.dateExpiration >= CURDATE()
            AND l.dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY l.dateExpiration ASC LIMIT 10");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'type' => 'expiration',
                'message' => 'Expiration proche : ' . $row['nom'] . ' (' . $row['dateExpiration'] . ')',
            ];
        }
        return $rows;
    }
}
