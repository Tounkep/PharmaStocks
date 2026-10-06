<?php

class Vente
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /* =====================================================
       PRODUITS DISPONIBLES À LA VENTE
    ====================================================== */

    public function getProducts(): array
    {
        $sql = "
            SELECT
                p.id,
                p.reference,
                p.nom,
                p.prixVente,
                p.seuilMinimum,
                COALESCE(
                    SUM(
                        CASE
                            WHEN l.statut = 'DISPONIBLE'
                                 AND l.quantite > 0
                                 AND l.dateExpiration >= CURDATE()
                            THEN l.quantite
                            ELSE 0
                        END
                    ),
                    0
                ) AS stockTotal

            FROM Produit p

            LEFT JOIN Lot l
                ON l.produit_id = p.id

            GROUP BY
                p.id,
                p.reference,
                p.nom,
                p.prixVente,
                p.seuilMinimum

            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* =====================================================
       HISTORIQUE
    ====================================================== */

    public function getHistory(): array
    {
        $sql = "
            SELECT
                v.id,
                v.dateVente,
                v.montantTotal,
                u.nom,
                u.prenom,

                COALESCE(
                    SUM(dv.quantite),
                    0
                ) AS nombreArticles,

                COALESCE(
                    GROUP_CONCAT(
                        DISTINCT CONCAT(p.nom, ' x', dv.quantite)
                        SEPARATOR ', '
                    ),
                    '-'
                ) AS medicaments

            FROM Vente v

            INNER JOIN Utilisateur u
                ON u.id = v.utilisateur_id

            LEFT JOIN DetailVente dv
                ON dv.vente_id = v.id

            LEFT JOIN Produit p
                ON p.id = dv.produit_id

            GROUP BY
                v.id,
                v.dateVente,
                v.montantTotal,
                u.nom,
                u.prenom

            ORDER BY
                v.id DESC

            LIMIT 100
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /* =====================================================
       DETAIL D'UNE VENTE
    ====================================================== */

    public function getDetail(int $id): ?array
    {
        $sql = "
            SELECT
                v.id,
                v.dateVente,
                v.montantTotal,
                u.nom,
                u.prenom

            FROM Vente v

            INNER JOIN Utilisateur u
                ON u.id = v.utilisateur_id

            WHERE v.id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $vente =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$vente) {
            return null;
        }


        $sqlDetails = "
            SELECT
                dv.id,
                dv.quantite,
                dv.prixUnitaire,

                p.id AS produit_id,
                p.nom AS produitNom,
                p.reference,

                l.id AS lot_id,
                l.numeroLot,
                l.dateExpiration

            FROM DetailVente dv

            INNER JOIN Produit p
                ON p.id = dv.produit_id

            INNER JOIN Lot l
                ON l.id = dv.lot_id

            WHERE dv.vente_id = :vente_id

            ORDER BY dv.id ASC
        ";

        $stmtDetails =
            $this->db->prepare($sqlDetails);

        $stmtDetails->execute([
            ':vente_id' => $id
        ]);

        $vente['details'] =
            $stmtDetails->fetchAll(
                PDO::FETCH_ASSOC
            );


        return $vente;
    }


    /* =====================================================
       ENREGISTRER UNE VENTE
    ====================================================== */

    public function create(
        array $items,
        int $utilisateurId
    ): array {

        if (empty($items)) {

            throw new InvalidArgumentException(
                "Le panier est vide."
            );

        }


        $this->db->beginTransaction();


        try {

            /*
             * On crée d'abord la vente avec un total temporaire.
             */

            $stmtVente =
                $this->db->prepare("
                    INSERT INTO Vente
                    (
                        dateVente,
                        montantTotal,
                        utilisateur_id
                    )
                    VALUES
                    (
                        CURDATE(),
                        0,
                        :utilisateur_id
                    )
                ");

            $stmtVente->execute([
                ':utilisateur_id' =>
                    $utilisateurId
            ]);


            $venteId =
                (int)$this->db->lastInsertId();


            $total = 0;


            foreach ($items as $item) {

                $produitId =
                    filter_var(
                        $item['produit_id'] ?? null,
                        FILTER_VALIDATE_INT
                    );

                $quantiteDemandee =
                    filter_var(
                        $item['quantite'] ?? null,
                        FILTER_VALIDATE_INT
                    );


                if (
                    !$produitId ||
                    !$quantiteDemandee ||
                    $quantiteDemandee <= 0
                ) {

                    throw new InvalidArgumentException(
                        "Produit ou quantité invalide."
                    );

                }


                /*
                 * Récupération du produit.
                 */

                $stmtProduit =
                    $this->db->prepare("
                        SELECT
                            id,
                            nom,
                            prixVente

                        FROM Produit

                        WHERE id = :id

                        FOR UPDATE
                    ");

                $stmtProduit->execute([
                    ':id' => $produitId
                ]);


                $produit =
                    $stmtProduit->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (!$produit) {

                    throw new RuntimeException(
                        "Le médicament #{$produitId} n'existe pas."
                    );

                }


                $prixUnitaire =
                    (float)$produit['prixVente'];


                /*
                 * FEFO :
                 * on prend d'abord le lot
                 * qui expire le plus tôt.
                 */

                $stmtLots =
                    $this->db->prepare("
                        SELECT
                            id,
                            numeroLot,
                            quantite,
                            dateExpiration

                        FROM Lot

                        WHERE produit_id = :produit_id

                        AND statut = 'DISPONIBLE'

                        AND quantite > 0

                        AND dateExpiration >= CURDATE()

                        ORDER BY
                            dateExpiration ASC,
                            id ASC

                        FOR UPDATE
                    ");

                $stmtLots->execute([
                    ':produit_id' =>
                        $produitId
                ]);


                $lots =
                    $stmtLots->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                $reste =
                    $quantiteDemandee;


                foreach ($lots as $lot) {

                    if ($reste <= 0) {
                        break;
                    }


                    $quantiteLot =
                        (int)$lot['quantite'];


                    $quantiteSortie =
                        min(
                            $reste,
                            $quantiteLot
                        );


                    /*
                     * Diminution du lot.
                     */

                    $nouvelleQuantite =
                        $quantiteLot -
                        $quantiteSortie;


                    $nouveauStatut =
                        $nouvelleQuantite > 0
                            ? 'DISPONIBLE'
                            : 'EPUISE';


                    $stmtUpdateLot =
                        $this->db->prepare("
                            UPDATE Lot

                            SET
                                quantite = :quantite,
                                statut = :statut

                            WHERE id = :id
                        ");

                    $stmtUpdateLot->execute([
                        ':quantite' =>
                            $nouvelleQuantite,

                        ':statut' =>
                            $nouveauStatut,

                        ':id' =>
                            $lot['id']
                    ]);


                    /*
                     * Détail de la vente.
                     */

                    $stmtDetail =
                        $this->db->prepare("
                            INSERT INTO DetailVente
                            (
                                quantite,
                                prixUnitaire,
                                vente_id,
                                produit_id,
                                lot_id
                            )
                            VALUES
                            (
                                :quantite,
                                :prixUnitaire,
                                :vente_id,
                                :produit_id,
                                :lot_id
                            )
                        ");

                    $stmtDetail->execute([
                        ':quantite' =>
                            $quantiteSortie,

                        ':prixUnitaire' =>
                            $prixUnitaire,

                        ':vente_id' =>
                            $venteId,

                        ':produit_id' =>
                            $produitId,

                        ':lot_id' =>
                            $lot['id']
                    ]);


                    /*
                     * Mouvement de stock.
                     */

                    $stmtMouvement =
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
                                'SORTIE',
                                :quantite,
                                NOW(),
                                :motif,
                                :lot_id,
                                :utilisateur_id
                            )
                        ");

                    $stmtMouvement->execute([
                        ':quantite' =>
                            $quantiteSortie,

                        ':motif' =>
                            "Vente #{$venteId}",

                        ':lot_id' =>
                            $lot['id'],

                        ':utilisateur_id' =>
                            $utilisateurId
                    ]);


                    $total +=
                        $quantiteSortie *
                        $prixUnitaire;


                    $reste -=
                        $quantiteSortie;

                }


                if ($reste > 0) {

                    throw new RuntimeException(
                        "Stock insuffisant pour : " .
                        $produit['nom']
                    );

                }

            }


            /*
             * Mise à jour du montant total.
             */

            $stmtTotal =
                $this->db->prepare("
                    UPDATE Vente

                    SET montantTotal = :total

                    WHERE id = :id
                ");

            $stmtTotal->execute([
                ':total' =>
                    number_format(
                        $total,
                        2,
                        '.',
                        ''
                    ),

                ':id' =>
                    $venteId
            ]);


            /*
             * Journal d'activité.
             */

            $stmtJournal =
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
                        'VENTE',
                        :description,
                        NOW(),
                        :utilisateur_id
                    )
                ");

            $stmtJournal->execute([
                ':description' =>
                    "Vente #{$venteId} enregistrée pour un montant de " .
                    number_format(
                        $total,
                        2,
                        '.',
                        ''
                    ) .
                    " EUR",

                ':utilisateur_id' =>
                    $utilisateurId
            ]);


            $this->db->commit();


            return [
                'id' =>
                    $venteId,

                'montantTotal' =>
                    number_format(
                        $total,
                        2,
                        '.',
                        ''
                    )
            ];


        } catch (Throwable $e) {

            if (
                $this->db->inTransaction()
            ) {

                $this->db->rollBack();

            }

            throw $e;
        }
    }
}