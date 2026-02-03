<template>
<ion-page>
  <ion-header>
    <ion-toolbar>
      <ion-title>Signaler un problème</ion-title>
    </ion-toolbar>
  </ion-header>
  <ion-content class="ion-padding">
    <form @submit.prevent="submitReport">
      <ion-item>
        <ion-label position="floating">Route (optionnel)</ion-label>
        <ion-select v-model="roadId" :disabled="loading">
          <ion-select-option :value="null">-- Aucune route --</ion-select-option>
          <ion-select-option v-for="road in roads" :key="road.id" :value="road.id">
            {{ road.designation }} ({{ road.latitude }}, {{ road.longitude }})
          </ion-select-option>
        </ion-select>
      </ion-item>
      <ion-item>
        <ion-label position="floating">Type d'élément</ion-label>
        <ion-select v-model="targetType" :disabled="loading">
          <ion-select-option v-for="type in elementTypes" :key="type" :value="type">
            {{ type }}
          </ion-select-option>
        </ion-select>
      </ion-item>
      <ion-item>
        <ion-label position="floating">Date du signalement</ion-label>
        <ion-datetime
          v-model="reportDate"
          presentation="date"
          locale="fr"
          :disabled="loading"
        ></ion-datetime>
      </ion-item>
      <ion-item>
        <ion-label position="floating">Raison du signalement</ion-label>
        <ion-textarea v-model="reason" :disabled="loading"></ion-textarea>
      </ion-item>
      <ion-button expand="full" type="submit" :disabled="loading">Envoyer le signalement</ion-button>
      </form>
      <ion-text color="success" v-if="successMessage">{{ successMessage }}</ion-text>
      <ion-text color="danger" v-if="errorMessage">{{ errorMessage }}</ion-text>
    </ion-content>
</ion-page>
</template>

<script setup lang="ts">

import { ref, onMounted, watch } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonItem, IonLabel, IonInput, IonTextarea, IonButton, IonSelect, IonSelectOption, IonText, IonDatetime } from '@ionic/vue';
import { createReport } from '../services/report';
import { addReportToFirestore } from '../services/firestoreReport';
import api from '../services/api';
import { useRouter } from 'vue-router';
import { useUserRole } from '../composables/useUserRole';

const router = useRouter();
const { canCreateReport, isAuthenticated, userData, fetchUserRole } = useUserRole();

// Redirection si non autorisé
onMounted(() => {
  if (!isAuthenticated.value || !canCreateReport.value) {
    console.warn('⚠️ Accès refusé: signalement réservé aux utilisateurs');
    router.push('/dashboard');
  }
});

interface Road {
  id: number;
  designation: string;
  longitude: number;
  latitude: number;
  area: number;
}

const roads = ref<Road[]>([]);
const userId = ref<number|null>(null);
const roadId = ref<number|null>(null);
const elementTypes = ['road', 'comment']; // Types statiques
const targetType = ref('road');
// Initialiser la date à aujourd'hui au format yyyy-MM-dd
const today = new Date();
const yyyy = today.getFullYear();
const mm = String(today.getMonth() + 1).padStart(2, '0');
const dd = String(today.getDate()).padStart(2, '0');
const defaultDate = `${yyyy}-${mm}-${dd}`;
const reportDate = ref<string>(defaultDate);
const reason = ref('');
const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// Observer userData pour définir userId automatiquement
watch(userData, (newUserData) => {
  if (newUserData && !userId.value) {
    userId.value = newUserData.id;
    console.log('👤 Utilisateur automatiquement sélectionné (watch):', newUserData.name, 'ID:', newUserData.id);
  }
}, { immediate: true });

onMounted(async () => {
  // Forcer le rechargement des données utilisateur
  await fetchUserRole();
  
  // Définir automatiquement l'utilisateur connecté
  if (userData.value) {
    userId.value = userData.value.id;
    console.log('👤 Utilisateur automatiquement sélectionné (mounted):', userData.value.name, 'ID:', userData.value.id);
  }

  try {
    const roadRes = await api.get('/roads');
    roads.value = roadRes.data;
  } catch {
    roads.value = [];
  }
});

const submitReport = async () => {
    // DEBUG : afficher le token utilisé et l'utilisateur
    const debugToken = localStorage.getItem('token');
    console.log('Token utilisé pour l\'API :', debugToken);
    console.log('User ID pour le signalement :', userId.value);
    console.log('userData actuel :', userData.value);
  successMessage.value = '';
  errorMessage.value = '';
  
  // Vérifier que l'utilisateur est bien défini
  if (!userId.value) {
    // Essayer de recharger les données utilisateur
    await fetchUserRole();
    if (userData.value) {
      userId.value = userData.value.id;
    }
  }
  
  if (!userId.value || !targetType.value || !reportDate.value || !reason.value.trim()) {
    errorMessage.value = 'Tous les champs sont obligatoires.';
    console.error('Champs manquants - userId:', userId.value, 'targetType:', targetType.value, 'reportDate:', reportDate.value, 'reason:', reason.value);
    return;
  }
  // S'assurer que la date est bien au format yyyy-MM-dd
  let dateToSend = reportDate.value;
  if (dateToSend && dateToSend.length > 10) {
    dateToSend = dateToSend.slice(0, 10);
  }
  loading.value = true;
  try {
    // Enregistrement dans Laravel - OBLIGATOIRE
    console.log('Envoi du signalement à Laravel...');
    const laravelResponse = await createReport({
      report_date: dateToSend,
      target_type: targetType.value,
      reason: reason.value.trim(),
      road_id: roadId.value,
    });
    console.log('Réponse Laravel complète :', laravelResponse);
    console.log('Statut:', laravelResponse.status);
    console.log('Données:', laravelResponse.data);
    
    // Enregistrement dans Firestore - OPTIONNEL, asynchrone
    console.log('Tentative d\'enregistrement Firestore (asynchrone)...');
    addReportToFirestore({
      user_id: userId.value as number,
      report_date: dateToSend,
      target_type: targetType.value,
      reason: reason.value.trim(),
      road_id: roadId.value,
    }).then(() => {
      console.log('Firestore synchronisé avec succès');
    }).catch((firebaseError: any) => {
      console.warn('Erreur Firestore (non bloquante) :', firebaseError);
    });
    
    // SUCCÈS - Le signalement est créé dans Laravel
    successMessage.value = 'Signalement envoyé avec succès !';
    console.log('Message de succès affiché, redirection dans 2 secondes...');
    reportDate.value = defaultDate;
    reason.value = '';
    roadId.value = null;
    
    // Rediriger vers le dashboard après succès
    setTimeout(() => {
      console.log('Redirection vers dashboard...');
      router.push('/dashboard');
    }, 2000); // Délai de 2 secondes pour afficher le message
    
  } catch (e: any) {
    console.error('Erreur complète :', e);
    console.error('Status code:', e.response?.status);
    console.error('Réponse du serveur:', e.response?.data);
    errorMessage.value = e.response?.data?.message || e.message || "Erreur lors de l'envoi du signalement.";
    console.error('Message d\'erreur affiché :', errorMessage.value);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 24px;
}
</style>
