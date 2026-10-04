<?php

class Medicament
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* =========================
       CATEGORIES
    ========================= */

    public function getCategories(): array
    {
        $sql = "
            SELECT id, nom
            FROM Categorie
            ORDER BY nom ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }


    /* =========================
       LISTE DES MEDICAMENTS
    ========================= */

    public function getAll(): array
    {
        $sql = "
            SELECT
                p.id,
                p.reference,
                p.nom,
                p.description,
                p.forme,
                p.dosage,
                p.prixAchat,
                p.prixVente,
                p.seuilMinimum,
                p.categorie_id,
                c.nom AS categorie_nom,

                COALESCE(
                    SUM(
                        CASE
                            WHEN l.statut = 'DISPONIBLE'
                            THEN l.quantite
                            ELSE 0
                        END
                    ),
                    0
                ) AS stock,

                MIN(
                    CASE
                        WHEN l.statut = 'DISPONIBLE'
                        AND l.quantite > 0
                        THEN l.dateExpiration
                    END
                ) AS dateExpiration

            FROM Produit p

            INNER JOIN Categorie c
                ON c.id = p.categorie_id

            LEFT JOIN Lot l
                ON l.produit_id = p.id

            GROUP BY
                p.id,
                p.reference,
                p.nom,
                p.description,
                p.forme,
                p.dosage,
                p.prixAchat,
                p.prixVente,
                p.seuilMinimum,
                p.categorie_id,
                c.nom

            ORDER BY p.nom ASC
        ";

        $products = $this->db->query($sql)->fetchAll();

        foreach ($products as &$product) {

            $product['stock'] = (int)$product['stock'];
            $product['seuilMinimum'] = (int)$product['seuilMinimum'];

            $product['statut'] = $this->calculateStatus(
                $product['stock'],
                $product['seuilMinimum'],
                $product['dateExpiration']
            );
        }

        return $products;
    }


    /* =========================
       DETAIL D'UN MEDICAMENT
    ========================= */

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                p.id,
                p.reference,
                p.nom,
                p.description,
                p.forme,
                p.dosage,
                p.prixAchat,
                p.prixVente,
                p.seuilMinimum,
                p.categorie_id,
                c.nom AS categorie_nom,

                COALESCE(
                    SUM(
                        CASE
                            WHEN l.statut = 'DISPONIBLE'
                            THEN l.quantite
                            ELSE 0
                        END
                    ),
                    0
                ) AS stock,

                MIN(
                    CASE
                        WHEN l.statut = 'DISPONIBLE'
                        AND l.quantite > 0
                        THEN l.dateExpiration
                    END
                ) AS dateExpiration

            FROM Produit p

            INNER JOIN Categorie c
                ON c.id = p.categorie_id

            LEFT JOIN Lot l
                ON l.produit_id = p.id

            WHERE p.id = :id

            GROUP BY
                p.id,
                p.reference,
                p.nom,
                p.description,
                p.forme,
                p.dosage,
                p.prixAchat,
                p.prixVente,
                p.seuilMinimum,
                p.categorie_id,
                c.nom
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $product['stock'] = (int)$product['stock'];
        $product['seuilMinimum'] = (int)$product['seuilMinimum'];

        $product['statut'] = $this->calculateStatus(
            $product['stock'],
            $product['seuilMinimum'],
            $product['dateExpiration']
        );

        return $product;
    }


    /* =========================
       LOTS
    ========================= */

    public function getLots(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                numeroLot,
                quantite,
                dateReception,
                dateExpiration,
                statut
            FROM Lot
            WHERE produit_id = :produit_id
            ORDER BY dateExpiration ASC
        ");

        $stmt->execute([
            'produit_id' => $productId
        ]);

        return $stmt->fetchAll();
    }


    /* =========================
       HISTORIQUE DU STOCK
    ========================= */

    public function getHistory(int $productId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                m.id,
                m.type,
                m.quantite,
                m.dateHeure,
                m.motif,
                u.nom,
                u.prenom

            FROM MouvementStock m

            INNER JOIN Lot l
                ON l.id = m.lot_id

            INNER JOIN Utilisateur u
                ON u.id = m.utilisateur_id

            WHERE l.produit_id = :produit_id

            ORDER BY m.dateHeure DESC

            LIMIT 100
        ");

        $stmt->execute([
            'produit_id' => $productId
        ]);

        return $stmt->fetchAll();
    }


    /* =========================
       LOTS / MOUVEMENTS
    ========================= */

    public function getLotById(int $lotId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                produit_id,
                numeroLot,
                quantite,
                dateReception,
                dateExpiration,
                statut
            FROM Lot
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $lotId
        ]);

        $lot = $stmt->fetch();

        if (!$lot) {
            return null;
        }

        $lot['quantite'] = (int)$lot['quantite'];

        return $lot;
    }


    public function createLot(int $productId, array $data): int
    {
        $this->validateLot($data);

        $sql = "
            INSERT INTO Lot
            (
                produit_id,
                numeroLot,
                quantite,
                dateReception,
                dateExpiration,
                statut
            )
            VALUES
            (
                :produit_id,
                :numeroLot,
                :quantite,
                :dateReception,
                :dateExpiration,
                :statut
            )
        ";

        $quantity = (int)$data['quantite'];
        $dateReception = $data['dateReception'] ?? date('Y-m-d');
        $dateExpiration = $data['dateExpiration'] ?? null;
        $status = $this->calculateLotStatus($quantity, $dateExpiration);

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'produit_id' => $productId,
            'numeroLot' => trim((string)$data['numeroLot']),
            'quantite' => $quantity,
            'dateReception' => $dateReception,
            'dateExpiration' => $dateExpiration,
            'statut' => $status
        ]);

        return (int)$this->db->lastInsertId();
    }


    public function updateLot(int $lotId, array $data): bool
    {
        $this->validateLot($data, false);

        $lot = $this->getLotById($lotId);

        if (!$lot) {
            throw new InvalidArgumentException('Lot introuvable.');
        }

        $dateReception = $data['dateReception'] ?? $lot['dateReception'];
        $dateExpiration = $data['dateExpiration'] ?? $lot['dateExpiration'];
        $quantity = isset($data['quantite']) ? (int)$data['quantite'] : (int)$lot['quantite'];
        $status = $this->calculateLotStatus($quantity, $dateExpiration);

        $sql = "
            UPDATE Lot
            SET
                numeroLot = :numeroLot,
                quantite = :quantite,
                dateReception = :dateReception,
                dateExpiration = :dateExpiration,
                statut = :statut
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $lotId,
            'numeroLot' => trim((string)$data['numeroLot']),
            'quantite' => $quantity,
            'dateReception' => $dateReception,
            'dateExpiration' => $dateExpiration,
            'statut' => $status
        ]);
    }


    public function applyStockMovement(int $lotId, string $type, int $quantite, ?string $motif, int $utilisateurId): array
    {
        $type = strtoupper(trim($type));
        $lot = $this->getLotById($lotId);

        if (!$lot) {
            throw new InvalidArgumentException('Lot introuvable.');
        }

        $allowed = ['ENTREE', 'SORTIE', 'PERTE', 'RETOUR', 'AJUSTEMENT'];

        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Type de mouvement invalide.');
        }

        $quantite = (int)$quantite;

        if ($quantite <= 0) {
            throw new InvalidArgumentException('La quantité du mouvement doit être supérieure à zéro.');
        }

        $newQuantity = $lot['quantite'];

        if ($type === 'ENTREE' || $type === 'RETOUR') {
            $newQuantity = $lot['quantite'] + $quantite;
        } elseif ($type === 'SORTIE' || $type === 'PERTE') {
            $newQuantity = $lot['quantite'] - $quantite;
        } elseif ($type === 'AJUSTEMENT') {
            $newQuantity = $quantite;
        }

        $newQuantity = max(0, $newQuantity);

        $stmt = $this->db->prepare("
            INSERT INTO MouvementStock
            (
                type,
                quantite,
                dateHeure,
                motif,
                lot_id,
                utilisateur_id
            )
            VALUES
            (
                :type,
                :quantite,
                NOW(),
                :motif,
                :lot_id,
                :utilisateur_id
            )
        ");

        $stmt->execute([
            'type' => $type,
            'quantite' => $quantite,
            'motif' => $motif ?: 'Mouvement manuel',
            'lot_id' => $lotId,
            'utilisateur_id' => $utilisateurId
        ]);

        $this->db->prepare("
            UPDATE Lot
            SET quantite = :quantite,
                statut = :statut
            WHERE id = :id
        ")->execute([
            'quantite' => $newQuantity,
            'statut' => $this->calculateLotStatus($newQuantity, $lot['dateExpiration']),
            'id' => $lotId
        ]);

        return [
            'lot_id' => $lotId,
            'type' => $type,
            'quantite' => $quantite,
            'nouvelle_quantite' => $newQuantity,
            'statut' => $this->calculateLotStatus($newQuantity, $lot['dateExpiration'])
        ];
    }


    /* =========================
       CREATION
    ========================= */

    public function create(array $data): int
    {
        $this->validate($data);

        $sql = "
            INSERT INTO Produit
            (
                reference,
                nom,
                description,
                forme,
                dosage,
                prixAchat,
                prixVente,
                seuilMinimum,
                categorie_id
            )
            VALUES
            (
                :reference,
                :nom,
                :description,
                :forme,
                :dosage,
                :prixAchat,
                :prixVente,
                :seuilMinimum,
                :categorie_id
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'reference' => $data['reference'],
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'forme' => $data['forme'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'prixAchat' => $data['prixAchat'],
            'prixVente' => $data['prixVente'],
            'seuilMinimum' => $data['seuilMinimum'],
            'categorie_id' => $data['categorie_id']
        ]);

        return (int)$this->db->lastInsertId();
    }


    /* =========================
       MODIFICATION
    ========================= */

    public function update(int $id, array $data): bool
    {
        $this->validate($data);

        $sql = "
            UPDATE Produit
            SET
                reference = :reference,
                nom = :nom,
                description = :description,
                forme = :forme,
                dosage = :dosage,
                prixAchat = :prixAchat,
                prixVente = :prixVente,
                seuilMinimum = :seuilMinimum,
                categorie_id = :categorie_id

            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'reference' => $data['reference'],
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'forme' => $data['forme'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'prixAchat' => $data['prixAchat'],
            'prixVente' => $data['prixVente'],
            'seuilMinimum' => $data['seuilMinimum'],
            'categorie_id' => $data['categorie_id']
        ]);
    }


    /* =========================
       SUPPRESSION
    ========================= */

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM Produit
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }


    /* =========================
       STATUT
    ========================= */

    private function calculateStatus(
        int $stock,
        int $seuil,
        ?string $expiration
    ): string {

        if ($expiration && $expiration < date('Y-m-d')) {
            return 'EXPIRE';
        }

        if ($stock === 0) {
            return 'RUPTURE';
        }

        if ($stock <= $seuil) {
            return 'FAIBLE';
        }

        return 'NORMAL';
    }


    private function calculateLotStatus(
        int $quantity,
        ?string $expiration
    ): string {

        if ($expiration && $expiration < date('Y-m-d')) {
            return 'PERIME';
        }

        if ($quantity <= 0) {
            return 'EPUISE';
        }

        return 'DISPONIBLE';
    }


    /* =========================
       VALIDATION
    ========================= */

    private function validate(array $data): void
    {
        if (empty($data['reference'])) {
            throw new InvalidArgumentException(
                "La référence est obligatoire."
            );
        }

        if (empty($data['nom'])) {
            throw new InvalidArgumentException(
                "Le nom est obligatoire."
            );
        }

        if (
            !isset($data['categorie_id']) ||
            (int)$data['categorie_id'] <= 0
        ) {
            throw new InvalidArgumentException(
                "La catégorie est obligatoire."
            );
        }

        if (
            !isset($data['prixAchat']) ||
            !is_numeric($data['prixAchat']) ||
            $data['prixAchat'] < 0
        ) {
            throw new InvalidArgumentException(
                "Le prix d'achat est invalide."
            );
        }

        if (
            !isset($data['prixVente']) ||
            !is_numeric($data['prixVente']) ||
            $data['prixVente'] < 0
        ) {
            throw new InvalidArgumentException(
                "Le prix de vente est invalide."
            );
        }

        if (
            !isset($data['seuilMinimum']) ||
            !is_numeric($data['seuilMinimum']) ||
            $data['seuilMinimum'] < 0
        ) {
            throw new InvalidArgumentException(
                "Le seuil minimum est invalide."
            );
        }
    }


    private function validateLot(array $data, bool $requireQuantity = true): void
    {
        if (empty($data['numeroLot'])) {
            throw new InvalidArgumentException('Le numéro de lot est obligatoire.');
        }

        if (!isset($data['dateReception']) || empty($data['dateReception'])) {
            throw new InvalidArgumentException('La date de réception est obligatoire.');
        }

        if (!isset($data['dateExpiration']) || empty($data['dateExpiration'])) {
            throw new InvalidArgumentException('La date d\'expiration est obligatoire.');
        }

        if ($requireQuantity || array_key_exists('quantite', $data)) {
            if (!isset($data['quantite']) || !is_numeric($data['quantite']) || (int)$data['quantite'] < 0) {
                throw new InvalidArgumentException('La quantité du lot est invalide.');
            }
        }
    }
}