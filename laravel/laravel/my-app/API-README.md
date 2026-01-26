# Projet Cloud S5 - API Fournisseur d'Identité

## Vue d'ensemble

API REST pour la gestion des utilisateurs, authentification, et signalements de travaux routiers à Antananarivo.

### Mode : API Only
- ✅ Pas de vues HTML
- ✅ Pas de routes web inutiles
- ✅ Routes API seulement `/api/*`
- ✅ Authentification par tokens JWT

---

## Endpoints Disponibles

### Authentication
- `POST /api/auth/signup` - Créer un compte
- `POST /api/auth/login` - Se connecter (retourne un token)
- `PUT /api/auth/profile` - Mettre à jour le profil (nécessite token)
- `POST /api/auth/unlock-account/{userId}` - Débloquer un compte (Manager uniquement)

### Health Check
- `GET /api/health` - Vérifier l'état du service
- `GET /health` - Vérifier l'état du service (alias web)

---

## Lancement

```bash
cd laravel/laravel/my-app
docker compose up --build
```

**Services disponibles :**
- API : `http://localhost:8000`
- PostgreSQL : `localhost:5432`
- Redis : `localhost:6379`
- Swagger UI : `http://localhost:8081` (en préparation)

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

- [ ] Module Cartes (Tuiles OSM, Leaflet)
- [ ] Module Signalements (CRUD)
- [ ] Module Web (3 profils)
- [ ] Module Mobile (PWA/Ionic)
- [ ] Synchronisation Firebase

---

## Développeurs

- Promotion 17 (S5)
- Équipe : 4 personnes
