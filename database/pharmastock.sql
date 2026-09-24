-- =========================================================
-- CREATION DE LA BASE DE DONNEES
-- =========================================================

CREATE DATABASE IF NOT EXISTS gestion_pharmacie
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gestion_pharmacie;


-- =========================================================
-- TABLE ROLE
-- =========================================================

CREATE TABLE Role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
) ENGINE=InnoDB;


-- =========================================================
-- TABLE UTILISATEUR
-- =========================================================

CREATE TABLE Utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    motDePasse VARCHAR(255) NOT NULL,
    dateCreation DATE NOT NULL,
    role_id INT NOT NULL,

    CONSTRAINT fk_utilisateur_role
        FOREIGN KEY (role_id)
        REFERENCES Role(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE CATEGORIE
-- =========================================================

CREATE TABLE Categorie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE PRODUIT
-- =========================================================

CREATE TABLE Produit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(100) NOT NULL UNIQUE,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    forme VARCHAR(100),
    dosage VARCHAR(100),

    prixAchat DECIMAL(10,2) NOT NULL,
    prixVente DECIMAL(10,2) NOT NULL,

    seuilMinimum INT NOT NULL,

    categorie_id INT NOT NULL,

    CONSTRAINT chk_produit_prix_achat
        CHECK (prixAchat >= 0),

    CONSTRAINT chk_produit_prix_vente
        CHECK (prixVente >= 0),

    CONSTRAINT chk_produit_seuil
        CHECK (seuilMinimum >= 0),

    CONSTRAINT fk_produit_categorie
        FOREIGN KEY (categorie_id)
        REFERENCES Categorie(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE LOT
-- =========================================================

CREATE TABLE Lot (
    id INT AUTO_INCREMENT PRIMARY KEY,

    numeroLot VARCHAR(100) NOT NULL,
    quantite INT NOT NULL,

    dateReception DATE NOT NULL,
    dateExpiration DATE NOT NULL,

    statut VARCHAR(20) NOT NULL,

    produit_id INT NOT NULL,

    CONSTRAINT chk_lot_quantite
        CHECK (quantite >= 0),

    CONSTRAINT chk_statut_lot
        CHECK (
            statut IN (
                'DISPONIBLE',
                'EPUISE',
                'PERIME'
            )
        ),

    CONSTRAINT fk_lot_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_lot_numero_produit
        UNIQUE (produit_id, numeroLot)
) ENGINE=InnoDB;


-- =========================================================
-- TABLE FOURNISSEUR
-- =========================================================

CREATE TABLE Fournisseur (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nom VARCHAR(150) NOT NULL,
    adresse VARCHAR(255),
    telephone VARCHAR(30),
    email VARCHAR(255)
) ENGINE=InnoDB;


-- =========================================================
-- TABLE COMMANDE
-- =========================================================

CREATE TABLE Commande (
    id INT AUTO_INCREMENT PRIMARY KEY,

    dateCommande DATE NOT NULL,
    statut VARCHAR(20) NOT NULL,
    montantTotal DECIMAL(10,2) NOT NULL,

    fournisseur_id INT NOT NULL,
    utilisateur_id INT NOT NULL,

    CONSTRAINT chk_commande_montant
        CHECK (montantTotal >= 0),

    CONSTRAINT chk_statut_commande
        CHECK (
            statut IN (
                'EN_ATTENTE',
                'VALIDEE',
                'RECUE',
                'ANNULEE'
            )
        ),

    CONSTRAINT fk_commande_fournisseur
        FOREIGN KEY (fournisseur_id)
        REFERENCES Fournisseur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_commande_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE DETAIL COMMANDE
-- =========================================================

CREATE TABLE DetailCommande (
    id INT AUTO_INCREMENT PRIMARY KEY,

    quantite INT NOT NULL,
    prixUnitaire DECIMAL(10,2) NOT NULL,

    commande_id INT NOT NULL,
    produit_id INT NOT NULL,

    CONSTRAINT chk_detail_commande_quantite
        CHECK (quantite > 0),

    CONSTRAINT chk_detail_commande_prix
        CHECK (prixUnitaire >= 0),

    CONSTRAINT fk_detailcommande_commande
        FOREIGN KEY (commande_id)
        REFERENCES Commande(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_detailcommande_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE VENTE
-- =========================================================

CREATE TABLE Vente (
    id INT AUTO_INCREMENT PRIMARY KEY,

    dateVente DATE NOT NULL,
    montantTotal DECIMAL(10,2) NOT NULL,

    utilisateur_id INT NOT NULL,

    CONSTRAINT chk_vente_montant
        CHECK (montantTotal >= 0),

    CONSTRAINT fk_vente_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE DETAIL VENTE
-- =========================================================

CREATE TABLE DetailVente (
    id INT AUTO_INCREMENT PRIMARY KEY,

    quantite INT NOT NULL,
    prixUnitaire DECIMAL(10,2) NOT NULL,

    vente_id INT NOT NULL,
    produit_id INT NOT NULL,
    lot_id INT NOT NULL,

    CONSTRAINT chk_detail_vente_quantite
        CHECK (quantite > 0),

    CONSTRAINT chk_detail_vente_prix
        CHECK (prixUnitaire >= 0),

    CONSTRAINT fk_detailvente_vente
        FOREIGN KEY (vente_id)
        REFERENCES Vente(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_detailvente_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_detailvente_lot
        FOREIGN KEY (lot_id)
        REFERENCES Lot(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE MOUVEMENT STOCK
-- =========================================================

CREATE TABLE MouvementStock (
    id INT AUTO_INCREMENT PRIMARY KEY,

    type VARCHAR(20) NOT NULL,
    quantite INT NOT NULL,
    dateHeure DATETIME NOT NULL,
    motif VARCHAR(255),

    lot_id INT NOT NULL,
    utilisateur_id INT NOT NULL,

    CONSTRAINT chk_mouvement_quantite
        CHECK (quantite > 0),

    CONSTRAINT chk_type_mouvement
        CHECK (
            type IN (
                'ENTREE',
                'SORTIE',
                'PERTE',
                'RETOUR',
                'AJUSTEMENT'
            )
        ),

    CONSTRAINT fk_mouvement_lot
        FOREIGN KEY (lot_id)
        REFERENCES Lot(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_mouvement_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =========================================================
-- TABLE ALERTE
-- =========================================================

CREATE TABLE Alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,

    type VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    dateCreation DATETIME NOT NULL,
    statut VARCHAR(20) NOT NULL,

    produit_id INT NULL,
    lot_id INT NULL,

    CONSTRAINT chk_type_alerte
        CHECK (
            type IN (
                'STOCK_FAIBLE',
                'RUPTURE',
                'EXPIRATION_PROCHE',
                'EXPIRE'
            )
        ),

    CONSTRAINT chk_statut_alerte
        CHECK (
            statut IN (
                'NOUVELLE',
                'TRAITEE',
                'IGNOREE'
            )
        ),

    CONSTRAINT fk_alerte_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_alerte_lot
        FOREIGN KEY (lot_id)
        REFERENCES Lot(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- TABLE JOURNAL ACTIVITE
-- =========================================================

CREATE TABLE JournalActivite (
    id INT AUTO_INCREMENT PRIMARY KEY,

    action VARCHAR(150) NOT NULL,
    description TEXT,
    dateHeure DATETIME NOT NULL,

    utilisateur_id INT NOT NULL,

    CONSTRAINT fk_journal_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;