-- =========================================================
-- DONNÉES DE DÉMONSTRATION
-- =========================================================
--
-- À importer APRÈS pharmastock.sql, sur une base vide.
-- Sur une base qui contient déjà des données, l'import
-- s'arrête sur une erreur de doublon (aucune donnée n'est écrasée).
--
-- Les dates sont calculées par rapport au jour de l'import
-- (lot périmé, lot qui expire bientôt, ventes de la semaine...).
--
-- Comptes :
--   admin@test.fr    / admin    (ADMIN)
--   pharma@test.fr   / pharma   (PHARMACIEN)
--   employe@test.fr  / employe  (EMPLOYE)
-- =========================================================

USE gestion_pharmacie;


-- =========================================================
-- RÔLES ET UTILISATEURS
-- =========================================================

INSERT INTO Role (id, nom) VALUES
    (1, 'ADMIN'),
    (2, 'PHARMACIEN'),
    (3, 'EMPLOYE');

INSERT INTO Utilisateur (id, nom, prenom, email, motDePasse, dateCreation, role_id) VALUES
    (1, 'Martin', 'Alice', 'admin@test.fr',   'admin',   CURDATE(), 1),
    (2, 'Durand', 'Paul',  'pharma@test.fr',  'pharma',  CURDATE(), 2),
    (3, 'Petit',  'Lea',   'employe@test.fr', 'employe', CURDATE(), 3);


-- =========================================================
-- CATÉGORIES ET MÉDICAMENTS
-- =========================================================

INSERT INTO Categorie (id, nom) VALUES
    (1, 'Antalgiques'),
    (2, 'Antibiotiques'),
    (3, 'Anti-inflammatoires');

INSERT INTO Produit (id, reference, nom, forme, dosage, prixAchat, prixVente, seuilMinimum, categorie_id) VALUES
    (1, 'DOL500', 'Doliprane',    'Comprimé',              '500 mg', 1.20, 2.50, 20, 1),
    (2, 'AMOX1G', 'Amoxicilline', 'Gélule',                '1 g',    3.00, 6.90, 10, 2),
    (3, 'IBU400', 'Ibuprofène',   'Comprimé',              '400 mg', 1.50, 3.20, 15, 3),
    (4, 'SPAS80', 'Spasfon',      'Comprimé',              '80 mg',  2.00, 4.10,  5, 1),
    (5, 'EFF1G',  'Efferalgan',   'Comprimé effervescent', '1 g',    1.80, 3.60, 10, 1);


-- =========================================================
-- LOTS
-- =========================================================
-- D-2401 : stock normal
-- D-2310 : expire dans 10 jours        -> alerte EXPIRATION_PROCHE
-- A-2302 : périmé depuis 5 jours       -> alertes EXPIRE + RUPTURE (Amoxicilline)
-- I-2405 : 9 unités pour un seuil de 15 -> alerte STOCK_FAIBLE
-- E-2406 : expire dans 25 jours        -> alerte EXPIRATION_PROCHE
-- Spasfon n'a aucun lot                -> alerte RUPTURE

INSERT INTO Lot (id, numeroLot, quantite, dateReception, dateExpiration, statut, produit_id) VALUES
    (1, 'D-2401', 100, DATE_SUB(CURDATE(), INTERVAL 60 DAY),  DATE_ADD(CURDATE(), INTERVAL 300 DAY), 'DISPONIBLE', 1),
    (2, 'D-2310',  12, DATE_SUB(CURDATE(), INTERVAL 200 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY),  'DISPONIBLE', 1),
    (3, 'A-2302',   8, DATE_SUB(CURDATE(), INTERVAL 400 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY),   'DISPONIBLE', 2),
    (4, 'I-2405',   9, DATE_SUB(CURDATE(), INTERVAL 30 DAY),  DATE_ADD(CURDATE(), INTERVAL 200 DAY), 'DISPONIBLE', 3),
    (5, 'E-2406',  45, DATE_SUB(CURDATE(), INTERVAL 20 DAY),  DATE_ADD(CURDATE(), INTERVAL 25 DAY),  'DISPONIBLE', 5);


-- =========================================================
-- FOURNISSEURS ET COMMANDES (À RÉCEPTIONNER)
-- =========================================================

INSERT INTO Fournisseur (id, nom, adresse, telephone, email) VALUES
    (1, 'OCP Répartition',     '12 rue des Lilas, Paris', '0102030405', 'contact@ocp.test'),
    (2, 'Alliance Healthcare', '5 avenue Foch, Lyon',     '0472000000', 'pro@alliance.test');

INSERT INTO Commande (id, dateCommande, statut, montantTotal, fournisseur_id, utilisateur_id) VALUES
    (1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'VALIDEE',    210.00, 1, 2),
    (2, CURDATE(),                          'EN_ATTENTE',  40.00, 2, 2);

INSERT INTO DetailCommande (quantite, prixUnitaire, commande_id, produit_id) VALUES
    (50, 3.00, 1, 2),
    (40, 1.50, 1, 3),
    (20, 2.00, 2, 4);


-- =========================================================
-- VENTES (POUR LES GRAPHIQUES)
-- =========================================================
-- Montants seuls : les ventes réelles se font depuis la page Ventes,
-- qui crée les lignes de détail et sort le stock.

INSERT INTO Vente (id, dateVente, montantTotal, utilisateur_id) VALUES
    (1, CURDATE(),                          7.50, 3),
    (2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 12.80, 2),
    (3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 25.30, 3),
    (4, DATE_SUB(CURDATE(), INTERVAL 5 DAY),  9.60, 2);
