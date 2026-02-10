<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar class="home-toolbar">
        <ion-title>Système de Signalement Routier</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar class="home-toolbar">
          <ion-title size="large">🛣️ Antananarivo</ion-title>
        </ion-toolbar>
      </ion-header>

      <div id="container">
        <div class="hero-section">
          <h1>Signaler les Problèmes Routiers</h1>
          <p class="subtitle">Participez à l'amélioration des routes d'Antananarivo</p>
          
          <div class="features">
            <div class="feature-card">
              <ion-icon name="alert-circle" class="feature-icon report"></ion-icon>
              <h3>Signaler</h3>
              <p>Signalez les problèmes routiers</p>
            </div>
            
            <div class="feature-card">
              <ion-icon name="map" class="feature-icon map"></ion-icon>
              <h3>Localiser</h3>
              <p>Visualisez les routes sur la carte</p>
            </div>
            
            <div class="feature-card">
              <ion-icon name="list" class="feature-icon list"></ion-icon>
              <h3>Consulter</h3>
              <p>Consultez tous les signalements</p>
            </div>
          </div>

          <div class="auth-buttons">
            <ion-button expand="block" router-link="/login" class="login-btn">
              <ion-icon slot="start" name="log-in"></ion-icon>
              Se connecter
            </ion-button>
            <ion-button expand="block" router-link="/register" fill="outline" class="register-btn">
              <ion-icon slot="start" name="person-add"></ion-icon>
              S'inscrire
            </ion-button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonContent, IonHeader, IonPage, IonTitle, IonToolbar, IonButton, IonIcon } from '@ionic/vue';
import { addIcons } from 'ionicons';
import { alertCircle, map, list, logIn, personAdd } from 'ionicons/icons';

addIcons({ alertCircle, map, list, logIn, personAdd });
</script>

<style scoped>
.hero-section {
  padding: 40px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
  min-height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero-section h1 {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 10px 0;
}

.hero-section p {
  font-size: 16px;
  margin: 0;
  opacity: 0.9;
}

.features-container {
  padding: 40px 20px;
  background: #f5f5f5;
}

.feature-title {
  font-size: 24px;
  font-weight: 600;
  text-align: center;
  margin-bottom: 30px;
  color: #333;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.feature-card {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.feature-card ion-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.feature-card h3 {
  font-size: 20px;
  margin: 15px 0 10px 0;
  color: #333;
}

.feature-card p {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  margin: 0;
}

.red-icon {
  color: #FF6B6B;
}

.green-icon {
  color: #4CAF50;
}

.yellow-icon {
  color: #FFC107;
}

.auth-container {
  padding: 40px 20px;
  text-align: center;
  background: white;
}

.auth-title {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 20px;
  color: #333;
}

.auth-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
  flex-wrap: wrap;
  max-width: 600px;
  margin: 0 auto;
}

.login-btn {
  --background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --padding-start: 32px;
  --padding-end: 32px;
  --border-radius: 8px;
  font-weight: 600;
}

.register-btn {
  --border-color: #667eea;
  --color: #667eea;
  --padding-start: 32px;
  --padding-end: 32px;
  --border-radius: 8px;
  font-weight: 600;
}

.auth-buttons ion-icon {
  margin-right: 8px;
}
</style>
