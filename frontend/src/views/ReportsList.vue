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
        <!-- Filtre -->
        <div class="filter-container">
          <ion-segment v-model="filterMode" @ionChange="applyFilter">
            <ion-segment-button value="all">
              <ion-label>Tous</ion-label>
            </ion-segment-button>
            <ion-segment-button value="mine">
              <ion-label>Mes signalements</ion-label>
            </ion-segment-button>
          </ion-segment>
        </div>
        
        <h3>{{ filterMode === 'all' ? 'Tous les signalements enregistrés' : 'Mes signalements' }}</h3>
        
        <!-- Loader -->
        <div v-if="loading" class="loading-spinner">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Chargement des signalements...</p>
        </div>

        <!-- Liste des signalements -->
        <ion-list v-if="!loading && filteredReports.length > 0" class="reports-list">
          <ion-item-divider color="light">
            <ion-label>
              <h2>{{ filteredReports.length }} signalement(s) trouvé(s)</h2>
            </ion-label>
          </ion-item-divider>
          <ion-item v-for="report in filteredReports" :key="report.id" class="report-item" @click="goToDetail(report.id)" style="cursor:pointer;">
            <ion-label>
              <h4>{{ report.target_type }}</h4>
              <p><strong>Utilisateur :</strong> {{ report.user?.name || 'Inconnu' }}</p>
              <p><strong>Email :</strong> {{ report.user?.email || '-' }}</p>
              <p v-if="report.road"><strong>Route :</strong> {{ report.road.designation }} ({{ report.road.latitude }}, {{ report.road.longitude }})</p>
              <p v-else><strong>Route :</strong> -</p>
              <p><strong>Date :</strong> {{ formatDate(report.report_date) }}</p>
              <p><strong>Raison :</strong> {{ report.reason }}</p>
              <div v-if="report.photo_path" class="report-photo">
                <img :src="`/storage/${report.photo_path}`" alt="Photo du signalement" @error="($event.target as HTMLImageElement).style.display='none'" />
              </div>
              <p class="created-at"><small>Créé le : {{ formatDateTime(report.created_at) }}</small></p>
            </ion-label>
          </ion-item>
        </ion-list>

        <!-- Message si aucun signalement -->
        <div v-if="!loading && filteredReports.length === 0" class="no-data">
          <ion-icon name="alert-circle-outline"></ion-icon>
          <p>{{ filterMode === 'mine' ? 'Vous n\'avez pas encore créé de signalement.' : 'Aucun signalement enregistré pour le moment.' }}</p>
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
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons, IonSpinner, IonItemDivider, IonText, IonIcon, IonSegment, IonSegmentButton } from '@ionic/vue';
import api from '../services/api';
import { useUserRole } from '../composables/useUserRole';

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
  photo_path?: string | null;
  user?: User;
  road?: Road;
}

const router = useRouter();
const { canViewReports, isAuthenticated } = useUserRole();
const reports = ref<Report[]>([]);
const allReports = ref<Report[]>([]);
const myReports = ref<Report[]>([]);
const filteredReports = ref<Report[]>([]);
const loading = ref(true);
const errorMessage = ref('');
const filterMode = ref<'all' | 'mine'>('all');
const currentUserId = ref<number | null>(null);

// Permission guard
onMounted(async () => {
  console.log('🚀 Initialisation de ReportsList...');
  // Wait for user role to be loaded
  if (!isAuthenticated.value || !canViewReports.value) {
    console.warn('❌ Utilisateur non autorisé ou non authentifié');
    router.push('/dashboard');
    return;
  }
  // Récupérer l'ID de l'utilisateur courant
  try {
    console.log('🔍 Récupération des informations utilisateur...');
    const response = await api.get('/auth/me');
    currentUserId.value = response.data.id;
    console.log('✅ Utilisateur connecté:', response.data.name, '(ID:', currentUserId.value, ')');
  } catch (error) {
    console.error('❌ Erreur lors de la récupération de l\'utilisateur courant', error);
  }
  // Charger les signalements selon le mode par défaut
  console.log('📊 Chargement initial des données...');
  applyFilter();
});

// Récupérer tous les signalements
const fetchReports = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    console.log('🔄 Chargement de TOUS les signalements...');
    const response = await api.get('/reports');
    allReports.value = response.data;
    filteredReports.value = allReports.value;
    console.log('✅ Tous les signalements chargés :', allReports.value.length, 'signalements');
    console.log('📋 Détails :', allReports.value.map(r => ({ id: r.id, user: r.user?.name, user_id: r.user_id })));
  } catch (error: any) {
    console.error('❌ Erreur lors de la récupération des signalements', error);
    errorMessage.value = 'Impossible de charger les signalements. Veuillez réessayer.';
  } finally {
    loading.value = false;
  }
};

// Récupérer uniquement les signalements de l'utilisateur connecté
const fetchMyReports = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    console.log('🔄 Chargement de MES signalements pour user_id:', currentUserId.value);
    const response = await api.get('/reports/my');
    myReports.value = response.data;
    filteredReports.value = myReports.value;
    console.log('✅ Mes signalements chargés :', myReports.value.length, 'signalements');
    console.log('👤 Utilisateur connecté ID:', currentUserId.value);
    console.log('📋 Mes signalements :', myReports.value.map(r => ({ id: r.id, user: r.user?.name, user_id: r.user_id })));
  } catch (error: any) {
    console.error('❌ Erreur lors de la récupération de mes signalements', error);
    console.error('🔍 Détails erreur:', error.response?.data || error.message);
    errorMessage.value = 'Impossible de charger vos signalements. Veuillez réessayer.';
  } finally {
    loading.value = false;
  }
};

// Appliquer le filtre
const applyFilter = () => {
  console.log('🎛️ Changement de filtre - Mode sélectionné:', filterMode.value);
  if (filterMode.value === 'mine') {
    console.log('👤 Mode "Mes signalements" - Chargement des signalements personnels...');
    fetchMyReports();
  } else {
    console.log('🌍 Mode "Tous" - Chargement de tous les signalements...');
    fetchReports();
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

const goToDetail = (id: number) => {
  router.push({ name: 'DetailSignalement', params: { id } });
};

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

.filter-container {
  margin-bottom: 20px;
  padding: 15px;
  background: #f0f0f0;
  border-radius: 8px;
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

.report-photo {
  margin: 8px 0;
}
.report-photo img {
  max-width: 100%;
  max-height: 150px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #ddd;
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
