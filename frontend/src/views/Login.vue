<template>
  <ion-page>
    <ion-content class="login-container">
      <div class="login-form">
        <h1 class="app-title">Mon App</h1>
        
        <!-- Indicateur mode hors ligne -->
        <div v-if="!isOnline" class="offline-banner">
          <ion-icon name="cloud-offline"></ion-icon>
          <span>Mode hors ligne</span>
        </div>
        
        <ion-item class="input-item">
          <ion-label position="floating">Email</ion-label>
          <ion-input v-model="email" type="email"></ion-input>
        </ion-item>
        <ion-item class="input-item">
          <ion-label position="floating">Mot de passe</ion-label>
          <ion-input v-model="password" type="password"></ion-input>
        </ion-item>
        <ion-button expand="full" class="login-btn" @click="login" :disabled="loading">
          <ion-spinner v-if="loading" name="crescent" style="margin-right: 8px;"></ion-spinner>
          {{ loading ? 'Connexion...' : 'Se connecter' }}
        </ion-button>
        <div class="auth-links">
          <ion-button fill="clear" router-link="/forgot-password" class="forgot-password-link">
            Mot de passe oublié ?
          </ion-button>
        </div>
        <ion-button expand="full" fill="clear" class="register-link" router-link="/register">S'inscrire</ion-button>
        <div v-if="errors.length > 0" class="error-messages">
          <ion-text color="danger">
            <ul>
              <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
          </ion-text>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { IonPage, IonContent, IonItem, IonLabel, IonInput, IonButton, IonText, IonIcon, IonSpinner } from '@ionic/vue';
import { signInWithEmailAndPassword } from 'firebase/auth';
import { auth } from '../firebase';
import { addIcons } from 'ionicons';
import { cloudOffline } from 'ionicons/icons';

addIcons({ 'cloud-offline': cloudOffline });

// Clé pour stocker les credentials en cache (hors ligne)
const OFFLINE_AUTH_KEY = 'offline_auth_cache';

const email = ref('');
const password = ref('');
const errors = ref<string[]>([]);
const loading = ref(false);
const isOnline = ref(navigator.onLine);

// Écouter les changements de connexion
const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine;
};

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus);
  window.addEventListener('offline', updateOnlineStatus);
});

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus);
  window.removeEventListener('offline', updateOnlineStatus);
});

// Hasher le mot de passe pour le cache (simple hash pour comparaison locale)
const simpleHash = (str: string): string => {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    const char = str.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return hash.toString(36);
};

// Sauvegarder les credentials en cache après login réussi
const saveOfflineCredentials = (userEmail: string, userPassword: string, userData: any, token: string) => {
  const cache = {
    email: userEmail,
    passwordHash: simpleHash(userPassword),
    userData,
    token,
    savedAt: Date.now()
  };
  localStorage.setItem(OFFLINE_AUTH_KEY, JSON.stringify(cache));
  console.log('💾 Credentials sauvegardés pour mode hors ligne');
};

// Vérifier les credentials en mode hors ligne
const checkOfflineCredentials = (userEmail: string, userPassword: string): { valid: boolean; userData?: any; token?: string } => {
  try {
    const cached = localStorage.getItem(OFFLINE_AUTH_KEY);
    if (!cached) return { valid: false };
    
    const { email, passwordHash, userData, token, savedAt } = JSON.parse(cached);
    
    // Vérifier si le cache n'est pas trop vieux (7 jours max)
    const maxAge = 7 * 24 * 60 * 60 * 1000;
    if (Date.now() - savedAt > maxAge) {
      console.log('⚠️ Cache hors ligne expiré');
      return { valid: false };
    }
    
    // Vérifier email et mot de passe
    if (email === userEmail && passwordHash === simpleHash(userPassword)) {
      console.log('✅ Credentials hors ligne valides');
      return { valid: true, userData, token };
    }
    
    return { valid: false };
  } catch {
    return { valid: false };
  }
};

const login = async () => {
  loading.value = true;
  errors.value = [];

  if (!email.value.trim()) {
    errors.value.push("L'email est requis");
  }
  if (!password.value.trim()) {
    errors.value.push('Le mot de passe est requis');
  }
  if (errors.value.length > 0) {
    loading.value = false;
    return;
  }
  
  // Mode HORS LIGNE
  if (!navigator.onLine) {
    console.log('📴 Mode hors ligne - Vérification credentials locaux...');
    
    const offlineCheck = checkOfflineCredentials(email.value, password.value);
    
    if (offlineCheck.valid && offlineCheck.token) {
      console.log('✅ Connexion hors ligne réussie pour:', email.value);
      localStorage.setItem('token', offlineCheck.token);
      
      // Stocker les infos utilisateur pour le mode hors ligne
      if (offlineCheck.userData) {
        localStorage.setItem('offline_user', JSON.stringify(offlineCheck.userData));
      }
      
      window.location.href = '/dashboard';
      return;
    } else {
      errors.value.push('Mode hors ligne: Identifiants non reconnus. Connectez-vous d\'abord en ligne.');
      loading.value = false;
      return;
    }
  }
  
  // Mode EN LIGNE - Supprimer l'ancien token avant la nouvelle connexion
  localStorage.removeItem('token');
  console.log('🗑️ Ancien token supprimé du localStorage');
  
  try {
    // 1. D'ABORD Laravel (le plus important - donne le token d'accès API)
    let laravelSuccess = false;
    try {
      const res = await (await import('../services/api')).default.post('/auth/login', {
        email: email.value,
        password: password.value
      });
      
      console.log('✅ Token Laravel reçu pour user:', res.data.user?.name, 'ID:', res.data.user?.id);
      
      // Sauvegarder le token
      localStorage.setItem('token', res.data.token);
      console.log('💾 Token stocké dans localStorage');
      
      // Sauvegarder pour le mode hors ligne
      saveOfflineCredentials(email.value, password.value, res.data.user, res.data.token);
      laravelSuccess = true;
      
    } catch (e: any) {
      console.error('❌ Erreur Laravel:', e);
      const status = e?.response?.status;
      const msg = e?.response?.data?.message || e?.message || '';
      
      if (status === 401 || status === 422) {
        errors.value.push('Email ou mot de passe incorrect');
      } else if (status === 403) {
        errors.value.push('Compte désactivé. Contactez un administrateur.');
      } else {
        errors.value.push('Serveur indisponible (' + (status || 'réseau') + '): ' + msg);
      }
      loading.value = false;
      return;
    }
    
    // 2. ENSUITE Firebase (pour la sync Firestore, pas bloquant)
    if (laravelSuccess) {
      try {
        await signInWithEmailAndPassword(auth, email.value, password.value);
        console.log('🔐 Firebase login réussi');
      } catch (firebaseError: any) {
        // Firebase échoue ? Pas grave, le login Laravel a réussi.
        // L'utilisateur peut quand même utiliser l'app.
        console.warn('⚠️ Firebase auth échouée (non bloquant):', firebaseError.code, firebaseError.message);
      }
    }
    
    window.location.href = '/dashboard';
  } catch (error: any) {
    console.error('❌ Erreur login:', error);
    errors.value.push('Erreur: ' + (error.code || '') + ' ' + (error.message || 'Inconnue'));
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-form {
  background: #ffffff;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  text-align: center;
  color: #333333;
  --ion-background-color: #ffffff;
  --ion-text-color: #333333;
  --ion-item-background: #f9f9f9;
}

.app-title {
  color: #1877f2;
  font-size: 2.5em;
  margin-bottom: 30px;
  font-weight: bold;
}

.input-item {
  --border-radius: 8px;
  --padding-start: 16px;
  --inner-padding-end: 16px;
  margin-bottom: 20px;
  --border-color: #ddd;
  --background: #f9f9f9;
  --color: #333333;
  color: #333333;
}

.input-item ion-label {
  color: #666666 !important;
  --color: #666666;
  font-weight: 500;
}

.input-item ion-input {
  --color: #333333 !important;
  color: #333333 !important;
  --placeholder-color: #999999;
}

.login-btn {
  --background: #1877f2;
  --background-hover: #166fe5;
  --background-activated: #166fe5;
  --color: white;
  --border-radius: 8px;
  --padding-top: 16px;
  --padding-bottom: 16px;
  font-weight: bold;
  margin-bottom: 20px;
}

.register-link {
  --color: #1877f2;
  font-weight: 500;
}

.auth-links {
  display: flex;
  justify-content: center;
  margin: 10px 0;
}

.forgot-password-link {
  --color: #666;
  font-size: 14px;
  --padding-start: 0;
  --padding-end: 0;
  min-height: auto;
  text-decoration: underline;
}

.error-messages {
  text-align: left;
  margin-top: 20px;
}

.error-messages ul {
  list-style: none;
  padding: 0;
}

.offline-banner {
  background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
  color: white;
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 500;
  animation: pulse 2s infinite;
}

.offline-banner ion-icon {
  font-size: 20px;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}
</style>