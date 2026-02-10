# Projet Cloud S5 - API Fournisseur d'Identité

## Vue d'ensemble

API REST pour la gestion des utilisateurs, authentification, et signalements de travaux routiers à Antananarivo.

### Mode : API Only
- ✅ Pas de vues HTML
- ✅ Pas de routes web inutiles
- ✅ Routes API seulement `/api/*`
- ✅ Authentification par tokens Sanctum (Bearer)

---

## Endpoints Disponibles

### Authentication
- `POST /api/auth/signup` - Créer un compte
- `POST /api/auth/login` - Se connecter (retourne un token)
- `POST /api/auth/logout` - Se déconnecter (token requis)
- `PUT /api/auth/profile` - Mettre à jour le profil (token requis)
- `GET /api/auth/me` - Infos utilisateur connecté (token requis)

### Signalements (Reports)
- `GET /api/reports` - Lister tous les signalements (?user_id=&status=&road_id=)
- `POST /api/reports` - Créer un signalement (JSON ou form-data avec photo)
- `GET /api/reports/my` - Mes signalements
- `GET /api/reports/{id}` - Voir un signalement
- `PUT /api/reports/{id}` - Modifier un signalement (Manager only)
- `DELETE /api/reports/{id}` - Supprimer un signalement (Manager only)

### Routes (Roads)
- `GET /api/roads` - Lister toutes les routes
- `POST /api/roads` - Créer une route
- `GET /api/roads/{id}` - Voir une route
- `PUT /api/roads/{id}` - Modifier une route
- `DELETE /api/roads/{id}` - Supprimer une route

### Manager
- `POST /api/auth/manager-signup` - Créer utilisateur mobile (Manager only, crée dans Laravel + Firebase)
- `GET /api/auth/locked-accounts` - Liste utilisateurs bloqués (Manager only)
- `POST /api/auth/unlock-account/{userId}` - Débloquer un compte (Manager only)
- `PUT /api/roads/{id}/status` - Modifier statut travaux d'une route (Manager only)
- `PUT /api/roads/{id}/road-details` - Modifier détails (surface, budget, entreprise) (Manager only)
- `POST /api/manager/sync` - Synchronisation Firebase (Manager only)

### Statistiques (publiques)
- `GET /api/statistics` - Statistiques globales (total routes, budget, surface, avancement %, etc.)
- `GET /api/statuses` - Liste des statuts disponibles
- `GET /api/enterprises` - Liste des entreprises

### Travaux Routiers
- `GET /api/roadworks` - Liste des travaux routiers
- `GET /api/roads-details` - Toutes les routes avec détails complets
- `GET /api/roads/{roadId}/details` - Détails complets d'une route

### Health Check
- `GET /api/health` - Vérifier l'état du service

---

## Lancement

```bash
cd laravel/laravel/my-app
docker compose up --build
```

**Services disponibles :**
- API : `http://localhost:8000`
- PostgreSQL : `localhost:5433` (mappé depuis 5432 interne)
- Redis : `localhost:6379`
- Swagger UI : `http://localhost:8081`
- Serveur de Tuiles (Carte) : `http://localhost:8082`

---

## Documentation API (Swagger)

L'interface Swagger UI est accessible après `docker compose up` à :

👉 **http://localhost:8081**

Le fichier OpenAPI est généré dans `openapi.json` à la racine du projet.
Pour tester les routes protégées dans Swagger :
1. Exécuter `POST /auth/login` pour obtenir un token
2. Cliquer sur le bouton **Authorize** 🔓 en haut
3. Entrer le token (sans le préfixe "Bearer")
4. Toutes les requêtes incluront automatiquement le header `Authorization: Bearer <token>`

---

## Serveur de Tuiles (Carte)

### Description
Le serveur de tuiles utilise **TileServer GL** pour servir des tuiles vectorielles/raster au format MBTiles.
Il est utilisé par le frontend (Leaflet) pour afficher la carte des routes.

### Configuration Docker
```yaml
tileserver:
  image: maptiler/tileserver-gl:latest
  container_name: laravel_tileserver
  ports:
    - "8082:80"
  volumes:
    - ./tiles:/data
```

### Installation et utilisation

1. **Démarrer** : Le serveur démarre automatiquement avec `docker compose up`
2. **Accès** : `http://localhost:8082`
3. **Dossier des tuiles** : `./tiles/` — placez vos fichiers `.mbtiles` ici

### Obtenir des tuiles pour Madagascar/Antananarivo

1. Télécharger les tuiles depuis [OpenMapTiles](https://data.maptiler.com/downloads/planet/) ou [Protomaps](https://protomaps.com/downloads)
2. Sélectionner la région **Madagascar** ou **Antananarivo**
3. Télécharger au format `.mbtiles`
4. Placer le fichier dans le dossier `./tiles/`
5. Redémarrer le conteneur : `docker compose restart tileserver`

### Intégration Leaflet (Frontend)
```javascript
L.tileLayer('http://localhost:8082/styles/basic-preview/{z}/{x}/{y}.png', {
  maxZoom: 18,
  attribution: '© OpenMapTiles © OpenStreetMap'
}).addTo(map);
```

### Notes
- Le fichier actuel `zurich_switzerland.mbtiles` est un exemple de démonstration
- Pour la production, utiliser les tuiles de Madagascar
- Le serveur supporte les formats vectoriels (PBF) et raster (PNG)

---

## Configuration

Fichier `.env` :
```env
APP_NAME=Cloud-S5
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=laravel
CACHE_DRIVER=redis
```

---

## Tests

### Avec curl
```bash
# Signup
curl -X POST http://localhost:8000/api/auth/signup \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"pass123","name":"User"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"pass123"}'
```

### Avec Postman
Importez la collection Postman : `docs/postman-collection.json`

---

## Architecture

- **Backend** : Laravel 12 (PHP 8.2-FPM)
- **Web** : Nginx 1.25
- **Base de données** : PostgreSQL 15
- **Cache** : Redis 7
- **Documentation** : Swagger/OpenAPI

---

## Modules à implémenter

- [x] Module Signalements (CRUD complet avec photos, filtrage, statuts)
- [x] Module Statistiques (total routes, budget, surface, avancement %)
- [x] Module Manager (création utilisateurs, sync Firebase, gestion statuts)
- [x] Documentation Swagger/OpenAPI
- [x] Serveur de Tuiles (TileServer GL)
- [ ] Module Cartes (Intégration Leaflet frontend)
- [ ] Module Web (3 profils : visiteur, utilisateur, manager)
- [ ] Module Mobile (PWA/Ionic)
- [ ] Synchronisation bidirectionnelle Firebase

---

## Développeurs

- Promotion 17 (S5)
- Équipe : 4 personnes
