# 🚀 Woyofal - API de Gestion d'Achat de Crédit Électrique

![PHP](https://img.shields.io/badge/PHP-8.3-blue?style=flat-square&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-blue?style=flat-square&logo=postgresql)
![Docker](https://img.shields.io/badge/Docker-Enabled-blue?style=flat-square&logo=docker)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Application PHP structurée permettant de gérer l'achat de crédit électrique (style Woyofal) via une API REST. Le système calcule automatiquement les kilowattheures (kWh) selon les tranches de prix définies et génère des codes de recharge uniques.

---

## 📋 Table des Matières

1. [Fonctionnalités](#-fonctionnalités)
2. [Architecture](#-architecture)
3. [Prérequis](#-prérequis)
4. [Installation](#-installation)
5. [Configuration](#-configuration)
6. [API Endpoints](#-api-endpoints)
7. [Base de Données](#-base-de-données)
8. [Déploiement](#-déploiement)
9. [Commandes Utiles](#-commandes-utiles)
10. [Structure du Projet](#-structure-du-projet)
11. [Licence](#-licence)

---

## ✨ Fonctionnalités

- 🔌 **Achat de crédit électrique** - Achat de kWh avec calcul automatique selon les tranches
- 📊 **Calcul intelligent des tranches** - 4 tranches de prix progressives (98 à 125FCFA/kWh)
- 🔍 **Vérification de compteur** - Validation de l'existence et du statut d'un compteur
- 📝 **Génération de codes** - Références et codes de recharge uniques
- 📋 **Logging complet** - Suivi de toutes les transactions avec géolocalisation
- 🏗️ **Architecture MVC** - Structure organisée avec injection de dépendances YAML
- 🐳 **Conteneurisation Docker** - Déploiement simple et reproductible
- ☁️ **Prêt pour le cloud** - Configuration prête pour Render.com

---

## 🏗️ Architecture

Le projet utilise une architecture **MVC (Model-View-Controller)** minimaliste avec les caractéristiques suivantes :

```
┌─────────────────────────────────────────────────────────────┐
│                      APPLICATION                            │
├─────────────────────────────────────────────────────────────┤
│  Controller (WoyofalController)                              │
│     ↓                                                       │
│  Service (WoyofalService)                                   │
│     ↓                                                       │
│  Repository (AchatRepository, CompteurRepository, etc.)     │
│     ↓                                                       │
│  Entity (Achat, Client, Compteur, LogAchat, Tranche)        │
├─────────────────────────────────────────────────────────────┤
│  Core Framework                                             │
│  ├── Router    → Gestion des routes                         │
│  ├── Database  → Connexion PDO (Singleton)                  │
│  ├── App       → Injection de dépendances YAML              │
│  └── Abstract  → Classes de base                            │
├─────────────────────────────────────────────────────────────┤
│  Config (services.yml) → Dépendances injectées              │
└─────────────────────────────────────────────────────────────┘
```

### Composants Principaux

| Composant | Rôle |
|-----------|------|
| **Router** |Analyse les requêtes URL et fait correspondre les routes définies |
| **Database** |Gestion des connexions PostgreSQL (mode singleton) |
| **App** |Conteneur d'injection de dépendances YAML |
| **WoyofalService** |Logique métier : calculs d'achat, génération de codes |
| **Repositories** |Couche d'accès aux données |

---

## 📦 Prérequis

- **PHP** 8.0+ (Testé avec 8.3)
- **PostgreSQL** 12+
- **Composer** 2.0+
- **Docker** (Optionnel, pour le déploiement conteneurisé)
- **Git**

---

## 🛠️ Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-username/woyofal.git
cd woyofal
```

### 2. Installer les dépendances

```bash
composer install
```

Cela installera :
- `vlucas/phpdotenv` - Gestion des variables d'environnement
- `symfony/yaml` - Analyse de la configuration YAML
- `phpunit/phpunit` - Framework de test

### 3. Configurer les variables d'environnement

```bash
cp .env.example .env
```

Puis éditez le fichier `.env` (voir la section [Configuration](#-configuration))

### 4. Configurer la base de données

```bash
# Exécuter les migrations
php migrations/migration.php

# (Optionnel) Remplir les données de test
php seeders/seeder.php
```

---

## ⚙️ Configuration

### Variables d'environnement (.env)

```env
# Base de données
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=woyofal_db
DB_USER=postgres
DB_PASSWORD=votre_mot_de_passe

# Application
APP_ENV=development
METHODE_INSTANCE_NAME=getInstance
SERVICES_PATH=/var/www/html/app/config/services.yml
```

### Services YAML (services.yml)

```yaml
ERROR_CONTROLLER:
  class: 
  argument : []
DATABASE: 
  class: DevNoKage\Database
  argument : 
   - "%DB_DRIVE%" 
   - "%DB_HOST%" 
   - "%DB_PORT%" 
   - "%DB_NAME%" 
   - "%DB_USER%" 
   - "%DB_PASSWORD%" 
```

---

## 🔌 API Endpoints

### 1. Acheter du crédit électrique

```http
POST /api/woyofal/acheter
Content-Type: application/json

{
  "numero_compteur": "CPT001",
  "montant": 5000
}
```

**Réponse en cas de succès :**

```json
{
  "data": {
    "compteur": "CPT001",
    "reference": "WOY-20241215-0001",
    "code": "1234-5678-9012-3456",
    "date": "2024-12-15T10:30:00Z",
    "tranche": "Tranche 2",
    "prix": "105.00",
    "nbreKwt": "45.24",
    "client": "john doe"
  },
  "statut": "success",
  "code": 200,
  "message": "Achat effectué avec succès"
}
```

### 2. Vérifier un compteur

```http
GET /api/woyofal/compteur/{numero}
```

**Exemple :**
```http
GET /api/woyofal/compteur/CPT001
```

**Réponse en cas de succès :**

```json
{
  "data": {
    "compteur": "CPT001",
    "client": "John Doe",
    "actif": true,
    "date_creation": "2024-01-15T10:00:00Z"
  },
  "statut": "success",
  "code": 200,
  "message": "Compteur trouvé"
}
```

---

## 💰 Tranches de Prix

| Tranche | Montant (FCFA) | Prix/kWh |
|---------|----------------|----------|
| Tranche 1 | 0 - 5,000 | 98 |
| Tranche 2 | 5,001 - 15,000 | 105 |
| Tranche 3 | 15,001 - 30,000 | 115 |
| Tranche 4 | 30,001+ | 125 |

**Exemple de calcul pour 5,000FCFA：**
- 5,000 ÷ 98 = **51.02 kWh**

---

## 🗄️ Base de Données

### Schéma des Tables

```
┌─────────────────┐       ┌─────────────────┐
│    clients      │       │   compteurs     │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │───┐   │ id (PK)         │
│ nom             │   └───│ client_id (FK)  │
│ prenom          │       │ numero (UNIQUE) │
│ telephone       │       │ actif           │
│ adresse         │       │ date_creation   │
└─────────────────┘       └─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│     achats      │       │  logs_achats    │
├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │
│ reference       │       │ date_heure      │
│ code_recharge   │       │ localisation    │
│ numero_compteur │       │ adresse_ip      │
│ montant         │       │ statut          │
│ nbre_kwt        │       │ message_erreur  │
│ tranche         │       └─────────────────┘
│ prix_kw         │
└─────────────────┘
```

### Migrations

Les migrations sont situées dans `migrations/script.sql` et créent :

- Tables principales (`clients`, `compteurs`, `achats`, `tranches`, `logs_achats`)
- Fonctions PostgreSQL (`generer_code_recharge()`, `generer_reference_achat()`)
- Index pour optimiser les performances
- Triggers pour la mise à jour automatique des timestamps

---

## 🚀 Déploiement

### Docker

```bash
# Construire l'image
docker build -t woyofal .

# Lancer le conteneur
docker run -p 80:80 woyofal
```

### Render.com

Le projet est configuré avec `render.yml` pour le déploiement automatique :

1. Connecter le dépôt GitHub à Render
2. Créer un nouveau Web Service
3. Sélectionner "Docker" comme runtime
4. Configurer les variables d'environnement (voir ci-dessus)
5. Le déploiement commence automatiquement

**环境变量 Render：**
```
DB_HOST=aws-0-eu-west-3.pooler.supabase.com
DB_PORT=5432
DB_USER=postgres.bggsopbguyuuljjuqzsd
DB_PASSWORD=votre_mot_de_passe_supabase
DB_NAME=postgres
APP_ENV=production
```

---

## 📜 Commandes Utiles

```bash
# Installer les dépendances
composer install

# Migration de la base de données
composer database:migrate

# Réinitialiser la base de données (supprimer toutes les tables)
composer database:reset

# Remplir les données de test
composer database:seed
```

---

## 📁 Structure du Projet

```
woyofal/
├── app/
│   ├── config/
│   │   ├── bootstrap.php      # Fichier de démarrage
│   │   ├── env.php            # Configuration d'environnement
│   │   ├── helpers.php        # Fonctions auxiliaires
│   │   ├── services.php       # Chargement des services
│   │   └── services.yml       # Configuration d'injection de dépendances
│   ├── core/
│   │   ├── App.php            # Conteneur d'injection de dépendances
│   │   ├── Database.php       # Connexion à la base de données
│   │   ├── Router.php         # Analyseur de routes
│   │   ├── abstract/          # Classes de base abstraites
│   │   ├── enums/             # Classes d'énumération
│   │   └── Interfaces/        # Définitions d'interfaces
│   └── ...
├── migrations/
│   ├── migration.php          # Script de migration
│   └── script.sql             # Structure de la base de données
├── public/
│   ├── index.php              # Point d'entrée
│   └── debug.php              # Page de débogage
├── routes/
│   └── route.web.php          # Définition des routes
├── seeders/
│   ├── seeder.php             # Script de remplissage des données
│   └── script.sql             # Données de test
├── src/
│   ├── controller/
│   │   └── WoyofalController.php  # Contrôleur API
│   ├── entity/
│   │   ├── Achat.php          # Entité d'achat
│   │   ├── Client.php         # Entité client
│   │   ├── Compteur.php       # Entité compteur
│   │   └── ...
│   ├── repository/
│   │   └── *Repository.php    # Couche d'accès aux données
│   └── service/
│       └── WoyofalService.php # Couche logique métier
├── docker/
│   ├── nginx/
│   │   ├── default.conf       # Configuration Nginx
│   │   └── supervisord.conf   # Gestion des processus
│   └── Dockerfile             # Configuration de l'image Docker
├── composer.json
├── render.yml                 # Configuration de déploiement Render
└── README.md
```

---

## 🧪 Test

```bash
# Exécuter tous les tests
./vendor/bin/phpunit

# Exécuter un test spécifique
./vendor/bin/phpunit tests/AchatServiceTest.php
```

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👤 Auteur

**Mapathé Ndiaye**

- GitHub: [@mapthe](https://github.com/mapthe)
- Email: mapathe@gmail.com

---

<div align="center">
  Fait avec ❤️ pour le Sénégal
</div>

