<?php

class Stock
{
    private PDO $db;

    public const TYPES_MOUVEMENT = [
        'ENTREE',
        'SORTIE',
        'PERTE',
        'RETOUR',
        'AJUSTEMENT'
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* =====================================================
       MISE À JOUR DES STATUTS DES LOTS
    ====================================================== */

    /*
     * Un lot dont la date d'expiration est dépassée
     * passe automatiquement au statut PERIME.
     */
    public function synchroniserStatutsLots(): void
    {
        $this->db->exec("
            UPDATE Lot
            SET statut = 'PERIME'
            WHERE dateExpiration < CURDATE()
              AND statut <> 'PERIME'
        ");

        $this->db->exec("
            UPDATE Lot
            SET statut = 'EPUISE'
            WHERE dateExpiration >= CURDATE()
              AND quantite = 0
              AND statut = 'DISPONIBLE'
        ");
    }


    /* =====================================================
       RÉSUMÉ
    ====================================================== */

    public function getResume(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS totalLots,

                COALESCE(SUM(
                    CASE WHEN statut = 'DISPONIBLE' THEN quantite ELSE 0 END
                ), 0) AS unitesDisponibles,

                COALESCE(SUM(
                    CASE WHEN statut = 'DISPONIBLE' THEN quantite * p.prixAchat ELSE 0 END
                ), 0) AS valeurStock,

                SUM(
                    CASE
                        WHEN statut = 'DISPONIBLE'
                         AND dateExpiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        THEN 1 ELSE 0
                    END
                ) AS lotsExpirationProche,

                SUM(
                    CASE WHEN statut = 'PERIME' AND quantite > 0 THEN 1 ELSE 0 END
                ) AS lotsPerimes

            FROM Lot l

            INNER JOIN Produit p
                ON p.id = l.produit_id
        ")->fetch();

        return [
            'totalLots' => (int)$row['totalLots'],
            'unitesDisponibles' => (int)$row['unitesDisponibles'],
            'valeurStock' => (float)$row['valeurStock'],
            'lotsExpirationProche' => (int)$row['lotsExpirationProche'],
            'lotsPerimes' => (int)$row['lotsPerimes']
        ];
    }


    /* =====================================================
       LISTE DES LOTS
    ====================================================== */

    public function getLots(): array
    {
        $lots = $this->db->query("
            SELECT
                l.id,
                l.numeroLot,
                l.quantite,
                l.dateReception,
                l.dateExpiration,
                l.statut,
                DATEDIFF(l.dateExpiration, CURDATE()) AS joursRestants,

                p.id AS produit_id,
                p.reference,
                p.nom AS produit,
                p.forme,
                p.dosage,

                c.nom AS categorie

            FROM Lot l

            INNER JOIN Produit p
                ON p.id = l.produit_id

            INNER JOIN Categorie c
                ON c.id = p.categorie_id

            ORDER BY
                FIELD(l.statut, 'PERIME', 'DISPONIBLE', 'EPUISE'),
                l.dateExpiration ASC,
                p.nom ASC
        ")->fetchAll();

        foreach ($lots as &$lot) {
            $lot['quantite'] = (int)$lot['quantite'];
            $lot['joursRestants'] = (int)$lot['joursRestants'];
        }

        return $lots;
    }


    /* =====================================================
       HISTORIQUE DES MOUVEMENTS
    ====================================================== */

    public function getMouvements(
        ?string $type,
        ?string $dateDebut,
        ?string $dateFin
    ): array {

        $conditions = [];
        $params = [];

        if ($type !== null && in_array($type, self::TYPES_MOUVEMENT, true)) {
            $conditions[] = 'm.type = :type';
            $params['type'] = $type;
        }

        if ($dateDebut !== null && $this->isDate($dateDebut)) {
            $conditions[] = 'm.dateHeure >= :dateDebut';
            $params['dateDebut'] = $dateDebut . ' 00:00:00';
        }

        if ($dateFin !== null && $this->isDate($dateFin)) {
            $conditions[] = 'm.dateHeure <= :dateFin';
            $params['dateFin'] = $dateFin . ' 23:59:59';
        }

        $where = $conditions
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        $stmt = $this->db->prepare("
            SELECT
                m.id,
                m.type,
                m.quantite,
                m.dateHeure,
                m.motif,

                l.id AS lot_id,
                l.numeroLot,

                p.id AS produit_id,
                p.nom AS produit,

                u.nom,
                u.prenom

            FROM MouvementStock m

            INNER JOIN Lot l
                ON l.id = m.lot_id

            INNER JOIN Produit p
                ON p.id = l.produit_id

            INNER JOIN Utilisateur u
                ON u.id = m.utilisateur_id

            {$where}

            ORDER BY
                m.dateHeure DESC,
                m.id DESC

            LIMIT 300
        ");

        $stmt->execute($params);

        return $stmt->fetchAll();
    }


    /* =====================================================
       PRODUITS (POUR LES FORMULAIRES)
    ====================================================== */

    public function getProduits(): array
    {
        return $this->db->query("
            SELECT
                id,
                reference,
                nom,
                dosage,
                forme
            FROM Produit
            ORDER BY nom ASC
        ")->fetchAll();
    }


    /* =====================================================
       CRÉATION D'UN LOT (ENTRÉE EN STOCK)
    ====================================================== */

    public function creerLot(array $data, int $utilisateurId): int
    {
        $produitId = (int)($data['produit_id'] ?? 0);
        $numeroLot = trim((string)($data['numeroLot'] ?? ''));
        $quantite = filter_var($data['quantite'] ?? null, FILTER_VALIDATE_INT);
        $dateReception = $data['dateReception'] ?? date('Y-m-d');
        $dateExpiration = $data['dateExpiration'] ?? '';

        if ($produitId <= 0 || !$this->produitExiste($produitId)) {
            throw new InvalidArgumentException('Médicament invalide.');
        }

        $this->validerLot($numeroLot, $quantite, $dateReception, $dateExpiration);

        $this->db->beginTransaction();

        try {

            $lotId = $this->insererLot(
                $produitId,
                $numeroLot,
                (int)$quantite,
                $dateReception,
                $dateExpiration
            );

            $this->enregistrerMouvement(
                $lotId,
                'ENTREE',
                (int)$quantite,
                trim((string)($data['motif'] ?? '')) ?: 'Réception du lot ' . $numeroLot,
                $utilisateurId
            );

            $this->journaliser(
                'LOT_AJOUTE',
                "Lot {$numeroLot} ajouté ({$quantite} unités).",
                $utilisateurId
            );

            $this->db->commit();

            return $lotId;

        } catch (Throwable $e) {

            $this->rollBack();

            throw $e;
        }
    }


    /* =====================================================
       MOUVEMENT DE STOCK SUR UN LOT
    ====================================================== */

    public function enregistrerMouvementLot(
        int $lotId,
        string $type,
        int $quantite,
        ?string $motif,
        int $utilisateurId
    ): array {

        $type = strtoupper(trim($type));

        if (!in_array($type, self::TYPES_MOUVEMENT, true)) {
            throw new InvalidArgumentException('Type de mouvement invalide.');
        }

        if ($quantite < 0 || ($quantite === 0 && $type !== 'AJUSTEMENT')) {
            throw new InvalidArgumentException('La quantité doit être supérieure à zéro.');
        }

        $this->db->beginTransaction();

        try {

            $lot = $this->getLotPourMiseAJour($lotId);

            $ancienneQuantite = (int)$lot['quantite'];

            switch ($type) {

                case 'ENTREE':
                case 'RETOUR':
                    $nouvelleQuantite = $ancienneQuantite + $quantite;
                    $quantiteMouvement = $quantite;
                    break;

                case 'SORTIE':
                case 'PERTE':
                    if ($quantite > $ancienneQuantite) {
                        throw new RuntimeException(
                            "Quantité insuffisante dans le lot {$lot['numeroLot']} ({$ancienneQuantite} disponibles)."
                        );
                    }

                    $nouvelleQuantite = $ancienneQuantite - $quantite;
                    $quantiteMouvement = $quantite;
                    break;

                default:
                    /*
                     * AJUSTEMENT : la quantité saisie est la
                     * nouvelle quantité réelle (inventaire).
                     * Le mouvement enregistre l'écart.
                     */
                    $nouvelleQuantite = $quantite;
                    $quantiteMouvement = abs($nouvelleQuantite - $ancienneQuantite);

                    if ($quantiteMouvement === 0) {
                        throw new InvalidArgumentException(
                            'La quantité saisie est identique au stock actuel.'
                        );
                    }

                    $ecart = "de {$ancienneQuantite} à {$nouvelleQuantite}";
                    $motif = trim((string)$motif) !== ''
                        ? trim((string)$motif) . " ({$ecart})"
                        : "Inventaire : {$ecart}";
            }

            $this->enregistrerMouvement(
                $lotId,
                $type,
                $quantiteMouvement,
                trim((string)$motif) ?: $this->motifParDefaut($type),
                $utilisateurId
            );

            $statut = $this->mettreAJourQuantiteLot(
                $lotId,
                $nouvelleQuantite,
                $lot['dateExpiration']
            );

            $this->journaliser(
                'MOUVEMENT_' . $type,
                "{$this->libelleType($type)} sur le lot {$lot['numeroLot']} ({$lot['produit']}) : "
                    . "{$ancienneQuantite} → {$nouvelleQuantite}.",
                $utilisateurId
            );

            $this->db->commit();

            return [
                'lot_id' => $lotId,
                'type' => $type,
                'ancienneQuantite' => $ancienneQuantite,
                'nouvelleQuantite' => $nouvelleQuantite,
                'statut' => $statut
            ];

        } catch (Throwable $e) {

            $this->rollBack();

            throw $e;
        }
    }


    /* =====================================================
       RETRAIT D'UN LOT PÉRIMÉ
    ====================================================== */

    public function retirerLot(int $lotId, int $utilisateurId): array
    {
        $lot = $this->getLot($lotId);

        if ((int)$lot['quantite'] === 0) {
            throw new RuntimeException('Ce lot est déjà vide.');
        }

        return $this->enregistrerMouvementLot(
            $lotId,
            'PERTE',
            (int)$lot['quantite'],
            $lot['dateExpiration'] < date('Y-m-d')
                ? 'Retrait du lot périmé'
                : 'Retrait du lot',
            $utilisateurId
        );
    }


    /* =====================================================
       RÉCEPTION DES COMMANDES
    ====================================================== */

    public function getCommandesAReceptionner(): array
    {
        $commandes = $this->db->query("
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
        ")->fetchAll();

        $stmt = $this->db->prepare("
            SELECT
                d.id,
                d.quantite,
                d.prixUnitaire,
                p.id AS produit_id,
                p.nom AS produit,
                p.reference

            FROM DetailCommande d

            INNER JOIN Produit p
                ON p.id = d.produit_id

            WHERE d.commande_id = :commande_id

            ORDER BY d.id ASC
        ");

        foreach ($commandes as &$commande) {

            $stmt->execute([
                'commande_id' => $commande['id']
            ]);

            $commande['details'] = $stmt->fetchAll();
        }

        return $commandes;
    }


    /*
     * $lignes : [
     *   { detail_id, numeroLot, dateExpiration, quantite }
     * ]
     *
     * Chaque ligne de la commande devient un lot.
     */
    public function receptionnerCommande(
        int $commandeId,
        array $lignes,
        int $utilisateurId
    ): array {

        $this->db->beginTransaction();

        try {

            $stmt = $this->db->prepare("
                SELECT id, statut
                FROM Commande
                WHERE id = :id
                FOR UPDATE
            ");

            $stmt->execute([
                'id' => $commandeId
            ]);

            $commande = $stmt->fetch();

            if (!$commande) {
                throw new InvalidArgumentException('Commande introuvable.');
            }

            if (!in_array($commande['statut'], ['EN_ATTENTE', 'VALIDEE'], true)) {
                throw new RuntimeException(
                    'Seule une commande en attente ou validée peut être réceptionnée.'
                );
            }

            $stmt = $this->db->prepare("
                SELECT
                    d.id,
                    d.quantite,
                    d.produit_id,
                    p.nom AS produit
                FROM DetailCommande d
                INNER JOIN Produit p
                    ON p.id = d.produit_id
                WHERE d.commande_id = :commande_id
            ");

            $stmt->execute([
                'commande_id' => $commandeId
            ]);

            $details = [];

            foreach ($stmt->fetchAll() as $detail) {
                $details[(int)$detail['id']] = $detail;
            }

            if (!$details) {
                throw new RuntimeException('Cette commande ne contient aucun produit.');
            }

            $saisies = [];

            foreach ($lignes as $ligne) {
                $saisies[(int)($ligne['detail_id'] ?? 0)] = $ligne;
            }

            $lotsCrees = [];
            $dateReception = date('Y-m-d');

            foreach ($details as $detailId => $detail) {

                if (!isset($saisies[$detailId])) {
                    throw new InvalidArgumentException(
                        "Informations du lot manquantes pour : {$detail['produit']}."
                    );
                }

                $ligne = $saisies[$detailId];

                $numeroLot = trim((string)($ligne['numeroLot'] ?? ''));
                $dateExpiration = (string)($ligne['dateExpiration'] ?? '');
                $quantite = filter_var(
                    $ligne['quantite'] ?? $detail['quantite'],
                    FILTER_VALIDATE_INT
                );

                $this->validerLot($numeroLot, $quantite, $dateReception, $dateExpiration, $detail['produit']);

                $lotId = $this->insererLot(
                    (int)$detail['produit_id'],
                    $numeroLot,
                    (int)$quantite,
                    $dateReception,
                    $dateExpiration
                );

                $this->enregistrerMouvement(
                    $lotId,
                    'ENTREE',
                    (int)$quantite,
                    "Réception commande #{$commandeId}",
                    $utilisateurId
                );

                $lotsCrees[] = $lotId;
            }

            $this->db->prepare("
                UPDATE Commande
                SET statut = 'RECUE'
                WHERE id = :id
            ")->execute([
                'id' => $commandeId
            ]);

            $this->journaliser(
                'COMMANDE_RECUE',
                "Commande #{$commandeId} réceptionnée : " . count($lotsCrees) . ' lot(s) ajouté(s).',
                $utilisateurId
            );

            $this->db->commit();

            return [
                'commande_id' => $commandeId,
                'lots' => $lotsCrees
            ];

        } catch (Throwable $e) {

            $this->rollBack();

            throw $e;
        }
    }


    /* =====================================================
       OUTILS INTERNES
    ====================================================== */

    private function getLot(int $lotId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id,
                l.numeroLot,
                l.quantite,
                l.dateExpiration,
                l.statut,
                p.nom AS produit
            FROM Lot l
            INNER JOIN Produit p
                ON p.id = l.produit_id
            WHERE l.id = :id
        ");

        $stmt->execute([
            'id' => $lotId
        ]);

        $lot = $stmt->fetch();

        if (!$lot) {
            throw new InvalidArgumentException('Lot introuvable.');
        }

        return $lot;
    }


    private function getLotPourMiseAJour(int $lotId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id,
                l.numeroLot,
                l.quantite,
                l.dateExpiration,
                p.nom AS produit
            FROM Lot l
            INNER JOIN Produit p
                ON p.id = l.produit_id
            WHERE l.id = :id
            FOR UPDATE
        ");

        $stmt->execute([
            'id' => $lotId
        ]);

        $lot = $stmt->fetch();

        if (!$lot) {
            throw new InvalidArgumentException('Lot introuvable.');
        }

        return $lot;
    }


    private function insererLot(
        int $produitId,
        string $numeroLot,
        int $quantite,
        string $dateReception,
        string $dateExpiration
    ): int {

        $stmt = $this->db->prepare("
            SELECT id
            FROM Lot
            WHERE produit_id = :produit_id
              AND numeroLot = :numeroLot
        ");

        $stmt->execute([
            'produit_id' => $produitId,
            'numeroLot' => $numeroLot
        ]);

        if ($stmt->fetch()) {
            throw new RuntimeException(
                "Le lot {$numeroLot} existe déjà pour ce médicament."
            );
        }

        $this->db->prepare("
            INSERT INTO Lot
            (
                numeroLot,
                quantite,
                dateReception,
                dateExpiration,
                statut,
                produit_id
            )
            VALUES
            (
                :numeroLot,
                :quantite,
                :dateReception,
                :dateExpiration,
                :statut,
                :produit_id
            )
        ")->execute([
            'numeroLot' => $numeroLot,
            'quantite' => $quantite,
            'dateReception' => $dateReception,
            'dateExpiration' => $dateExpiration,
            'statut' => $this->calculerStatutLot($quantite, $dateExpiration),
            'produit_id' => $produitId
        ]);

        return (int)$this->db->lastInsertId();
    }


    private function mettreAJourQuantiteLot(
        int $lotId,
        int $quantite,
        string $dateExpiration
    ): string {

        $statut = $this->calculerStatutLot($quantite, $dateExpiration);

        $this->db->prepare("
            UPDATE Lot
            SET quantite = :quantite,
                statut = :statut
            WHERE id = :id
        ")->execute([
            'quantite' => $quantite,
            'statut' => $statut,
            'id' => $lotId
        ]);

        return $statut;
    }


    private function enregistrerMouvement(
        int $lotId,
        string $type,
        int $quantite,
        string $motif,
        int $utilisateurId
    ): void {

        $this->db->prepare("
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
        ")->execute([
            'type' => $type,
            'quantite' => $quantite,
            'motif' => mb_substr($motif, 0, 255),
            'lot_id' => $lotId,
            'utilisateur_id' => $utilisateurId
        ]);
    }


    private function journaliser(
        string $action,
        string $description,
        int $utilisateurId
    ): void {

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
                :action,
                :description,
                NOW(),
                :utilisateur_id
            )
        ")->execute([
            'action' => $action,
            'description' => $description,
            'utilisateur_id' => $utilisateurId
        ]);
    }


    private function calculerStatutLot(int $quantite, string $dateExpiration): string
    {
        if ($dateExpiration < date('Y-m-d')) {
            return 'PERIME';
        }

        if ($quantite <= 0) {
            return 'EPUISE';
        }

        return 'DISPONIBLE';
    }


    private function validerLot(
        string $numeroLot,
        $quantite,
        string $dateReception,
        string $dateExpiration,
        ?string $produit = null
    ): void {

        $suffixe = $produit ? " ({$produit})" : '';

        if ($numeroLot === '') {
            throw new InvalidArgumentException('Le numéro de lot est obligatoire' . $suffixe . '.');
        }

        if (mb_strlen($numeroLot) > 100) {
            throw new InvalidArgumentException('Le numéro de lot est trop long' . $suffixe . '.');
        }

        if ($quantite === false || $quantite === null || (int)$quantite <= 0) {
            throw new InvalidArgumentException('La quantité doit être supérieure à zéro' . $suffixe . '.');
        }

        if (!$this->isDate($dateReception)) {
            throw new InvalidArgumentException('La date de réception est invalide' . $suffixe . '.');
        }

        if (!$this->isDate($dateExpiration)) {
            throw new InvalidArgumentException("La date d'expiration est invalide" . $suffixe . '.');
        }

        if ($dateExpiration <= $dateReception) {
            throw new InvalidArgumentException(
                "La date d'expiration doit être postérieure à la date de réception" . $suffixe . '.'
            );
        }
    }


    private function produitExiste(int $produitId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM Produit
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $produitId
        ]);

        return (bool)$stmt->fetchColumn();
    }


    private function isDate(string $value): bool
    {
        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }


    private function motifParDefaut(string $type): string
    {
        return [
            'ENTREE' => 'Entrée manuelle',
            'SORTIE' => 'Sortie manuelle',
            'PERTE' => 'Perte / casse',
            'RETOUR' => 'Retour en stock',
            'AJUSTEMENT' => 'Ajustement d\'inventaire'
        ][$type];
    }


    private function libelleType(string $type): string
    {
        return [
            'ENTREE' => 'Entrée',
            'SORTIE' => 'Sortie',
            'PERTE' => 'Perte',
            'RETOUR' => 'Retour',
            'AJUSTEMENT' => 'Ajustement'
        ][$type];
    }


    private function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
