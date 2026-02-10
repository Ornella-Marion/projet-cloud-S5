<template>
  <ion-page>
    <ion-content class="forgot-password-container">
      <div class="forgot-password-form">
        <h1 class="app-title">Mot de passe oublié</h1>
        <p class="description">
          Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
        </p>

        <div v-if="!emailSent" class="form-section">
          <ion-item class="input-item">
            <ion-label position="floating">Email</ion-label>
            <ion-input v-model="email" type="email" placeholder="votre@email.com"></ion-input>
          </ion-item>

          <ion-button
            expand="full"
            class="reset-btn"
            @click="sendResetEmail"
            :disabled="loading || !email.trim()"
          >
            <ion-spinner v-if="loading" slot="start" name="crescent"></ion-spinner>
            Envoyer le lien de réinitialisation
          </ion-button>
        </div>

        <div v-else class="success-section">
          <ion-icon name="checkmark-circle" class="success-icon" color="success"></ion-icon>
          <h3>Email envoyé !</h3>
          <p>
            Un lien de réinitialisation a été envoyé à <strong>{{ email }}</strong>.
            Vérifiez votre boîte de réception et suivez les instructions.
          </p>
          <ion-button expand="full" fill="clear" router-link="/login" class="back-to-login">
            Retour à la connexion
          </ion-button>
        </div>

        <ion-button expand="full" fill="clear" router-link="/login" class="back-link">
          ← Retour à la connexion
        </ion-button>

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
import { IonPage, IonContent, IonItem, IonLabel, IonInput, IonButton, IonText, IonSpinner, IonIcon } from '@ionic/vue';
import { sendPasswordResetEmail } from 'firebase/auth';
import { auth } from '../firebase';

const email = ref('');
const errors = ref<string[]>([]);
const loading = ref(false);
const emailSent = ref(false);

const sendResetEmail = async () => {
  loading.value = true;
  errors.value = [];

  // Validation côté frontend
  if (!email.value.trim()) {
    errors.value.push('L\'email est requis');
    loading.value = false;
    return;
  }

  if (!/\S+@\S+\.\S+/.test(email.value)) {
    errors.value.push('L\'email n\'est pas valide');
    loading.value = false;
    return;
  }

  try {
    await sendPasswordResetEmail(auth, email.value);
    emailSent.value = true;
    console.log('Email de réinitialisation envoyé à:', email.value);
  } catch (error: any) {
    if (error.code === 'auth/user-not-found') {
      errors.value.push('Aucun compte trouvé avec cette adresse email');
    } else if (error.code === 'auth/invalid-email') {
      errors.value.push('Adresse email invalide');
    } else if (error.code === 'auth/too-many-requests') {
      errors.value.push('Trop de tentatives, réessayez plus tard');
    } else {
      errors.value.push('Erreur lors de l\'envoi de l\'email. Veuillez réessayer');
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.forgot-password-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.forgot-password-form {
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
  margin-bottom: 10px;
  font-size: 28px;
  font-weight: bold;
}

.description {
  color: #666;
  margin-bottom: 30px;
  line-height: 1.5;
}

.input-item {
  --border-radius: 8px;
  --padding-start: 16px;
  --inner-padding-end: 16px;
  margin-bottom: 20px;
  --border-color: #e1e8ed;
}

.reset-btn {
  --background: #1877f2;
  --border-radius: 8px;
  margin-bottom: 20px;
  --color: white;
}

.success-section {
  padding: 20px 0;
}

.success-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.success-section h3 {
  color: #28a745;
  margin-bottom: 15px;
  font-size: 24px;
}

.success-section p {
  color: #666;
  line-height: 1.5;
  margin-bottom: 20px;
}

.back-to-login {
  margin-bottom: 20px;
}

.back-link {
  margin-top: 10px;
  --color: #1877f2;
}

.error-messages {
  margin-top: 20px;
  text-align: left;
}

.error-messages ul {
  padding-left: 20px;
}

.error-messages li {
  margin-bottom: 5px;
}
</style>
