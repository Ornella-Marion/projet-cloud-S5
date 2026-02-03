<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="dashboard-toolbar">
        <ion-title>Dashboard</ion-title>
        <ion-buttons slot="end">
          <ion-button class="logout-btn" @click="logout">Déconnexion</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="dashboard-content">
      <div class="welcome-section">
        <h2>Bonjour, {{ userEmail || 'Utilisateur' }} !</h2>
        <p>Bienvenue sur votre tableau de bord.</p>
        <p class="user-info">Connecté en tant que : {{ userEmail }}</p>
        <p v-if="userRole" class="role-badge" :class="`role-${userRole}`">👤 Rôle: {{ userRole }}</p>
      </div>

      <div class="actions-section">
        <h3>Actions</h3>
        <ion-button v-if="canCreateReport" expand="full" router-link="/report" class="action-btn">
          <ion-icon slot="start" name="alert-circle"></ion-icon>
          Signaler un problème
        </ion-button>
        <ion-button v-if="canViewReports" expand="full" router-link="/reports-list" class="action-btn secondary">
          <ion-icon slot="start" name="list"></ion-icon>
          Voir les signalements
        </ion-button>
        <ion-button expand="full" router-link="/map" class="action-btn map-btn">
          <ion-icon slot="start" name="map"></ion-icon>
          Voir la carte
        </ion-button>
      </div>

      <!-- Panneau Manager -->
      <manager-panel />

      <div class="roads-section">
        <h3>Routes Disponibles</h3>
        <ion-list class="roads-list">
          <ion-item v-for="road in roads" :key="road.id" class="road-item">
            <ion-label>
              <h4>{{ road.designation }}</h4>
              <p>Longitude: {{ road.longitude }}, Latitude: {{ road.latitude }}</p>
            </ion-label>
          </ion-item>
        </ion-list>
        <p v-if="roads.length === 0" class="no-data">Aucune route disponible.</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons, IonIcon } from '@ionic/vue';
import { signOut } from 'firebase/auth';
import { auth } from '../firebase';
import api from '../services/api';
import { useUserRole } from '../composables/useUserRole';
import ManagerPanel from '../components/ManagerPanel.vue';

const { userRole, canCreateReport, canViewReports } = useUserRole();

interface Road {
  id: number;
  designation: string;
  longitude: number;
  latitude: number;
}

const userEmail = ref('');
const roads = ref<Road[]>([]);

const fetchRoads = async () => {
  try {
    const response = await api.get('/roads');
    roads.value = response.data;
  } catch (error) {
    console.error('Erreur lors de la récupération des routes', error);
  }
};

const logout = async () => {
  try {
    // Appeler l'API de déconnexion Laravel pour invalider le token
    try {
      await api.post('/auth/logout');
      console.log('✅ Token Laravel invalidé');
    } catch (e) {
      console.warn('⚠️ Erreur lors de la déconnexion Laravel:', e);
    }
    
    // Supprimer le token du localStorage
    localStorage.removeItem('token');
    console.log('✅ Token supprimé du localStorage');
    
    // Déconnexion Firebase
    await signOut(auth);
    console.log('✅ Déconnexion Firebase réussie');
    
    window.location.href = '/login';
  } catch (error) {
    console.error('Erreur de déconnexion', error);
    // Même en cas d'erreur, supprimer le token local et rediriger
    localStorage.removeItem('token');
    window.location.href = '/login';
  }
};

onMounted(() => {
  const user = auth.currentUser;
  userEmail.value = user?.email || '';
  fetchRoads();
});
</script>

<style scoped>
.dashboard-toolbar {
  --background: #1877f2;
  --color: white;
}

.logout-btn {
  --color: white;
  --background: transparent;
}

.dashboard-content {
  --padding-start: 20px;
  --padding-end: 20px;
  --padding-top: 20px;
  --padding-bottom: 20px;
}

.welcome-section {
  text-align: center;
  margin-bottom: 30px;
}

.welcome-section h2 {
  color: #1877f2;
  margin-bottom: 10px;
}

.actions-section {
  margin-bottom: 30px;
}

.actions-section h3 {
  color: #333;
  margin-bottom: 15px;
}

.action-btn {
  --background: #1877f2;
  --color: white;
  margin-bottom: 10px;
}

.action-btn.secondary {
  --background: #28a745;
}

.action-btn.map-btn {
  --background: #ff6b35;
}

.roads-section h3 {
  color: #333;
  margin-bottom: 15px;
}

.roads-list {
  background: #f9f9f9;
  border-radius: 8px;
  padding: 10px;
}

.road-item {
  --border-radius: 8px;
  --padding-start: 16px;
  --padding-end: 16px;
  margin-bottom: 10px;
  --background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.road-item h4 {
  color: #1877f2;
  margin: 0;
}

.road-item p {
  color: #666;
  margin: 5px 0 0 0;
}

.user-info {
  font-size: 14px;
  color: #666;
  margin-top: 5px;
}

.role-badge {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  margin-top: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-manager {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF5252 100%);
  color: white;
}

.role-user {
  background: linear-gradient(135deg, #4CAF50 0%, #45A049 100%);
  color: white;
}

.role-visitor {
  background: linear-gradient(135deg, #FFC107 0%, #FFB300 100%);
  color: white;
}

.no-data {
  text-align: center;
  color: #999;
  font-style: italic;
}
</style>