# 📊 GUIDE POSTMAN - TESTER LES NOUVELLES FONCTIONNALITÉS

## 🎯 Nouvelles fonctionnalités ajoutées

✅ **Statistiques des délais de traitement**
✅ **Enregistrement automatique de started_at et finished_at**
✅ **Création automatique de status_history**
✅ **Routes de photos (upload multiple)**

---

## 🚀 WORKFLOW COMPLET DE TEST

### **Votre Token:**
```
1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
```

---

## 📋 TEST 1: Créer 3 Roadworks pour les statistiques

### **REQUEST 1A: Créer Roadwork #1**

```
Method: POST
URL: http://localhost:8000/api/roadworks
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "title": "Réparation Route Zurich - Phase 1",
  "description": "Réparation complète de la chaussée",
  "location": "Zurich, Switzerland",
  "latitude": 47.3769,
  "longitude": 8.5472,
  "status": "planned",
  "planned_start_date": "2026-02-15T09:00:00",
  "planned_end_date": "2026-02-28T17:00:00"
}
```

✅ **Response:** Vous recevrez `id: 1`

---

### **REQUEST 1B: Créer Roadwork #2**

```
Method: POST
URL: http://localhost:8000/api/roadworks
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "title": "Réparation Route Berne - Phase 2",
  "description": "Travaux de maintenance",
  "location": "Berne, Switzerland",
  "latitude": 46.9479,
  "longitude": 7.4474,
  "status": "planned",
  "planned_start_date": "2026-02-20T10:00:00",
  "planned_end_date": "2026-03-10T17:00:00"
}
```

✅ **Response:** Vous recevrez `id: 2`

---

### **REQUEST 1C: Créer Roadwork #3**

```
Method: POST
URL: http://localhost:8000/api/roadworks
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "title": "Réparation Route Genève - Phase 3",
  "description": "Réfection complète",
  "location": "Genève, Switzerland",
  "latitude": 46.2044,
  "longitude": 6.1432,
  "status": "planned",
  "planned_start_date": "2026-02-18T08:00:00",
  "planned_end_date": "2026-03-05T17:00:00"
}
```

✅ **Response:** Vous recevrez `id: 3`

---

## 🏗️ TEST 2: Tester l'enregistrement automatique de started_at

### **REQUEST 2A: Démarrer le Roadwork #1 (started_at sera auto-enregistré)**

```
Method: PUT
URL: http://localhost:8000/api/roadworks/1
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "status": "in_progress"
}
```

✅ **Response attendue:**
```json
{
  "id": 1,
  "status": "in_progress",
  "started_at": "2026-02-10T10:30:00",  ← Auto-enregistré!
  "statusHistory": [
    {
      "id": 1,
      "old_status": "planned",
      "new_status": "in_progress",
      "changed_by": 1,
      "changed_at": "2026-02-10T10:30:00"
    }
  ]
}
```

---

### **REQUEST 2B: Démarrer le Roadwork #2**

```
Method: PUT
URL: http://localhost:8000/api/roadworks/2
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "status": "in_progress"
}
```

---

### **REQUEST 2C: Démarrer le Roadwork #3**

```
Method: PUT
URL: http://localhost:8000/api/roadworks/3
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "status": "in_progress"
}
```

---

## ✅ TEST 3: Tester l'enregistrement automatique de completed_at

### **REQUEST 3A: Terminer le Roadwork #1 (finished_at sera auto-enregistré)**

```
Method: PUT
URL: http://localhost:8000/api/roadworks/1
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "status": "completed"
}
```

✅ **Response attendue:**
```json
{
  "id": 1,
  "status": "completed",
  "started_at": "2026-02-10T10:30:00",
  "completed_at": "2026-02-10T11:00:00",  ← Auto-enregistré!
  "statusHistory": [
    {
      "id": 1,
      "old_status": "planned",
      "new_status": "in_progress",
      "changed_at": "2026-02-10T10:30:00"
    },
    {
      "id": 2,
      "old_status": "in_progress",
      "new_status": "completed",
      "changed_at": "2026-02-10T11:00:00"
    }
  ]
}
```

---

### **REQUEST 3B: Terminer le Roadwork #2**

```
Method: PUT
URL: http://localhost:8000/api/roadworks/2
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
Header: Content-Type: application/json

Body (JSON):
{
  "status": "completed"
}
```

---

## 📸 TEST 4: Upload multiple photos

### **REQUEST 4A: Upload Photo 1 du Roadwork #1**

```
Method: POST
URL: http://localhost:8000/api/roadworks/1/photos
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17

Body (form-data):
- photo_type: before
- description: État initial du chantier
- file: [Sélectionner une image]
```

---

### **REQUEST 4B: Upload Photo 2 du Roadwork #1**

```
Method: POST
URL: http://localhost:8000/api/roadworks/1/photos
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17

Body (form-data):
- photo_type: during
- description: Travaux en cours
- file: [Sélectionner une image]
```

---

### **REQUEST 4C: Upload Photo 3 du Roadwork #1**

```
Method: POST
URL: http://localhost:8000/api/roadworks/1/photos
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17

Body (form-data):
- photo_type: after
- description: Travaux terminés
- file: [Sélectionner une image]
```

---

### **REQUEST 4D: Lister les photos du Roadwork #1**

```
Method: GET
URL: http://localhost:8000/api/roadworks/1/photos
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
```

✅ **Response attendue:**
```json
[
  {
    "id": 1,
    "photo_type": "before",
    "description": "État initial du chantier",
    "photo_url": "http://localhost:8000/storage/roadwork_photos/xxx.jpg"
  },
  {
    "id": 2,
    "photo_type": "during",
    "description": "Travaux en cours",
    "photo_url": "http://localhost:8000/storage/roadwork_photos/yyy.jpg"
  },
  {
    "id": 3,
    "photo_type": "after",
    "description": "Travaux terminés",
    "photo_url": "http://localhost:8000/storage/roadwork_photos/zzz.jpg"
  }
]
```

---

## 📊 TEST 5: Tester les statistiques

### **REQUEST 5A: Délais moyens de traitement**

```
Method: GET
URL: http://localhost:8000/api/statistics/average-delay
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
```

✅ **Response attendue:**
```json
{
  "total_roadworks": 3,
  "completed_roadworks": 2,
  "in_progress_roadworks": 1,
  "planned_roadworks": 0,
  "delays": {
    "planned_to_in_progress": {
      "average_hours": 0.5,
      "average_days": 0.02,
      "min_hours": 0,
      "max_hours": 1,
      "count": 3,
      "details": [...]
    },
    "in_progress_to_completed": {
      "average_hours": 0.5,
      "average_days": 0.02,
      "min_hours": 0,
      "max_hours": 1,
      "count": 2,
      "details": [...]
    },
    "planned_to_completed": {
      "average_hours": 1,
      "average_days": 0.04,
      "min_hours": 1,
      "max_hours": 1,
      "count": 2,
      "details": [...]
    }
  }
}
```

---

### **REQUEST 5B: Délais par localisation**

```
Method: GET
URL: http://localhost:8000/api/statistics/delay-by-location
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
```

✅ **Response attendue:**
```json
{
  "Zurich, Switzerland": {
    "total_roadworks": 1,
    "completed_roadworks": 1,
    "average_delay_hours": 0.5,
    "average_delay_days": 0.02
  },
  "Berne, Switzerland": {
    "total_roadworks": 1,
    "completed_roadworks": 1,
    "average_delay_hours": 0.5,
    "average_delay_days": 0.02
  },
  "Genève, Switzerland": {
    "total_roadworks": 1,
    "completed_roadworks": 0,
    "average_delay_hours": 0,
    "average_delay_days": 0
  }
}
```

---

### **REQUEST 5C: Résumé statistiques globales**

```
Method: GET
URL: http://localhost:8000/api/statistics/summary
Header: Authorization: Bearer 1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17
```

✅ **Response attendue:**
```json
{
  "total_roadworks": 3,
  "status_breakdown": {
    "planned": 0,
    "in_progress": 1,
    "completed": 2,
    "paused": 0
  },
  "total_photos": 3,
  "average_photos_per_roadwork": 1
}
```

---

## 🎯 Ordre de test recommandé

1. ✅ **REQUEST 1A, 1B, 1C** - Créer 3 roadworks
2. ✅ **REQUEST 2A, 2B, 2C** - Démarrer les travaux (started_at auto)
3. ✅ **REQUEST 3A, 3B** - Terminer certains travaux (completed_at auto)
4. ✅ **REQUEST 4A, 4B, 4C, 4D** - Upload photos et lister
5. ✅ **REQUEST 5A, 5B, 5C** - Tester statistiques

---

## ✅ Vérifications clés

### **Vérifier que started_at est auto-enregistré:**
- REQUEST 2A → Vérifier que `started_at` a une valeur ✅

### **Vérifier que completed_at est auto-enregistré:**
- REQUEST 3A → Vérifier que `completed_at` a une valeur ✅

### **Vérifier que status_history est créée:**
- REQUEST 3A → Vérifier que `statusHistory` a des entrées ✅

### **Vérifier les photos multiples:**
- REQUEST 4D → Vérifier 3 photos dans la liste ✅

### **Vérifier les statistiques:**
- REQUEST 5A → Vérifier les délais calculés ✅
- REQUEST 5B → Vérifier les délais par localisation ✅
- REQUEST 5C → Vérifier le résumé global ✅

---

## 🔧 Troubleshooting

| Erreur | Solution |
|--------|----------|
| 401 Unauthorized | Vérifier le token |
| 404 Not Found | Vérifier que l'ID roadwork existe |
| 422 Validation Error | Vérifier le format JSON |
| Photo non uploadée | Sélectionner une vraie image (format: jpg, png) |

---

## 📱 À tester dans Postman maintenant!

1. Copiez le token: `1|firbisYqGHMsn2GW0KFX7IFXxL9WzHJwg7PcLsDy1c2edc17`
2. Suivez le workflow complet ci-dessus
3. Vérifiez chaque réponse
4. Les statistiques doivent être cohérentes!

🎉 Vous êtes prêt à tester!
