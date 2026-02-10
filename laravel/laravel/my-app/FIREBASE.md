# Firebase Integration Guide

## Configuration Firebase

Ce guide explique comment configurer Firebase pour l'application Cloud-S5.

## 1. Prérequis

### Compte Firebase
1. Aller sur [Firebase Console](https://console.firebase.google.com)
2. Créer un nouveau projet ou utiliser un projet existant
3. Activer les services nécessaires (Realtime Database, Firestore, Storage, etc.)

### Package PHP
```bash
composer require kreait/firebase-php
```

## 2. Variables d'environnement (.env)

### Configuration de base
```dotenv
# Firebase Configuration
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_DATABASE_URL=https://cloud-s5-30351-default-rtdb.europe-west1 firebasedatabase.app
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id
FIREBASE_MEASUREMENT_ID=your-measurement-id
```

### Service Account (Admin SDK)
Pour utiliser le SDK Admin Firebase côté serveur:

```dotenv
# Obtenir ces valeurs depuis Firebase Console > Project Settings > Service Accounts
FIREBASE_PRIVATE_KEY_ID=your-private-key-id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com
```

### Alternative: Fichier JSON de credentials
```dotenv
FIREBASE_CREDENTIALS_JSON_PATH=/path/to/firebase-credentials.json
```

### Activation des services
```dotenv
FIREBASE_ENABLE_REALTIME_DB=false       # Si vous utilisez Realtime Database
FIREBASE_ENABLE_FIRESTORE=false         # Si vous utilisez Firestore
FIREBASE_ENABLE_STORAGE=false           # Si vous utilisez Cloud Storage
FIREBASE_ENABLE_PUSH_NOTIFICATIONS=false # Si vous utilisez Cloud Messaging
FIREBASE_ENABLE_ANALYTICS=false         # Si vous utilisez Analytics
```

## 3. Obtenir les credentials Firebase

### Depuis Firebase Console:

1. **Allez à Firebase Console**
   ```
   https://console.firebase.google.com/project/[votre-projet-id]
   
   ```

2. **Pour les Web Credentials:**
   - Cliquez sur "Project Settings" (⚙️)
   - Allez à l'onglet "General"
   - Copiez les valeurs de configuration

3. **Pour le Service Account (Admin SDK):**
   - Cliquez sur "Project Settings" (⚙️)
   - Allez à l'onglet "Service Accounts"
   - Cliquez "Generate New Private Key"
   - Téléchargez le fichier JSON
   - Copiez les valeurs dans .env ou utilisez le chemin du fichier

## 4. Fichiers de configuration

### config/firebase.php
Fichier de configuration centralisé avec tous les paramètres Firebase.

**Accès dans l'application:**
```php
$config = config('firebase');
$projectId = config('firebase.project_id');
$isFirestoreEnabled = config('firebase.services.firestore');
```

## 5. Service Firebase

### Classe: App\Services\FirebaseService

Service centralisé pour gérer les interactions avec Firebase.

**Utilisation basique:**
```php
use App\Services\FirebaseService;

class MyController {
    public function __construct(private FirebaseService $firebase) {}
    
    public function index() {
        // Vérifier si Firebase est configuré
        if (!$this->firebase->isConfigured()) {
            return response()->json(['error' => 'Firebase not configured'], 500);
        }
        
        // Obtenir l'ID du projet
        $projectId = $this->firebase->getProjectId();
        
        // Vérifier si un service est activé
        if ($this->firebase->isServiceEnabled('firestore')) {
            $firestoreClient = $this->firebase->getFirestoreClient();
            // Utiliser le client Firestore
        }
    }
}
```

### Méthodes disponibles:

```php
// Vérification et configuration
$firebase->isConfigured(): bool
$firebase->getConfig(): array
$firebase->getProjectId(): ?string
$firebase->getApiKey(): ?string
$firebase->getAuthDomain(): ?string
$firebase->getStorageBucket(): ?string
$firebase->getDatabaseUrl(): ?string

// Services
$firebase->isServiceEnabled(string $service): bool
$firebase->getAdminClient()
$firebase->getRealtimeDatabaseClient()
$firebase->getFirestoreClient()
$firebase->getStorageClient()
$firebase->getMessagingClient()

// Logging
$firebase->log(string $action, array $context = []): void
$firebase->logError(string $action, string $error, array $context = []): void
```

## 6. Exemples d'utilisation

### Utiliser Firestore
```php
$firebase = app(FirebaseService::class);

if ($firebase->isServiceEnabled('firestore')) {
    $firestore = $firebase->getFirestoreClient();
    
    // Ajouter un document
    $firestore->collection('users')->document('user123')->set([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    // Lire un document
    $doc = $firestore->collection('users')->document('user123')->snapshot();
    $data = $doc->data();
}
```

### Utiliser Realtime Database
```php
if ($firebase->isServiceEnabled('realtime_db')) {
    $database = $firebase->getRealtimeDatabaseClient();
    
    // Écrire des données
    $database->getReference('users/user123')->set([
        'name' => 'John Doe',
        'online' => true,
    ]);
    
    // Lire des données
    $data = $database->getReference('users/user123')->getValue();
}
```

### Envoyer des notifications push
```php
if ($firebase->isServiceEnabled('push_notifications')) {
    $messaging = $firebase->getMessagingClient();
    
    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', 'device-token')
        ->withNotification(\Kreait\Firebase\Messaging\Notification::create('Title', 'Message'))
        ->withData(['key' => 'value']);
    
    $messaging->send($message);
}
```

## 7. Sécurité

### Points importants:

1. **Ne jamais commiter les credentials:**
   ```
   # .gitignore
   *.json
   .env
   .env.local
   ```

2. **Utiliser les variables d'environnement:**
   - Utiliser `.env` en développement
   - Utiliser les secrets des déploiements en production (AWS Secrets Manager, Azure Key Vault, etc.)

3. **Permissions Firestore/Realtime Database:**
   - Implémenter les règles de sécurité appropriées dans Firebase Console
   - Ne pas utiliser les mode "test" en production

4. **Service Account:**
   - Limiter les permissions du service account
   - Régulièrement régénérer les clés
   - Utiliser des rôles IAM restreints

## 8. Dépannage

### Erreur: "Firebase is not properly configured"
- Vérifier que `FIREBASE_PROJECT_ID` et `FIREBASE_API_KEY` sont définis dans `.env`
- Vérifier que le fichier `.env` est chargé

### Erreur: "kreait/firebase-php package is not installed"
```bash
composer require kreait/firebase-php
```

### Erreur de credentials
- Vérifier que le fichier JSON est accessible
- Vérifier que `FIREBASE_CREDENTIALS_JSON_PATH` pointe vers le bon chemin
- Vérifier les permissions du fichier

### Timeout ou connexion lente
- Vérifier la configuration du timeout: `FIREBASE_TIMEOUT`
- Vérifier la taille du pool de connexions: `FIREBASE_POOL_SIZE`
- Vérifier la connectivité réseau

## 9. Environnements multi

### Exemple pour différents environnements:

**.env.local (développement)**
```dotenv
FIREBASE_PROJECT_ID=dev-project-id
FIREBASE_ENABLE_FIRESTORE=true
FIREBASE_ENABLE_REALTIME_DB=false
```

**.env.production**
```dotenv
FIREBASE_PROJECT_ID=prod-project-id
FIREBASE_ENABLE_FIRESTORE=true
FIREBASE_ENABLE_PUSH_NOTIFICATIONS=true
```

## 10. Documentation

- [Firebase Documentation](https://firebase.google.com/docs)
- [kreait/firebase-php](https://github.com/kreait/firebase-php)
- [Firebase Console](https://console.firebase.google.com)
