# 🛣️ RoadWatch — Projet Cloud S5

**Application de signalements routiers à Antananarivo**

Plateforme complète (Mobile + Web + API) pour le suivi des routes, signalements de dégradations, gestion des travaux routiers et statistiques en temps réel.

---

## 📋 Table des matières

1. [Architecture](#architecture)
2. [Prérequis](#prérequis)
3. [Lancement rapide (Docker)](#-lancement-rapide-docker)
4. [Services disponibles](#-services-disponibles)
5. [Frontend mobile (Ionic/Vue)](#-frontend-mobile-ionicvue)
6. [API Backend (Laravel)](#-api-backend-laravel)
7. [Base de données](#-base-de-données)
8. [Collection Postman](#-collection-postman)
9. [Documentation Swagger](#-documentation-swagger)
10. [Application mobile (APK)](#-application-mobile-apk)
11. [Structure du projet](#-structure-du-projet)

---

## Architecture

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │     │   Backend    │     │  PostgreSQL   │
│  Ionic/Vue   │────▶│   Laravel    │────▶│    15-alpine  │
│  Port 5173   │     │  Port 8000   │     │  Port 5433    │
└──────────────┘     └──────────────┘     └──────────────┘
                           │
                     ┌─────┴─────┐
                     │           │
               ┌─────▼───┐ ┌────▼─────┐
               │  Redis   │ │ Firebase │
               │  7-alpine│ │   Auth   │
               │ Port 6379│ │ Firestore│
               └──────────┘ └──────────┘

┌──────────────┐     ┌──────────────┐
│ Swagger UI   │     │ TileServer   │
│  Port 8081   │     │  Port 8082   │
└──────────────┘     └──────────────┘
```

**Stack technique :**
- **Frontend** : Vue 3 + Ionic 8 + TypeScript + Vite + Capacitor (mobile)
- **Backend** : Laravel (PHP 8.2-FPM) + Nginx 1.25
- **BDD** : PostgreSQL 15
- **Cache** : Redis 7
- **Auth** : Laravel Sanctum (tokens) + Firebase Auth (sync mobile)
- **Carte** : Leaflet + TileServer GL (tuiles MBTiles)
- **Doc API** : Swagger UI (OpenAPI 3.0)
- **Mobile** : APK Android via Capacitor

---

## Prérequis

- **Docker Desktop** ≥ 4.x ([Télécharger](https://www.docker.com/products/docker-desktop))
- **Docker Compose** (inclus dans Docker Desktop)
- **Git** (optionnel, pour cloner le repo)

> ⚠️ Aucune installation de PHP, Node.js, PostgreSQL n'est nécessaire — tout tourne dans Docker !

---

## 🚀 Lancement rapide (Docker)

### 1. Cloner/extraire le projet

```bash
# Si ZIP :
# Extraire le fichier ZIP dans un dossier

# Si Git :
git clone <url-du-repo> projet-cloud-S5
cd projet-cloud-S5
```

### 2. Configurer l'environnement Laravel

```bash
cd laravel/laravel/my-app

# Copier le fichier d'environnement
cp .env.example .env
```

Modifier le fichier `.env` avec ces valeurs :

```env
APP_NAME=RoadWatch
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=root

CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PORT=6379

SESSION_DRIVER=database
```

### 3. Lancer tous les services

```bash
cd laravel/laravel/my-app
docker compose up --build -d
```

> ⏱️ Le premier build peut prendre 3-5 minutes (téléchargement des images Docker).

### 4. Initialiser l'application Laravel

```bash
# Générer la clé d'application
docker exec laravel_app php artisan key:generate

# Exécuter les migrations (créer les tables)
docker exec laravel_app php artisan migrate --force

# Créer le lien symbolique storage (pour les photos)
docker exec laravel_app php artisan storage:link

# (Optionnel) Peupler la base avec des données de test
docker exec laravel_app php artisan db:seed
```

### 5. Vérifier que tout fonctionne

```bash
# Test rapide de l'API
curl http://localhost:8000/api/health
# Réponse attendue : {"status":"OK"}

# Voir les logs si problème
docker compose logs -f
```

**✅ C'est prêt !** L'application est accessible.

---

## 🌐 Services disponibles

| Service | URL | Description |
|---------|-----|-------------|
| **API Laravel** | http://localhost:8000 | Backend REST API |
| **Swagger UI** | http://localhost:8081 | Documentation interactive de l'API |
| **TileServer** | http://localhost:8082 | Serveur de tuiles cartographiques |
| **PostgreSQL** | localhost:5433 | Base de données (user: `laravel`, pass: `root`, db: `laravel`) |
| **Redis** | localhost:6379 | Cache et sessions |

---

## 📱 Frontend mobile (Ionic/Vue)

### Lancement en développement (hors Docker)

```bash
cd frontend

# Installer les dépendances
npm install

# Lancer le serveur de développement
npm run dev
```

Le frontend sera accessible sur **http://localhost:5173**

### Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| **Manager** | manager@test.com | password123 |
| **Utilisateur** | user@example.com | password123 |

### Fonctionnalités principales
- 🔐 Authentification (inscription, connexion, blocage après 3 tentatives)
- 📝 Création de signalements avec photo et géolocalisation
- 🗺️ Carte Leaflet avec marqueurs de signalements
- 📊 Dashboard avec statistiques (total routes, budget, avancement %)
- 👤 Panel Manager (création utilisateurs, sync Firebase, déblocage comptes)
- 🔄 Synchronisation offline/online avec Firebase Firestore
- 📷 Compression automatique des photos avant upload

---

## 🔧 API Backend (Laravel)

### Endpoints principaux

#### Authentication
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/auth/signup` | Créer un compte | Non |
| POST | `/api/auth/login` | Se connecter | Non |
| POST | `/api/auth/logout` | Se déconnecter | ✅ |
| GET | `/api/auth/me` | Infos utilisateur connecté | ✅ |
| PUT | `/api/auth/profile` | Modifier profil | ✅ |

#### Signalements
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/reports` | Lister (filtres: ?status=, ?user_id=) | ✅ |
| POST | `/api/reports` | Créer (JSON ou form-data + photo) | ✅ |
| GET | `/api/reports/my` | Mes signalements | ✅ |
| GET | `/api/reports/{id}` | Voir un signalement | ✅ |
| PUT | `/api/reports/{id}` | Modifier (Manager) | ✅ Manager |
| DELETE | `/api/reports/{id}` | Supprimer (Manager) | ✅ Manager |

#### Routes & Travaux
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/roads` | Lister les routes | ✅ |
| GET | `/api/statistics` | Statistiques globales | Non |
| PUT | `/api/roads/{id}/status` | Modifier statut travaux (Manager) | ✅ Manager |
| PUT | `/api/roads/{id}/road-details` | Modifier détails route (Manager) | ✅ Manager |

#### Manager
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/auth/manager-signup` | Créer utilisateur mobile | ✅ Manager |
| POST | `/api/manager/sync` | Sync Firebase | ✅ Manager |
| GET | `/api/auth/locked-accounts` | Comptes bloqués | ✅ Manager |
| POST | `/api/auth/unlock-account/{id}` | Débloquer compte | ✅ Manager |

### Authentification
L'API utilise **Laravel Sanctum** (tokens Bearer) :
```bash
# 1. Login → récupérer le token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@test.com","password":"password123"}'

# Réponse : { "token": "1|abc123...", "user": {...} }

# 2. Utiliser le token dans les requêtes suivantes
curl http://localhost:8000/api/reports \
  -H "Authorization: Bearer 1|abc123..."
```

---

## 🗄️ Base de données

**PostgreSQL 15** avec les tables principales :
- `users` — Utilisateurs (rôles: visitor, user, manager)
- `reports` — Signalements routiers (avec photo, statut, géolocalisation)
- `roads` — Routes à surveiller
- `roadworks` — Travaux routiers (budget, entreprise, statut)
- `statuses` — Statuts de travaux (avec pourcentage d'avancement)
- `enterprises` — Entreprises de travaux publics
- `login_attempts` — Tentatives de connexion (blocage après 3 échecs)

### Accès direct à la BDD
```bash
# Via Docker
docker exec -it laravel_postgres psql -U laravel -d laravel

# Ou avec un client (DBeaver, pgAdmin, etc.)
# Host: localhost, Port: 5433, User: laravel, Password: root, DB: laravel
```

---

## 📮 Collection Postman

Le fichier `RoadWatch-API.postman_collection.json` est fourni dans `laravel/laravel/my-app/`.

### Importer dans Postman
1. Ouvrir **Postman**
2. Cliquer sur **Import** (Ctrl+O)
3. Sélectionner le fichier `RoadWatch-API.postman_collection.json`
4. La collection contient **28 requêtes** organisées par catégorie

### Variables de la collection
| Variable | Valeur | Description |
|----------|--------|-------------|
| `baseUrl` | `http://localhost:8000/api` | URL de base de l'API |
| `token` | *(auto)* | Token Sanctum (sauvegardé auto après Login) |

### Utilisation
1. Exécuter la requête **Login** en premier (le token est sauvegardé automatiquement)
2. Toutes les autres requêtes utiliseront ce token
3. Pour tester en tant que Manager, se connecter avec `manager@test.com`

---

## 📖 Documentation Swagger

Après `docker compose up`, la documentation interactive est sur :

👉 **http://localhost:8081**

Pour tester les routes protégées :
1. Exécuter `POST /auth/login` pour obtenir un token
2. Cliquer sur **Authorize** 🔓 en haut
3. Entrer le token (sans le préfixe "Bearer")
4. Toutes les requêtes incluront automatiquement le header Authorization

Le fichier source OpenAPI : `laravel/laravel/my-app/openapi.json`

---

## 📲 Application mobile (APK)

L'APK Android est généré via **Capacitor 8**.

### Installation sur téléphone Android
1. Transférer `app-debug.apk` sur le téléphone (USB, email, Drive...)
2. Sur le téléphone : **Paramètres → Sécurité → Sources inconnues** → Activer
3. Ouvrir le fichier APK et installer

### Rebuild de l'APK (si nécessaire)
```bash
cd frontend

# Installer les dépendances
npm install

# Build le frontend
npm run build

# Synchroniser avec Android
npx cap sync android

# Build l'APK
cd android
./gradlew assembleDebug

# L'APK est dans : android/app/build/outputs/apk/debug/app-debug.apk
```

> ⚠️ Nécessite : JDK 21, Android SDK (platforms-34, build-tools-34)

---

## 📁 Structure du projet

```
projet-cloud-S5/
├── README.md                          ← Ce fichier
├── frontend/                          ← Application Ionic/Vue.js
│   ├── src/
│   │   ├── views/                     ← Pages (Login, Map, Report, Dashboard...)
│   │   ├── services/                  ← API, Firebase, LocalDB, Report
│   │   ├── composables/               ← useAuth, useUserRole
│   │   ├── components/                ← AuthGuard, ManagerPanel
│   │   └── router/                    ← Routes de l'app
│   ├── capacitor.config.ts            ← Config Capacitor (mobile)
│   ├── vite.config.ts                 ← Config Vite (build)
│   └── package.json
│
├── laravel/laravel/my-app/            ← Backend Laravel
│   ├── app/
│   │   ├── Http/Controllers/          ← Controllers API
│   │   ├── Models/                    ← Modèles Eloquent
│   │   └── Services/                  ← Services métier
│   ├── routes/api.php                 ← Définition des routes API
│   ├── database/migrations/           ← Migrations BDD
│   ├── docker-compose.yml             ← Orchestration Docker
│   ├── docker/
│   │   ├── php/Dockerfile             ← Image PHP 8.2-FPM
│   │   └── nginx/default.conf         ← Config Nginx
│   ├── openapi.json                   ← Documentation OpenAPI/Swagger
│   ├── RoadWatch-API.postman_collection.json  ← Collection Postman
│   └── API-README.md                  ← Documentation API détaillée
│
├── database/                          ← Scripts SQL initiaux
│   ├── init.sql
│   └── cloud.sql
│
├── firebase.json                      ← Config Firebase
├── firebase.rules                     ← Règles Firestore
└── toDo/                              ← Notes et suivi du projet
```

---

## 🐳 Commandes Docker utiles

```bash
# Démarrer tous les services
docker compose up -d --build

# Voir les logs en temps réel
docker compose logs -f

# Arrêter tous les services
docker compose down

# Arrêter et supprimer les volumes (reset BDD)
docker compose down -v

# Reconstruire un service spécifique
docker compose build app
docker compose up -d app

# Exécuter une commande artisan
docker exec laravel_app php artisan <commande>

# Accéder au shell du conteneur PHP
docker exec -it laravel_app bash

# Voir l'état des conteneurs
docker compose ps
```

---

## 👥 Équipe

Projet Cloud S5 — Promotion 17
Université d'Antananarivo

---

*Dernière mise à jour : Février 2026*