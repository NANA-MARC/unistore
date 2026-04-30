# UniStore Backend API

UniStore est une plateforme e-commerce multi-vendeurs robuste, développée avec Laravel. Ce backend fournit une API REST complète pour gérer les utilisateurs, les boutiques, les produits, les commandes, les paiements et les notifications.

## 🚀 Technologies Utilisées

- **Framework** : [Laravel 11](https://laravel.com)
- **Authentification** : [Laravel Sanctum](https://laravel.com/docs/sanctum)
- **Base de données** : MySQL
- **Documentation API** : [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) (OpenAPI 3.0)
- **Architecture** : Service Layer, Transactions DB, Middleware de Rôles.

## 🛠 Fonctionnalités Principales

### 🔐 Authentification & Sécurité
- Inscription et connexion avec gestion des rôles (**client**, **vendeur**, **super_admin**).
- Protection des routes via Sanctum (Bearer Token).
- Middleware `CheckRole` pour sécuriser les actions spécifiques à chaque profil.

### 🏪 Gestion des Boutiques (Multi-vendeurs)
- Création de boutiques par les vendeurs.
- Système d'activation des boutiques par l'administrateur.
- Profil de boutique avec logo et statistiques.

### 📦 Catalogue de Produits
- CRUD complet des produits pour les vendeurs.
- Gestion automatique des stocks lors des commandes.
- Filtrage par boutique et par catégorie.
- Galerie d'images par produit.

### 🛒 Panier & Commandes
- Gestion persistante du panier utilisateur.
- Processus de commande sécurisé avec transactions atomiques.
- Suivi du statut de la commande : `en_attente`, `confirmee`, `en_livraison`, `livree`, `annulee`.

### 💳 Paiements
- Initialisation des paiements par le client.
- Validation des paiements par le vendeur.
- Système de remboursement avec restitution automatique des stocks.

### 💬 Avis & Notifications
- Système d'avis produits (uniquement pour les clients ayant acheté le produit).
- Calcul automatique de la note moyenne.
- Notifications système pour :
  - Nouvelles commandes (vendeur).
  - Changement de statut de commande (client).
  - Validation de paiement (client).
  - Activation de boutique (vendeur).

## 📖 Documentation API (Swagger)

L'API est entièrement documentée avec Swagger. Pour accéder à l'interface interactive :

1.  Lancez le serveur : `php artisan serve`
2.  Générez la doc (si nécessaire) : `php artisan l5-swagger:generate`
3.  Accédez à : `http://localhost:8000/api/documentation`

## ⚙️ Installation

### Prérequis
- PHP 8.2+
- Composer
- MySQL

### Étapes

1.  **Cloner le dépôt**
    ```bash
    git clone [url-du-repo]
    cd unistore-backend
    ```

2.  **Installer les dépendances**
    ```bash
    composer install
    ```

3.  **Configuration de l'environnement**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Configurez votre base de données dans le fichier `.env`.

4.  **Migrations et Seeders**
    ```bash
    php artisan migrate --seed
    ```

5.  **Lien symbolique pour le stockage**
    ```bash
    php artisan storage:link
    ```

6.  **Lancer le serveur**
    ```bash
    php artisan serve
    ```

## 📂 Structure du Projet (Points Clés)

- **Controllers** : `app/Http/Controllers/` (Utilisent les attributs PHP 8 pour Swagger).
- **Services** : `app/Services/NotificationService.php` (Logique centralisée).
- **Models** : `app/Models/` (Relations Eloquent définies).
- **Middleware** : `app/Http/Middleware/CheckRole.php`.

---
Développé pour UniStore - Solution e-commerce moderne.
# unistore
