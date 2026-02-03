<template>
  <ion-page>
    <ion-content class="login-container">
      <div class="login-form">
        <h1 class="app-title">Mon App</h1>
        <ion-item class="input-item">
          <ion-label position="floating">Email</ion-label>
          <ion-input v-model="email" type="email"></ion-input>
        </ion-item>
        <ion-item class="input-item">
          <ion-label position="floating">Mot de passe</ion-label>
          <ion-input v-model="password" type="password"></ion-input>
        </ion-item>
        <ion-button expand="full" class="login-btn" @click="login" :disabled="loading">Se connecter</ion-button>
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
import { ref } from 'vue';
import { IonPage, IonContent, IonItem, IonLabel, IonInput, IonButton, IonText } from '@ionic/vue';
import { signInWithEmailAndPassword } from 'firebase/auth';
import { auth } from '../firebase';

const email = ref('');
const password = ref('');
const errors = ref<string[]>([]);
const loading = ref(false);

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
  
  // Supprimer l'ancien token avant la nouvelle connexion
  localStorage.removeItem('token');
  console.log('🗑️ Ancien token supprimé du localStorage');
  
  try {
    const userCredential = await signInWithEmailAndPassword(auth, email.value, password.value);
    console.log('🔐 Firebase login réussi pour:', email.value);
    console.log('🔐 Firebase UID:', userCredential.user.uid);
    
    // Synchroniser avec le backend Laravel pour obtenir le token
    try {
      const res = await (await import('../services/api')).default.post('/auth/login', {
        email: email.value,
        password: password.value
      });
      
      // Vérifier que le token correspond bien à l'utilisateur connecté
      console.log('✅ Token Laravel reçu pour user:', res.data.user?.name, 'ID:', res.data.user?.id, 'Email:', res.data.user?.email);
      
      if (res.data.user?.email !== email.value) {
        console.error('❌ ERREUR: Email du token ne correspond pas!');
        console.error('Email attendu:', email.value);
        console.error('Email reçu:', res.data.user?.email);
      }
      
      localStorage.setItem('token', res.data.token);
      console.log('💾 Token stocké dans localStorage');
    } catch (e) {
      errors.value.push('Connexion Laravel échouée.');
      loading.value = false;
      return;
    }
    window.location.href = '/dashboard';
  } catch (error: any) {
    if (error.code === 'auth/user-not-found') {
      errors.value.push("Aucun utilisateur trouvé avec cet email");
    } else if (error.code === 'auth/wrong-password') {
      errors.value.push('Mot de passe incorrect');
    } else if (error.code === 'auth/invalid-email') {
      errors.value.push('Email invalide');
    } else if (error.code === 'auth/too-many-requests') {
      errors.value.push('Trop de tentatives, réessayez plus tard');
    } else {
      errors.value.push('Erreur lors de la connexion');
    }
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
  background: white;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  text-align: center;
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
  --color: black;
}

.input-item ion-label {
  color: #666;
  font-weight: 500;
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

.input-item ion-input {
  --color: black !important;
}
</style>