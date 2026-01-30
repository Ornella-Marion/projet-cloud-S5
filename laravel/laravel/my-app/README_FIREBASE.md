# Firebase Integration - Configuration Guide

## 📋 Vue d'ensemble

Cette documentation couvre la configuration complète et l'intégration de Firebase dans l'application Cloud-S5.

## 📁 Fichiers créés

### Configuration
- **`.env`** - Variables d'environnement (mise à jour avec les credentials Firebase)
- **`.env.firebase`** - Modèle complet avec exemples et documentation
- **`config/firebase.php`** - Configuration centralisée de Firebase

### Services
- **`app/Services/FirebaseService.php`** - Service centralisé pour Firebase
- **`app/Providers/FirebaseServiceProvider.php`** - Provider pour l'injection de dépendance
- **`app/Http/Controllers/Api/FirebaseExampleController.php`** - Exemples d'utilisation

### Documentation
- **`FIREBASE.md`** - Guide complet de configuration et utilisation
- **`README_FIREBASE.md`** - Ce fichier

## 🚀 Quick Start

### 1. Configurer les variables d'environnement

Copier les valeurs depuis Firebase Console:

```bash
# Variables obligatoires
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
```

### 2. Utiliser le service Firebase

```php
use App\Services\FirebaseService;

class MyController {
    public function __construct(private FirebaseService $firebase) {}
    
    public function index() {
        if ($this->firebase->isConfigured()) {
            // Votre code ici
        }
    }
}
```

### 3. Consulter la documentation complète

Voir `FIREBASE.md` pour:
- Configuration détaillée des credentials
- Activation des services (Firestore, Realtime DB, etc.)
- Exemples complets pour chaque service
- Dépannage

## 📦 Contenu des fichiers

### config/firebase.php
Configuration centralisée avec:
- Credentials du projet (API Key, Project ID, etc.)
- Configuration Admin SDK (Service Account)
- Feature flags pour chaque service
- Paramètres de sécurité et performance
- Configuration du cache

**Accès dans le code:**
```php
$projectId = config('firebase.project_id');
$isFirestoreEnabled = config('firebase.services.firestore');
```

### app/Services/FirebaseService.php
Service centralisé avec méthodes:

```php
// Configuration
$firebase->isConfigured(): bool
$firebase->getConfig(): array
$firebase->getProjectId(): ?string

// Vérification des services
$firebase->isServiceEnabled(string $service): bool

// Clients SDK
$firebase->getAdminClient()
$firebase->getRealtimeDatabaseClient()
$firebase->getFirestoreClient()
$firebase->getStorageClient()
$firebase->getMessagingClient()

// Logging
$firebase->log(string $action, array $context = [])
$firebase->logError(string $action, string $error, array $context = [])
```

### app/Providers/FirebaseServiceProvider.php
Enregistre automatiquement:
- `FirebaseService` comme singleton dans le conteneur
- Configuration pour injection de dépendance
- Alertes en développement si non configuré

### app/Http/Controllers/Api/FirebaseExampleController.php
Exemples complets de:
- Vérification de configuration
- Écriture/lecture Firestore
- Écriture/lecture Realtime Database
- Envoi de notifications push
- Upload vers Cloud Storage

## 🔧 Variables d'environnement

### Web/Client Configuration
```dotenv
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-web-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id
FIREBASE_MEASUREMENT_ID=your-measurement-id
```

### Admin SDK (Service Account)
```dotenv
FIREBASE_PRIVATE_KEY_ID=your-private-key-id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@project.iam.gserviceaccount.com
```

### Activation des services
```dotenv
FIREBASE_ENABLE_FIRESTORE=false           # Cloud Firestore
FIREBASE_ENABLE_REALTIME_DB=false         # Realtime Database
FIREBASE_ENABLE_STORAGE=false             # Cloud Storage
FIREBASE_ENABLE_PUSH_NOTIFICATIONS=false  # Cloud Messaging
FIREBASE_ENABLE_ANALYTICS=false           # Analytics
```

## 📚 Exemples d'utilisation

### Vérifier la configuration
```php
$firebase = app(FirebaseService::class);

if (!$firebase->isConfigured()) {
    return response()->json(['error' => 'Firebase not configured'], 500);
}
```

### Utiliser Firestore
```php
if ($firebase->isServiceEnabled('firestore')) {
    $firestore = $firebase->getFirestoreClient();
    
    // Écrire
    $firestore->collection('users')->document('user123')->set([
        'name' => 'John Doe',
    ]);
    
    // Lire
    $doc = $firestore->collection('users')->document('user123')->snapshot();
    $data = $doc->data();
}
```

### Utiliser Realtime Database
```php
if ($firebase->isServiceEnabled('realtime_db')) {
    $database = $firebase->getRealtimeDatabaseClient();
    
    // Écrire
    $database->getReference('users/user123')->set(['name' => 'John']);
    
    // Lire
    $data = $database->getReference('users/user123')->getValue();
}
```

### Envoyer une notification push
```php
if ($firebase->isServiceEnabled('push_notifications')) {
    $messaging = $firebase->getMessagingClient();
    
    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget(
        'token',
        $deviceToken
    )->withNotification(
        \Kreait\Firebase\Messaging\Notification::create('Title', 'Message')
    );
    
    $messaging->send($message);
}
```

## 🔐 Sécurité

### Points importants

1. **Credentials dans .env**
   - Ne jamais commiter `.env` avec les vraies valeurs
   - Utiliser des secrets/variables en production

2. **Service Account**
   - Stocker le fichier JSON de manière sécurisée
   - Régénérer régulièrement les clés

3. **Règles Firebase**
   - Implémenter les règles de sécurité dans Firebase Console
   - Ne pas utiliser le mode "test" en production

4. **Validation**
   - Valider les données côté serveur
   - Implémenter l'authentification appropriée

## 🧪 Tests

### Vérifier la configuration
```bash
docker-compose exec app php -l config/firebase.php
docker-compose exec app php -l app/Services/FirebaseService.php
docker-compose exec app php -l app/Providers/FirebaseServiceProvider.php
```

### Test d'intégration
- Utiliser `FirebaseExampleController` pour tester les connexions
- Vérifier les logs pour les erreurs: `storage/logs/laravel.log`

## 📖 Documentation complète

Consultez `FIREBASE.md` pour:
- Configuration détaillée pas à pas
- Guide complet des credentials
- Tous les exemples d'utilisation
- Dépannage et troubleshooting
- Gestion multi-environnements

## ✅ Checklist de configuration

- [ ] Créer un projet Firebase ou utiliser un existant
- [ ] Ajouter les Web Credentials au `.env`
- [ ] Générer le Service Account JSON
- [ ] Ajouter les credentials Admin SDK au `.env`
- [ ] Activer les services nécessaires dans `FIREBASE_ENABLE_*`
- [ ] Installer `kreait/firebase-php` si nécessaire
- [ ] Tester la connexion avec `FirebaseExampleController`
- [ ] Configurer les règles de sécurité dans Firebase Console
- [ ] Documenter la configuration pour votre équipe

## 🔗 Ressources

- [Firebase Documentation](https://firebase.google.com/docs)
- [Firebase Console](https://console.firebase.google.com)
- [kreait/firebase-php](https://github.com/kreait/firebase-php)
- [Fichier complet: FIREBASE.md](./FIREBASE.md)

## 📞 Support

Pour des questions spécifiques:
1. Consulter `FIREBASE.md`
2. Vérifier les logs: `storage/logs/laravel.log`
3. Vérifier la configuration dans `.env`
4. Consulter la documentation Firebase officielle

---

**Date de création:** 26 janvier 2026
**Version:** 1.0
**Framework:** Laravel 11
**PHP:** 8.2+
