<?php

/*
|--------------------------------------------------------------------------
| Alertes de stock
|--------------------------------------------------------------------------
|
| Les alertes sont calculées à partir de l'état réel du stock :
|
|   RUPTURE            : produit sans stock vendable
|   STOCK_FAIBLE       : stock vendable <= seuil minimum
|   EXPIRATION_PROCHE  : lot disponible qui expire dans les 30 jours
|   EXPIRE             : lot périmé qui contient encore des unités
|
| Une alerte ouverte (NOUVELLE ou IGNOREE) n'est pas recréée.
| Quand le problème disparaît, ses alertes ouvertes passent à TRAITEE.
*/

class Alerte
{
    private PDO $db;

    public const JOURS_EXPIRATION = 30;

    public const TYPES = [
        'STOCK_FAIBLE',
        'RUPTURE',
        'EXPIRATION_PROCHE',
        'EXPIRE'
    ];

    public const STATUTS = [
        'NOUVELLE',
        'TRAITEE',
        'IGNOREE'
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* =====================================================
       SYNCHRONISATION AVEC L'ÉTAT DU STOCK
    ====================================================== */

    public function synchroniser(): void
    {
        $problemes = array_merge(
            $this->detecterProblemesProduits(),
            $this->detecterProblemesLots()
        );

        $ouvertes = $this->db->query("
            SELECT id, type, produit_id, lot_id
            FROM Alerte
            WHERE statut IN ('NOUVELLE', 'IGNOREE')
        ")->fetchAll();

        $clesOuvertes = [];

        foreach ($ouvertes as $alerte) {
            $clesOuvertes[$this->cle($alerte['type'], $alerte['produit_id'], $alerte['lot_id'])][] =
                (int)$alerte['id'];
        }

        $this->db->beginTransaction();

        try {

            $insert = $this->db->prepare("
                INSERT INTO Alerte
                (
                    type,
                    message,
                    dateCreation,
                    statut,
                    produit_id,
                    lot_id
                )
                VALUES
                (
                    :type,
                    :message,
                    NOW(),
                    'NOUVELLE',
                    :produit_id,
                    :lot_id
                )
            ");

            $clesActuelles = [];

            foreach ($problemes as $probleme) {

                $cle = $this->cle($probleme['type'], $probleme['produit_id'], $probleme['lot_id']);

                $clesActuelles[$cle] = true;

                if (isset($clesOuvertes[$cle])) {
                    continue;
                }

                $insert->execute($probleme);
            }

            /*
             * Problèmes résolus : on clôture les alertes ouvertes.
             */
            $idsResolus = [];

            foreach ($clesOuvertes as $cle => $ids) {
                if (!isset($clesActuelles[$cle])) {
                    array_push($idsResolus, ...$ids);
                }
            }

            if ($idsResolus) {

                $placeholders = implode(',', array_fill(0, count($idsResolus), '?'));

                $this->db->prepare("
                    UPDATE Alerte
                    SET statut = 'TRAITEE'
                    WHERE id IN ({$placeholders})
                ")->execute($idsResolus);
            }

            $this->db->commit();

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /* =====================================================
       LISTE
    ====================================================== */

    public function getAll(?string $statut, ?string $type): array
    {
        $conditions = [];
        $params = [];

        if ($statut !== null && in_array($statut, self::STATUTS, true)) {
            $conditions[] = 'a.statut = :statut';
            $params['statut'] = $statut;
        }

        if ($type !== null && in_array($type, self::TYPES, true)) {
            $conditions[] = 'a.type = :type';
            $params['type'] = $type;
        }

        $where = $conditions
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        $stmt = $this->db->prepare("
            SELECT
                a.id,
                a.type,
                a.message,
                a.dateCreation,
                a.statut,

                COALESCE(p.id, pl.id) AS produit_id,
                COALESCE(p.nom, pl.nom) AS produit,
                COALESCE(p.reference, pl.reference) AS reference,

                l.id AS lot_id,
                l.numeroLot,
                l.quantite AS quantiteLot,
                l.dateExpiration

            FROM Alerte a

            LEFT JOIN Produit p
                ON p.id = a.produit_id

            LEFT JOIN Lot l
                ON l.id = a.lot_id

            LEFT JOIN Produit pl
                ON pl.id = l.produit_id

            {$where}

            ORDER BY
                FIELD(a.statut, 'NOUVELLE', 'IGNOREE', 'TRAITEE'),
                FIELD(a.type, 'RUPTURE', 'EXPIRE', 'STOCK_FAIBLE', 'EXPIRATION_PROCHE'),
                a.dateCreation DESC,
                a.id DESC

            LIMIT 500
        ");

        $stmt->execute($params);

        return $stmt->fetchAll();
    }


    public function compterNouvelles(): array
    {
        $rows = $this->db->query("
            SELECT type, COUNT(*) AS total
            FROM Alerte
            WHERE statut = 'NOUVELLE'
            GROUP BY type
        ")->fetchAll();

        $compteurs = array_fill_keys(self::TYPES, 0);

        foreach ($rows as $row) {
            $compteurs[$row['type']] = (int)$row['total'];
        }

        $compteurs['total'] = array_sum($compteurs);

        return $compteurs;
    }


    /* =====================================================
       CHANGEMENT DE STATUT
    ====================================================== */

    public function changerStatut(int $id, string $statut, int $utilisateurId): void
    {
        if (!in_array($statut, self::STATUTS, true)) {
            throw new InvalidArgumentException('Statut d\'alerte invalide.');
        }

        $stmt = $this->db->prepare("
            SELECT id, type, message
            FROM Alerte
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id
        ]);

        $alerte = $stmt->fetch();

        if (!$alerte) {
            throw new InvalidArgumentException('Alerte introuvable.');
        }

        $this->db->prepare("
            UPDATE Alerte
            SET statut = :statut
            WHERE id = :id
        ")->execute([
            'statut' => $statut,
            'id' => $id
        ]);

        $this->db->prepare("
            INSERT INTO JournalActivite
            (
                action,
                description,
                dateHeure,
                utilisateur_id
            )
            VALUES
            (
                'ALERTE',
                :description,
                NOW(),
                :utilisateur_id
            )
        ")->execute([
            'description' => "Alerte #{$id} marquée {$statut} : {$alerte['message']}",
            'utilisateur_id' => $utilisateurId
        ]);
    }


    /* =====================================================
       DÉTECTION
    ====================================================== */

    private function detecterProblemesProduits(): array
    {
        $produits = $this->db->query("
            SELECT
                p.id,
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
                p.nom,
                p.seuilMinimum
        ")->fetchAll();

        $problemes = [];

        foreach ($produits as $produit) {

            $stock = (int)$produit['stock'];
            $seuil = (int)$produit['seuilMinimum'];

            if ($stock === 0) {

                $problemes[] = [
                    'type' => 'RUPTURE',
                    'message' => "Rupture de stock : {$produit['nom']}.",
                    'produit_id' => (int)$produit['id'],
                    'lot_id' => null
                ];

            } elseif ($stock <= $seuil) {

                $problemes[] = [
                    'type' => 'STOCK_FAIBLE',
                    'message' => "Stock faible : {$produit['nom']} ({$stock} unités, seuil {$seuil}).",
                    'produit_id' => (int)$produit['id'],
                    'lot_id' => null
                ];
            }
        }

        return $problemes;
    }


    private function detecterProblemesLots(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id,
                l.numeroLot,
                l.quantite,
                l.dateExpiration,
                p.nom

            FROM Lot l

            INNER JOIN Produit p
                ON p.id = l.produit_id

            WHERE l.quantite > 0
              AND l.statut <> 'EPUISE'
              AND l.dateExpiration <= DATE_ADD(CURDATE(), INTERVAL :jours DAY)
        ");

        $stmt->execute([
            'jours' => self::JOURS_EXPIRATION
        ]);

        $problemes = [];
        $aujourdhui = date('Y-m-d');

        foreach ($stmt->fetchAll() as $lot) {

            $date = date('d/m/Y', strtotime($lot['dateExpiration']));

            if ($lot['dateExpiration'] < $aujourdhui) {

                $problemes[] = [
                    'type' => 'EXPIRE',
                    'message' => "Lot {$lot['numeroLot']} de {$lot['nom']} périmé depuis le {$date} ({$lot['quantite']} unités à retirer).",
                    'produit_id' => null,
                    'lot_id' => (int)$lot['id']
                ];

            } else {

                $problemes[] = [
                    'type' => 'EXPIRATION_PROCHE',
                    'message' => "Lot {$lot['numeroLot']} de {$lot['nom']} expire le {$date}.",
                    'produit_id' => null,
                    'lot_id' => (int)$lot['id']
                ];
            }
        }

        return $problemes;
    }


    private function cle(string $type, $produitId, $lotId): string
    {
        return $type . '|' . (int)$produitId . '|' . (int)$lotId;
    }
}
