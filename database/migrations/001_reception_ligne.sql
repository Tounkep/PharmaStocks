-- =========================================================
-- MIGRATION : contrôle des produits à la réception
-- =========================================================
--
-- À exécuter une seule fois sur une base créée AVANT l'ajout
-- de la table ReceptionLigne (phpMyAdmin > gestion_pharmacie
-- > Importer). Sans effet si la table existe déjà.
-- =========================================================

USE gestion_pharmacie;

-- =========================================================
-- TABLE RECEPTION LIGNE
-- =========================================================
-- Contrôle des produits livrés lors de la réception d'une
-- commande : une ligne par quantité acceptée (mise en stock
-- dans un lot) et une ligne par quantité refusée (avec motif).

CREATE TABLE IF NOT EXISTS ReceptionLigne (
    id INT AUTO_INCREMENT PRIMARY KEY,

    dateReception DATETIME NOT NULL,
    decision VARCHAR(10) NOT NULL,
    quantite INT NOT NULL,

    numeroLot VARCHAR(100),
    dateExpiration DATE,
    motif VARCHAR(255),

    commande_id INT NOT NULL,
    detail_commande_id INT NULL,
    produit_id INT NOT NULL,
    lot_id INT NULL,
    utilisateur_id INT NOT NULL,

    CONSTRAINT chk_reception_decision
        CHECK (
            decision IN (
                'ACCEPTEE',
                'REFUSEE'
            )
        ),

    CONSTRAINT chk_reception_quantite
        CHECK (quantite > 0),

    CONSTRAINT fk_reception_commande
        FOREIGN KEY (commande_id)
        REFERENCES Commande(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_reception_detail
        FOREIGN KEY (detail_commande_id)
        REFERENCES DetailCommande(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_reception_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_reception_lot
        FOREIGN KEY (lot_id)
        REFERENCES Lot(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_reception_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;
