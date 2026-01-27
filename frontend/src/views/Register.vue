<template>
  <ion-page>
    <ion-content class="register-container">
      <div class="register-form">
        <h1 class="app-title">Mon App</h1>
        <ion-item class="input-item">
          <ion-label position="floating">Nom</ion-label>
          <ion-input v-model="name"></ion-input>
        </ion-item>
        <ion-item class="input-item">
          <ion-label position="floating">Email</ion-label>
          <ion-input v-model="email" type="email"></ion-input>
        </ion-item>
        <ion-item class="input-item">
          <ion-label position="floating">Mot de passe</ion-label>
          <ion-input v-model="password" type="password"></ion-input>
        </ion-item>
        <ion-button expand="full" class="register-btn" @click="register" :disabled="loading">S'inscrire</ion-button>
        <ion-button expand="full" fill="clear" class="login-link" router-link="/login">Se connecter</ion-button>
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
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonItem, IonLabel, IonInput, IonButton, IonText } from '@ionic/vue';
import { createUserWithEmailAndPassword } from 'firebase/auth';
import { auth } from '../firebase';
import api from '../services/api';

const name = ref('');
const email = ref('');
const password = ref('');
const errors = ref<string[]>([]);
const loading = ref(false);

const register = async () => {
  loading.value = true;
  errors.value = [];

  if (!name.value.trim()) {
    errors.value.push('Le nom est requis');
  }
  if (!email.value.trim()) {
    errors.value.push("L'email est requis");
  } else if (!/\S+@\S+\.\S+/.test(email.value)) {
    errors.value.push("L'email n'est pas valide");
  }
  if (password.value.length < 6) {
    errors.value.push('Le mot de passe doit contenir au moins 6 caractères');
  }
  if (errors.value.length > 0) {
    loading.value = false;
    return;
  }
  try {
    const userCredential = await createUserWithEmailAndPassword(auth, email.value, password.value);
    // Synchroniser avec le backend Laravel
    try {
      await api.post('/auth/signup', {
        name: name.value,
        email: email.value,
        password: password.value
      });
    } catch (e) {
      // Optionnel : afficher une erreur si la synchro backend échoue
      console.error('Erreur lors de la synchro Laravel:', e);
    }
    window.location.href = '/login';
  } catch (error: any) {
    if (error.code === 'auth/email-already-in-use') {
      errors.value.push('Cet email est déjà utilisé');
    } else if (error.code === 'auth/invalid-email') {
      errors.value.push('Email invalide');
    } else if (error.code === 'auth/weak-password') {
      errors.value.push('Le mot de passe est trop faible');
    } else if (error.code === 'auth/too-many-requests') {
      errors.value.push('Trop de tentatives, réessayez plus tard');
    } else if (error.message) {
      errors.value.push(error.message);
    } else {
      errors.value.push("Erreur lors de l'inscription");
    }
    console.error('Erreur Firebase:', error);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.register-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.register-form {
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

.register-btn {
  --background: #42b883;
  --background-hover: #369870;
  --background-activated: #369870;
  --color: white;
  --border-radius: 8px;
  --padding-top: 16px;
  --padding-bottom: 16px;
  font-weight: bold;
  margin-bottom: 20px;
}

.login-link {
  --color: #1877f2;
  font-weight: 500;
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