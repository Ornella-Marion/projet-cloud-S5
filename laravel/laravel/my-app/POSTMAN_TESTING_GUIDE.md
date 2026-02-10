# 🧪 GUIDE COMPLET POSTMAN - TESTER LES NOUVELLES API

## 📋 Table des matières
1. [Configuration initiale](#configuration-initiale)
2. [Authentification](#authentification)
3. [Tests Roadworks](#tests-roadworks)
4. [Tests Photos](#tests-photos)
5. [Tests Notifications](#tests-notifications)
6. [Tests Firebase Tokens](#tests-firebase-tokens)
7. [Workflow complet](#workflow-complet)

---

## 🔧 Configuration initiale

### Étape 1: Importer les collections Postman

1. Ouvrir **Postman**
2. Cliquer sur **Import**
3. Coller l'URL de votre API: `http://localhost:8000`

### Étape 2: Créer des variables d'environnement

1. Cliquer sur **Environments** → **+**
2. Créer un nouvel environnement: `Local Development`
3. Ajouter ces variables:

```
baseUrl     : http://localhost:8000
token       : [sera rempli après login]
roadwork_id : [sera rempli après création]
photo_id    : [sera rempli après upload]
```

---

## 🔐 Authentification

### 1️⃣ Se connecter (POST /api/auth/login)

**URL:**
```
{{baseUrl}}/api/auth/login
```

**Method:** POST

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "email": "manager@example.com",
  "password": "manager123"
}
```

**✅ Response (201):**
```json
{
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz1234567890",
  "user": {
    "id": 1,
    "name": "Manager Default",
    "email": "manager@example.com",
    "role": "manager",
    "is_active": true
  },
  "expires_in": 604800
}
```

**💡 Action après réception:**
- Copier le token (la partie après le `|`)
- Aller à **Environments** et coller dans la variable `token`

---

## 🛣️ Tests Roadworks

### 2️⃣ Créer un Roadwork (POST /api/roadworks)

**URL:**
```
{{baseUrl}}/api/roadworks
```

**Method:** POST

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {{token}}
```

**Body (JSON):**
```json
{
  "title": "Réparation Route Principale",
  "description": "Réparation complète de la chaussée - Phase 1",
  "location": "Rue de la Paix 45, Zurich",
  "latitude": 47.3769,
  "longitude": 8.5472,
  "status": "planned",
  "planned_start_date": "2026-02-15T09:00:00",
  "planned_end_date": "2026-02-28T17:00:00",
  "notes": "Travaux prioritaires - Circulation détournée"
}
```

**✅ Response (201):**
```json
{
  "id": 1,
  "title": "Réparation Route Principale",
  "description": "Réparation complète de la chaussée - Phase 1",
  "location": "Rue de la Paix 45, Zurich",
  "latitude": 47.3769,
  "longitude": 8.5472,
  "status": "planned",
  "created_by": 1,
  "created_at": "2026-02-10T10:00:00",
  "updated_at": "2026-02-10T10:00:00"
}
```

**💡 Action après réception:**
- Copier l'`id` (ici: 1)
- Mettre à jour la variable `roadwork_id` dans l'environnement

---

### 3️⃣ Lister tous les Roadworks (GET /api/roadworks)

**URL:**
```
{{baseUrl}}/api/roadworks
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Réparation Route Principale",
      "status": "planned",
      "location": "Rue de la Paix 45, Zurich",
      "created_at": "2026-02-10T10:00:00"
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 15,
    "current_page": 1
  }
}
```

---

### 4️⃣ Obtenir un Roadwork spécifique (GET /api/roadworks/{id})

**URL:**
```
{{baseUrl}}/api/roadworks/{{roadwork_id}}
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "id": 1,
  "title": "Réparation Route Principale",
  "description": "Réparation complète de la chaussée - Phase 1",
  "location": "Rue de la Paix 45, Zurich",
  "latitude": 47.3769,
  "longitude": 8.5472,
  "status": "planned",
  "planned_start_date": "2026-02-15T09:00:00",
  "planned_end_date": "2026-02-28T17:00:00",
  "started_at": null,
  "completed_at": null,
  "creator": {
    "id": 1,
    "name": "Manager Default",
    "email": "manager@example.com"
  },
  "photos": [],
  "statusHistory": []
}
```

---

### 5️⃣ Démarrer les travaux (PUT /api/roadworks/{id})

**URL:**
```
{{baseUrl}}/api/roadworks/{{roadwork_id}}
```

**Method:** PUT

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {{token}}
```

**Body (JSON):**
```json
{
  "status": "in_progress",
  "started_at": "2026-02-10T08:30:00"
}
```

**✅ Response (200):**
```json
{
  "id": 1,
  "status": "in_progress",
  "started_at": "2026-02-10T08:30:00",
  "updated_at": "2026-02-10T10:05:00",
  "statusHistory": [
    {
      "id": 1,
      "old_status": "planned",
      "new_status": "in_progress",
      "changed_by": 1,
      "changed_at": "2026-02-10T08:30:00"
    }
  ]
}
```

---

### 6️⃣ Terminer les travaux (PUT /api/roadworks/{id})

**URL:**
```
{{baseUrl}}/api/roadworks/{{roadwork_id}}
```

**Method:** PUT

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {{token}}
```

**Body (JSON):**
```json
{
  "status": "completed",
  "completed_at": "2026-02-25T16:30:00"
}
```

---

### 7️⃣ Obtenir l'historique des changements (GET /api/roadworks/{id}/status-history)

**URL:**
```
{{baseUrl}}/api/roadworks/{{roadwork_id}}/status-history
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
[
  {
    "id": 1,
    "roadwork_id": 1,
    "old_status": "planned",
    "new_status": "in_progress",
    "reason": null,
    "changed_by": 1,
    "changed_at": "2026-02-10T08:30:00",
    "user": {
      "id": 1,
      "name": "Manager Default",
      "email": "manager@example.com"
    }
  },
  {
    "id": 2,
    "old_status": "in_progress",
    "new_status": "completed",
    "changed_by": 1,
    "changed_at": "2026-02-25T16:30:00"
  }
]
```

---

## 📸 Tests Photos

### 8️⃣ Uploader une photo (POST /api/roadworks/{id}/photos)

**URL:**
```
{{baseUrl}}/api/roadworks/{{roadwork_id}}/photos
```

**Method:** POST

**Headers:**
```
Authorization: Bearer {{token}}
```

**Body (form-data):**
| Key | Value |
|-----|-------|
| photo_type | before |
| description | État initial du chantier |
| taken_at | 2026-02-10T08:00:00 |
| file | [Sélectionner une image] |

**✅ Response (201):**
```json
{
  "id": 1,
  "roadwork_id": 1,
  "photo_url": "http://localhost:8000/storage/roadwork_photos/abc123def456.jpg",
  "photo_path": "roadwork_photos/abc123def456.jpg",
  "photo_type": "before",
  "description": "État initial du chantier",
  "taken_at": "2026-02-10T08:00:00",
  "uploaded_by": 1,
  "uploader": {
    "id": 1,
    "name": "Manager Default",
    "email": "manager@example.com"
  },
  "created_at": "2026-02-10T10:15:00"
}
```

**💡 Action après réception:**
- Copier l'`id`
- Mettre à jour la variable `photo_id` dans l'environnement

---

### 9️⃣ Lister les photos d'un Roadwork (GET /api/roadworks/{id}/photos)

**URL:**
```
{{baseUrl}}/api/roadworks/{{roadwork_id}}/photos
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
[
  {
    "id": 1,
    "roadwork_id": 1,
    "photo_url": "http://localhost:8000/storage/roadwork_photos/abc123.jpg",
    "photo_type": "before",
    "description": "État initial du chantier",
    "taken_at": "2026-02-10T08:00:00",
    "uploader": {
      "id": 1,
      "name": "Manager Default"
    }
  },
  {
    "id": 2,
    "photo_type": "during",
    "description": "Travaux en cours"
  }
]
```

---

### 🔟 Obtenir une photo (GET /api/roadwork-photos/{id})

**URL:**
```
{{baseUrl}}/api/roadwork-photos/{{photo_id}}
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

---

### 1️⃣1️⃣ Supprimer une photo (DELETE /api/roadwork-photos/{id})

**URL:**
```
{{baseUrl}}/api/roadwork-photos/{{photo_id}}
```

**Method:** DELETE

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "message": "Photo supprimée avec succès"
}
```

---

## 🔔 Tests Notifications

### 1️⃣2️⃣ Obtenir les notifications (GET /api/notifications)

**URL:**
```
{{baseUrl}}/api/notifications
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**Query Parameters (optionnels):**
```
filter = unread | read | all
```

**✅ Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "title": "Roadwork démarré",
      "message": "Les travaux sur 'Réparation Route Principale' ont commencé",
      "type": "info",
      "is_read": false,
      "read_at": null,
      "created_at": "2026-02-10T08:30:00"
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 20
  }
}
```

---

### 1️⃣3️⃣ Compter les notifications non lues (GET /api/notifications/unread-count)

**URL:**
```
{{baseUrl}}/api/notifications/unread-count
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "unread_count": 3
}
```

---

### 1️⃣4️⃣ Marquer comme lue (PUT /api/notifications/{id}/read)

**URL:**
```
{{baseUrl}}/api/notifications/1/read
```

**Method:** PUT

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "id": 1,
  "is_read": true,
  "read_at": "2026-02-10T10:20:00"
}
```

---

### 1️⃣5️⃣ Marquer toutes comme lues (PUT /api/notifications/mark-all-as-read)

**URL:**
```
{{baseUrl}}/api/notifications/mark-all-as-read
```

**Method:** PUT

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "message": "Toutes les notifications sont maintenant lues"
}
```

---

## 🔥 Tests Firebase Tokens

### 1️⃣6️⃣ Enregistrer un token Firebase (POST /api/firebase/register-token)

**URL:**
```
{{baseUrl}}/api/firebase/register-token
```

**Method:** POST

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {{token}}
```

**Body (JSON):**
```json
{
  "token": "erZF3dqSfU0:APA91bF2x1y9z0abc123def456ghi789jkl",
  "device_name": "iPhone 12 Pro",
  "device_id": "device_ios_unique_123",
  "metadata": {
    "os": "iOS",
    "os_version": "15.4",
    "app_version": "1.0.0",
    "manufacturer": "Apple"
  }
}
```

**✅ Response (201):**
```json
{
  "id": 1,
  "user_id": 1,
  "token": "erZF3dqSfU0:APA91bF...",
  "device_name": "iPhone 12 Pro",
  "device_id": "device_ios_unique_123",
  "is_active": true,
  "last_used_at": null,
  "metadata": {
    "os": "iOS",
    "os_version": "15.4"
  },
  "created_at": "2026-02-10T10:25:00"
}
```

---

### 1️⃣7️⃣ Lister tous les tokens (GET /api/firebase/tokens)

**URL:**
```
{{baseUrl}}/api/firebase/tokens
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
[
  {
    "id": 1,
    "device_name": "iPhone 12 Pro",
    "is_active": true,
    "last_used_at": "2026-02-10T10:30:00",
    "created_at": "2026-02-10T10:25:00"
  },
  {
    "id": 2,
    "device_name": "Samsung Galaxy S21",
    "is_active": true,
    "last_used_at": null,
    "created_at": "2026-02-09T15:45:00"
  }
]
```

---

### 1️⃣8️⃣ Lister tokens actifs (GET /api/firebase/tokens/active)

**URL:**
```
{{baseUrl}}/api/firebase/tokens/active
```

**Method:** GET

**Headers:**
```
Authorization: Bearer {{token}}
```

---

### 1️⃣9️⃣ Désactiver un token (PUT /api/firebase/tokens/{id}/deactivate)

**URL:**
```
{{baseUrl}}/api/firebase/tokens/1/deactivate
```

**Method:** PUT

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "id": 1,
  "is_active": false,
  "updated_at": "2026-02-10T10:35:00"
}
```

---

### 2️⃣0️⃣ Supprimer un token (DELETE /api/firebase/tokens/{id})

**URL:**
```
{{baseUrl}}/api/firebase/tokens/1
```

**Method:** DELETE

**Headers:**
```
Authorization: Bearer {{token}}
```

**✅ Response (200):**
```json
{
  "message": "Token supprimé avec succès"
}
```

---

## 🎯 Workflow complet - Test E2E

Suivez cette séquence pour tester le flux complet:

1. **Login** (Étape 1) → Copier le token
2. **Créer Roadwork** (Étape 2) → Copier l'ID
3. **Obtenir détails** (Étape 4) → Vérifier le statut "planned"
4. **Uploader photo avant** (Étape 8) → Sélectionner une image
5. **Démarrer travaux** (Étape 5) → Changer statut à "in_progress"
6. **Obtenir historique** (Étape 7) → Vérifier le changement
7. **Uploader photo pendant** (Étape 8) → Ajouter une 2e photo
8. **Enregistrer token Firebase** (Étape 16)
9. **Lister tokens** (Étape 17) → Vérifier le token
10. **Obtenir notifications** (Étape 12) → Vérifier les notifications
11. **Terminer travaux** (Étape 6) → Changer statut à "completed"
12. **Uploader photo après** (Étape 8) → Ajouter une 3e photo

---

## ⚠️ Codes d'erreur courants

| Code | Cause | Solution |
|------|-------|----------|
| 401 | Non authentifié | Copier le token du login |
| 403 | Non autorisé | Vérifier le rôle (manager needed) |
| 404 | Ressource non trouvée | Vérifier l'ID du roadwork/photo |
| 422 | Données invalides | Vérifier le format JSON/dates |
| 500 | Erreur serveur | Vérifier les logs Laravel |

---

## 💡 Tips & Tricks

✅ **Sauvegarder les réponses dans les variables:**
```javascript
// Dans l'onglet Tests de Postman
var jsonData = pm.response.json();
pm.environment.set("roadwork_id", jsonData.id);
pm.environment.set("token", jsonData.token);
```

✅ **Tester les relations:**
```
GET {{baseUrl}}/api/roadworks/{{roadwork_id}}?include=photos,statusHistory,creator
```

✅ **Paginer les résultats:**
```
GET {{baseUrl}}/api/roadworks?page=1&per_page=10
```

✅ **Filtrer les notifications:**
```
GET {{baseUrl}}/api/notifications?filter=unread
```

---

## 🚀 Exécuter les migrations

Avant de tester, assurez-vous d'exécuter les migrations:

```bash
cd laravel/laravel/my-app
php artisan migrate
php artisan db:seed
```

Maintenant vous pouvez commencer à tester! 🎉
