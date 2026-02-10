<template>
  <ion-page>
    <ion-content class="register-container">
      <div class="register-form">
        <h1 class="app-title">Mon App</h1>
        
        <!-- Message d'information -->
        <div class="info-message">
          <ion-icon name="information-circle"></ion-icon>
          <p>L'inscription est réservée aux managers. Veuillez contacter un manager pour créer votre compte.</p>
        </div>
        
        <ion-button expand="full" fill="clear" class="login-link" router-link="/login">
          <ion-icon slot="start" name="log-in"></ion-icon>
          Retour à la connexion
        </ion-button>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonContent, IonButton, IonIcon } from '@ionic/vue';
import { addIcons } from 'ionicons';
import { informationCircle, logIn } from 'ionicons/icons';

addIcons({
  'information-circle': informationCircle,
  'log-in': logIn
});
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

.info-message {
  background: #e3f2fd;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.info-message ion-icon {
  font-size: 48px;
  color: #1877f2;
}

.info-message p {
  color: #1565c0;
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
}

.login-link {
  --color: #1877f2;
  font-weight: 500;
  margin-top: 10px;
}
</style>