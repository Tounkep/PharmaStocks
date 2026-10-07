# PharmaStocks

Application web de gestion de stock pour une pharmacie : médicaments, lots et dates d'expiration, ventes, commandes fournisseurs, alertes de stock.

- **Backend** : PHP 8 (PDO) qui expose une API JSON (`backend/routes/*.php`)
- **Frontend** : HTML, CSS et JavaScript (`frontend/pages/*.html`)
- **Base de données** : MySQL / MariaDB (XAMPP)


## Installation avec XAMPP

### 1. Mettre le projet dans `htdocs`

Apache ne sert que les dossiers placés dans `C:\xampp\htdocs`. Deux possibilités :

- **Cloner directement dans `htdocs`** :
  ```
  cd C:\xampp\htdocs
  git clone https://github.com/Tounkep/PharmaStocks.git
  ```
- **Ou garder le projet ailleurs et créer un lien** (dans `cmd`, en adaptant le chemin) :
  ```
  mklink /J C:\xampp\htdocs\PharmaStocks C:\chemin\vers\PharmaStocks
  ```

### 2. Démarrer Apache et MySQL

Dans le **XAMPP Control Panel**, cliquer sur **Start** pour **Apache** et pour **MySQL**. Les deux lignes doivent passer au vert.

### 3. Créer la base de données

Dans **phpMyAdmin** (http://localhost/phpmyadmin), onglet **Importer**, importer dans cet ordre :

1. `database/pharmastock.sql` : crée la base `gestion_pharmacie` et les tables
2. `database/demo.sql` : ajoute les données de démonstration (facultatif mais conseillé)

`demo.sql` s'importe sur une base vide. S'il y a déjà des données, il s'arrête sur une erreur de doublon sans rien écraser.

> Pour repartir de zéro : supprimer la base `gestion_pharmacie` dans phpMyAdmin, puis réimporter les deux fichiers.

La connexion à la base se règle dans `backend/config/Database.php` (par défaut : `localhost:3306`, utilisateur `root`, sans mot de passe, comme XAMPP).

### 4. Ouvrir l'application

http://localhost/PharmaStocks/frontend/pages/connexion.html


## Comptes de démonstration

| Rôle | Email | Mot de passe | Page d'accueil |
|---|---|---|---|
| Administrateur | `admin@test.fr` | `admin` | Dashboard admin |
| Pharmacien | `pharma@test.fr` | `pharma` | Dashboard pharmacien |
| Employé | `employe@test.fr` | `employe` | *(page non terminée)* |

Les données de démo contiennent volontairement des cas à tester : un lot périmé, des lots qui expirent bientôt, un produit sous son seuil, un produit en rupture et deux commandes à réceptionner.


## Rôles et droits

Les droits sont vérifiés côté serveur (`backend/auth/Auth.php`).

| Fonctionnalité | Admin | Pharmacien | Employé |
|---|---|---|---|
| Médicaments | gestion | gestion | lecture |
| Stock (lots, mouvements, réception) | gestion | gestion | lecture |
| Alertes | gestion | gestion | lecture |
| Ventes | oui | oui | oui |
| Commandes | gestion | gestion | — |
| Fournisseurs | gestion | lecture | — |
| Utilisateurs, dashboard admin | oui | — | — |


## Structure du projet

```
backend/
  auth/          connexion, déconnexion, session et contrôle des rôles (Auth.php)
  config/        connexion à la base (Database.php)
  models/        accès aux données (une classe par table ou par module)
  controllers/   logique de chaque module, réponses JSON
  routes/        points d'entrée de l'API appelés par le frontend
database/
  pharmastock.sql   schéma de la base
  demo.sql          données de démonstration
frontend/
  pages/         pages HTML
  js/            un script par page, plus role-layout.js (menu selon le rôle)
                 et pharmacien-utils.js (outils communs)
  css/           styles
```


## API (résumé)

Toutes les routes renvoient du JSON de la forme `{ "success": true, "data": ... }` ou `{ "success": false, "message": "..." }`. Une requête sans session renvoie **401**, une requête d'un rôle non autorisé renvoie **403**.

| Route | Rôle |
|---|---|
| `auth/connexion.php` | connexion (formulaire) et déconnexion (POST `action=logout`) |
| `auth/session.php` | utilisateur connecté (nom, rôle), pour tous les rôles |
| `routes/dashboard.php` | dashboard admin |
| `routes/dashboardPharmacien.php` | dashboard pharmacien |
| `routes/medicaments.php` | médicaments, lots (fiche détail) |
| `routes/stock.php` | lots, mouvements, retrait, réception de commande |
| `routes/alertes.php` | alertes de stock |
| `routes/Ventes.php` | ventes |
| `routes/commandes.php` | commandes fournisseurs |
| `routes/fournisseurs.php` | fournisseurs |
| `routes/utilisateurs.php` | utilisateurs |


## Fonctionnement du stock

- Le stock d'un médicament est la **somme de ses lots** ; il n'est pas stocké dans la table `Produit`.
- Un lot dont la date d'expiration est dépassée passe automatiquement au statut `PERIME` et n'est plus vendable.
- Une vente sort le stock **du lot qui expire le plus tôt** (méthode FEFO).
- Chaque changement de stock crée une ligne dans `MouvementStock` (entrée, sortie, perte, retour, ajustement) et dans `JournalActivite`.
- Les **alertes** (rupture, stock faible, expiration proche, lot périmé) sont recalculées à l'ouverture du dashboard pharmacien, de la page Stock et de la page Alertes. Elles passent à « résolue » d'elles-mêmes quand le problème disparaît.


## Limites connues

- Les mots de passe sont stockés **en clair** : il faut passer à `password_hash` / `password_verify`.
- L'espace employé n'est pas terminé : `dashboard-employe.html` n'existe pas.
- La fiche détail d'un médicament et le changement de statut d'une commande en « Reçue » ne passent pas encore par le module Stock. Pour qu'une commande ajoute du stock, il faut utiliser **Stock → Réceptionner une commande**.
