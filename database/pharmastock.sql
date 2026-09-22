-- =========================================================
-- TABLE ROLE
-- =========================================================

CREATE TABLE Role (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);


-- =========================================================
-- TABLE UTILISATEUR
-- =========================================================

CREATE TABLE Utilisateur (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
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
);


-- =========================================================
-- TABLE CATEGORIE
-- =========================================================

CREATE TABLE Categorie (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);


-- =========================================================
-- TABLE PRODUIT
-- =========================================================

CREATE TABLE Produit (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    reference VARCHAR(100) NOT NULL UNIQUE,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    forme VARCHAR(100),
    dosage VARCHAR(100),
    prixAchat DOUBLE PRECISION NOT NULL,
    prixVente DOUBLE PRECISION NOT NULL,
    seuilMinimum INT NOT NULL,
    dateExpiration DATE,

    categorie_id INT NOT NULL,

    CONSTRAINT fk_produit_categorie
        FOREIGN KEY (categorie_id)
        REFERENCES Categorie(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- TABLE LOT
-- =========================================================

CREATE TABLE Lot (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    numeroLot VARCHAR(100) NOT NULL,
    quantite INT NOT NULL,
    dateReception DATE NOT NULL,
    dateExpiration DATE NOT NULL,
    statut VARCHAR(20) NOT NULL,

    produit_id INT NOT NULL,

    CONSTRAINT fk_lot_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_statut_lot
        CHECK (
            statut IN (
                'DISPONIBLE',
                'EPUISE',
                'PERIME'
            )
        )
);


-- =========================================================
-- TABLE FOURNISSEUR
-- =========================================================

CREATE TABLE Fournisseur (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    adresse VARCHAR(255),
    telephone VARCHAR(30),
    email VARCHAR(255)
);


-- =========================================================
-- TABLE COMMANDE
-- =========================================================

CREATE TABLE Commande (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dateCommande DATE NOT NULL,
    statut VARCHAR(20) NOT NULL,
    montantTotal DOUBLE PRECISION NOT NULL,

    fournisseur_id INT NOT NULL,
    utilisateur_id INT NOT NULL,

    CONSTRAINT fk_commande_fournisseur
        FOREIGN KEY (fournisseur_id)
        REFERENCES Fournisseur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_commande_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_statut_commande
        CHECK (
            statut IN (
                'EN_ATTENTE',
                'VALIDEE',
                'RECUE',
                'ANNULEE'
            )
        )
);


-- =========================================================
-- TABLE DETAIL COMMANDE
-- =========================================================

CREATE TABLE DetailCommande (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    quantite INT NOT NULL,
    prixUnitaire DOUBLE PRECISION NOT NULL,

    commande_id INT NOT NULL,
    produit_id INT NOT NULL,

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
);


-- =========================================================
-- TABLE VENTE
-- =========================================================

CREATE TABLE Vente (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dateVente DATE NOT NULL,
    montantTotal DOUBLE PRECISION NOT NULL,

    utilisateur_id INT NOT NULL,

    CONSTRAINT fk_vente_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- TABLE DETAIL VENTE
-- =========================================================

CREATE TABLE DetailVente (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    quantite INT NOT NULL,
    prixUnitaire DOUBLE PRECISION NOT NULL,

    vente_id INT NOT NULL,
    produit_id INT NOT NULL,
    lot_id INT NOT NULL,

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
);


-- =========================================================
-- TABLE MOUVEMENT STOCK
-- =========================================================

CREATE TABLE MouvementStock (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    type VARCHAR(20) NOT NULL,

    quantite INT NOT NULL,

    dateHeure TIMESTAMP NOT NULL,

    motif VARCHAR(255),

    lot_id INT NOT NULL,

    utilisateur_id INT NOT NULL,

    CONSTRAINT fk_mouvement_lot
        FOREIGN KEY (lot_id)
        REFERENCES Lot(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_mouvement_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_type_mouvement
        CHECK (
            type IN (
                'ENTREE',
                'SORTIE',
                'PERTE',
                'RETOUR',
                'AJUSTEMENT'
            )
        )
);


-- =========================================================
-- TABLE ALERTE
-- =========================================================

CREATE TABLE Alerte (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    type VARCHAR(30) NOT NULL,

    message TEXT NOT NULL,

    dateCreation TIMESTAMP NOT NULL,

    statut VARCHAR(20) NOT NULL,

    produit_id INT NULL,

    lot_id INT NULL,

    CONSTRAINT fk_alerte_produit
        FOREIGN KEY (produit_id)
        REFERENCES Produit(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_alerte_lot
        FOREIGN KEY (lot_id)
        REFERENCES Lot(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

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
        )
);


-- =========================================================
-- TABLE JOURNAL ACTIVITE
-- =========================================================

CREATE TABLE JournalActivite (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    action VARCHAR(150) NOT NULL,

    description TEXT,

    dateHeure TIMESTAMP NOT NULL,

    utilisateur_id INT NOT NULL,

    CONSTRAINT fk_journal_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES Utilisateur(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);