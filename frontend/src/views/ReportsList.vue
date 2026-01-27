<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="reports-toolbar">
        <ion-title>Liste des Signalements</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="goBack">Retour</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="reports-content">
      <div class="reports-section">
        <h3>Tous les signalements enregistrés</h3>
        
        <!-- Loader -->
        <div v-if="loading" class="loading-spinner">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des signalements...</p>
        </div>

        <!-- Liste des signalements -->
        <ion-list v-if="!loading && reports.length > 0" class="reports-list">
          <ion-item-divider color="light">
            <ion-label>
              <h2>{{ reports.length }} signalement(s) trouvé(s)</h2>
            </ion-label>
          </ion-item-divider>
          
          <ion-item v-for="report in reports" :key="report.id" class="report-item">
            <ion-label>
              <h4>{{ report.target_type }}</h4>
              <p><strong>Utilisateur :</strong> {{ report.user?.name || 'Inconnu' }}</p>
              <p><strong>Email :</strong> {{ report.user?.email || '-' }}</p>
              <p v-if="report.road"><strong>Route :</strong> {{ report.road.designation }} ({{ report.road.latitude }}, {{ report.road.longitude }})</p>
              <p v-else><strong>Route :</strong> -</p>
              <p><strong>Date :</strong> {{ formatDate(report.report_date) }}</p>
              <p><strong>Raison :</strong> {{ report.reason }}</p>
              <p class="created-at"><small>Créé le : {{ formatDateTime(report.created_at) }}</small></p>
            </ion-label>
          </ion-item>
        </ion-list>

        <!-- Message si aucun signalement -->
        <div v-if="!loading && reports.length === 0" class="no-data">
          <ion-icon name="alert-circle-outline"></ion-icon>
          <p>Aucun signalement enregistré pour le moment.</p>
        </div>

        <!-- Message d'erreur -->
        <div v-if="errorMessage" class="error-message">
          <ion-text color="danger">
            <p>{{ errorMessage }}</p>
          </ion-text>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons, IonSpinner, IonItemDivider, IonText, IonIcon } from '@ionic/vue';
import api from '../services/api';

interface User {
  id: number;
  name: string;
  email: string;
}

interface Road {
  id: number;
  designation: string;
  longitude: number;
  latitude: number;
  area: number;
}

interface Report {
  id: number;
  user_id: number;
  road_id?: number | null;
  target_type: string;
  report_date: string;
  reason: string;
  created_at: string;
  user?: User;
  road?: Road;
}

const router = useRouter();
const reports = ref<Report[]>([]);
const loading = ref(true);
const errorMessage = ref('');

// Récupérer tous les signalements
const fetchReports = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await api.get('/reports');
    reports.value = response.data;
    console.log('Signalements chargés :', reports.value);
  } catch (error: any) {
    console.error('Erreur lors de la récupération des signalements', error);
    errorMessage.value = 'Impossible de charger les signalements. Veuillez réessayer.';
  } finally {
    loading.value = false;
  }
};

// Formater la date
const formatDate = (dateString: string): string => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR');
};

// Formater la date et l'heure
const formatDateTime = (dateTimeString: string): string => {
  if (!dateTimeString) return '-';
  const date = new Date(dateTimeString);
  return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR');
};

// Retourner à la page précédente
const goBack = () => {
  router.back();
};

onMounted(() => {
  fetchReports();
});
</script>

<style scoped>
.reports-toolbar {
  --background: #1877f2;
  --color: white;
}

.reports-content {
  --padding-start: 20px;
  --padding-end: 20px;
  --padding-top: 20px;
  --padding-bottom: 20px;
}

.reports-section {
  margin-bottom: 30px;
}

.reports-section h3 {
  color: #333;
  margin-bottom: 20px;
  text-align: center;
}

.reports-list {
  background: #f9f9f9;
  border-radius: 8px;
  padding: 10px;
}

.report-item {
  --border-radius: 8px;
  --padding-start: 16px;
  --padding-end: 16px;
  margin-bottom: 12px;
  --background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.report-item h4 {
  color: #1877f2;
  margin: 0 0 8px 0;
  font-weight: bold;
  text-transform: uppercase;
}

.report-item p {
  color: #666;
  margin: 4px 0;
  font-size: 14px;
}

.report-item strong {
  color: #333;
}

.created-at {
  color: #999;
  margin-top: 8px;
}

.loading-spinner {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.loading-spinner ion-spinner {
  width: 50px;
  height: 50px;
  margin-bottom: 20px;
}

.loading-spinner p {
  color: #666;
  font-size: 14px;
}

.no-data {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 300px;
  text-align: center;
  color: #999;
}

.no-data ion-icon {
  font-size: 48px;
  margin-bottom: 20px;
  color: #ccc;
}

.no-data p {
  font-size: 16px;
}

.error-message {
  margin-top: 20px;
  padding: 15px;
  background: #fee;
  border-left: 4px solid #f44;
  border-radius: 4px;
}
</style>
