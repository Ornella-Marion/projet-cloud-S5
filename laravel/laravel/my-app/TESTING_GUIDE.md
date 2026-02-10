# 📋 Guide de Test des Nouvelles Entités - Postman

## 🚀 Étapes préalables

### 1. Exécuter les migrations
```bash
cd laravel/my-app
php artisan migrate
```

### 2. Exécuter les seeders (données de test)
```bash
php artisan db:seed
```

### 3. Obtenir un token d'authentification
```bash
# POST /api/auth/login
{
  "email": "manager@example.com",
  "password": "manager123"
}
```

Sauvegardez le token reçu.

---

## 🛣️ Tests - Roadworks API

### 1️⃣ Créer un Roadwork

**POST** `http://localhost:8000/api/roadworks`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "title": "Réparation route principale",
  "description": "Réparation complète de la chaussée",
  "location": "Rue de la Paix, Zurich",
  "latitude": 47.3769,
  "longitude": 8.5472,
  "status": "planned",
  "planned_start_date": "2026-02-15T09:00:00",
  "planned_end_date": "2026-02-28T17:00:00",
  "notes": "Travaux prioritaires"
}
```

**Response:**
```json
{
  "id": 1,
  "title": "Réparation route principale",
  "status": "planned",
  "created_at": "2026-02-10T10:00:00",
  "created_by": 1
}
```

---

### 2️⃣ Lister tous les Roadworks

**GET** `http://localhost:8000/api/roadworks`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 3️⃣ Obtenir un Roadwork spécifique

**GET** `http://localhost:8000/api/roadworks/1`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 4️⃣ Mettre à jour le statut (Démarrer les travaux)

**PUT** `http://localhost:8000/api/roadworks/1`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "status": "in_progress",
  "started_at": "2026-02-10T08:30:00"
}
```

---

## 📸 Tests - Roadwork Photos API

### 1️⃣ Uploader une photo

**POST** `http://localhost:8000/api/roadworks/1/photos`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data
```

**Body (form-data):**
```
roadwork_id: 1
photo_type: before (ou during, after, issue)
description: État initial du site
taken_at: 2026-02-10T08:00:00
file: [Sélectionner une image]
```

**Response:**
```json
{
  "id": 1,
  "roadwork_id": 1,
  "photo_url": "http://localhost:8000/storage/roadwork_photos/123.jpg",
  "photo_type": "before",
  "uploaded_by": 1,
  "created_at": "2026-02-10T10:00:00"
}
```

---

### 2️⃣ Lister les photos d'un Roadwork

**GET** `http://localhost:8000/api/roadworks/1/photos`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 3️⃣ Obtenir une photo spécifique

**GET** `http://localhost:8000/api/roadwork-photos/1`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

## 📝 Tests - Status History API

### 1️⃣ Obtenir l'historique des changements de statut

**GET** `http://localhost:8000/api/roadworks/1/status-history`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
[
  {
    "id": 1,
    "roadwork_id": 1,
    "old_status": "planned",
    "new_status": "in_progress",
    "reason": "Équipe disponible, démarrage des travaux",
    "changed_by": 1,
    "changed_at": "2026-02-10T08:30:00",
    "user": {
      "id": 1,
      "name": "Manager Default",
      "email": "manager@example.com"
    }
  }
]
```

---

### 2️⃣ Créer un changement de statut manuellement

**POST** `http://localhost:8000/api/roadworks/1/status-change`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "new_status": "paused",
  "reason": "Mauvais temps, pause des travaux"
}
```

---

## 🔔 Tests - Notifications API

### 1️⃣ Obtenir les notifications de l'utilisateur

**GET** `http://localhost:8000/api/notifications`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "title": "Roadwork démarré",
    "message": "Les travaux sur 'Réparation route principale' ont commencé",
    "type": "info",
    "is_read": false,
    "created_at": "2026-02-10T08:30:00"
  }
]
```

---

### 2️⃣ Obtenir les notifications non lues

**GET** `http://localhost:8000/api/notifications?filter=unread`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 3️⃣ Marquer une notification comme lue

**PUT** `http://localhost:8000/api/notifications/1/read`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 4️⃣ Envoyer une notification à un utilisateur

**POST** `http://localhost:8000/api/notifications/send`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "user_id": 2,
  "title": "Alerte",
  "message": "Une action est requise",
  "type": "warning"
}
```

---

## 🔥 Tests - Firebase Tokens API

### 1️⃣ Enregistrer un token Firebase

**POST** `http://localhost:8000/api/firebase/register-token`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "token": "erZF3dqSfU0:APA91bF2x1y9z0abc123def456ghi789",
  "device_name": "iPhone 12",
  "device_id": "device_ios_123",
  "metadata": {
    "os": "iOS",
    "version": "15.4",
    "app_version": "1.0.0"
  }
}
```

**Response:**
```json
{
  "id": 1,
  "user_id": 1,
  "device_name": "iPhone 12",
  "device_id": "device_ios_123",
  "is_active": true,
  "created_at": "2026-02-10T10:00:00"
}
```

---

### 2️⃣ Obtenir tous les tokens Firebase de l'utilisateur

**GET** `http://localhost:8000/api/firebase/tokens`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 3️⃣ Obtenir les tokens actifs seulement

**GET** `http://localhost:8000/api/firebase/tokens/active`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 4️⃣ Désactiver un token

**PUT** `http://localhost:8000/api/firebase/tokens/1/deactivate`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

### 5️⃣ Supprimer un token

**DELETE** `http://localhost:8000/api/firebase/tokens/1`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

---

## 🧪 Test Complet - Workflow Complet

### Scénario: Créer un Roadwork, ajouter des photos, et gérer les notifications

1. **Créer un Roadwork** (POST /api/roadworks)
2. **Uploader une photo avant** (POST /api/roadworks/1/photos)
3. **Démarrer les travaux** (PUT /api/roadworks/1) - status: in_progress
4. **Uploader une photo pendant** (POST /api/roadworks/1/photos)
5. **Consulter l'historique** (GET /api/roadworks/1/status-history)
6. **Enregistrer un token Firebase** (POST /api/firebase/register-token)
7. **Vérifier les notifications** (GET /api/notifications)
8. **Terminer les travaux** (PUT /api/roadworks/1) - status: completed
9. **Uploader une photo après** (POST /api/roadworks/1/photos)

---

## ⚠️ Codes d'erreur attendus

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 201 | Créé |
| 400 | Données invalides |
| 401 | Non authentifié |
| 403 | Non autorisé |
| 404 | Ressource non trouvée |
| 422 | Erreur de validation |
| 500 | Erreur serveur |

---

## 💡 Tips Postman

- ✅ Sauvegarder le token dans une variable: `{{token}}`
- ✅ Utiliser `{{baseUrl}}` pour la base de l'URL
- ✅ Tester les relations en utilisant le paramètre `?include=photos,statusHistory`
- ✅ Utiliser les Collections pour organiser les tests
