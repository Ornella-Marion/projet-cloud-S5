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
        <ion-select v-model="targetType" :disabled="loading" interface="action-sheet">
          <ion-select-option value="nid_de_poule">🕳️ Nid-de-poule</ion-select-option>
          <ion-select-option value="fissure">⚡ Fissure / Crevasse</ion-select-option>
          <ion-select-option value="effondrement">🚧 Effondrement de chaussée</ion-select-option>
          <ion-select-option value="inondation">🌊 Inondation / Eau stagnante</ion-select-option>
          <ion-select-option value="feu_signalisation">🚦 Feu de signalisation défaillant</ion-select-option>
          <ion-select-option value="panneau">🪧 Panneau manquant / endommagé</ion-select-option>
          <ion-select-option value="accident">💥 Accident</ion-select-option>
          <ion-select-option value="eclairage">💡 Éclairage défaillant</ion-select-option>
          <ion-select-option value="road">🛣️ Route endommagée (général)</ion-select-option>
          <ion-select-option value="signalisation">⚠️ Signalisation</ion-select-option>
          <ion-select-option value="autre">📋 Autre problème</ion-select-option>
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

      <!-- Section Photo -->
      <div class="photo-section">
        <ion-label class="photo-label">📷 Photos (optionnel, max 5)</ion-label>
        <div class="photo-buttons">
          <ion-button size="small" fill="outline" @click="triggerCamera" :disabled="loading || photos.length >= 5">
            <ion-icon slot="start" name="camera"></ion-icon>
            Caméra
          </ion-button>
          <ion-button size="small" fill="outline" @click="triggerGallery" :disabled="loading || photos.length >= 5">
            <ion-icon slot="start" name="image"></ion-icon>
            Galerie
          </ion-button>
        </div>
        <!-- Input caméra (capture) -->
        <input ref="cameraInput" type="file" accept="image/*" capture="environment" style="display:none" @change="onPhotoSelected" />
        <!-- Input galerie (multiple) -->
        <input ref="galleryInput" type="file" accept="image/*" multiple style="display:none" @change="onPhotoSelected" />
        <!-- Aperçu photos -->
        <div v-if="photoPreviews.length" class="photo-preview-list">
          <div v-for="(preview, idx) in photoPreviews" :key="idx" class="photo-preview">
            <img :src="preview" alt="Aperçu photo" />
            <p class="photo-info">{{ (photos[idx].size / 1024).toFixed(0) }} KB</p>
            <ion-button size="small" fill="clear" color="danger" @click="removePhoto(idx)" :disabled="loading">
              <ion-icon slot="icon-only" name="trash"></ion-icon>
            </ion-button>
          </div>
        </div>
      </div>

      <ion-button expand="full" type="submit" :disabled="loading">
        <ion-spinner v-if="loading" slot="start" name="crescent"></ion-spinner>
        Envoyer le signalement
      </ion-button>
      </form>
      <ion-text color="success" v-if="successMessage">{{ successMessage }}</ion-text>
      <ion-text color="danger" v-if="errorMessage">{{ errorMessage }}</ion-text>
    </ion-content>
</ion-page>
</template>

<script setup lang="ts">

import { ref, onMounted, watch } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonItem, IonLabel, IonInput, IonTextarea, IonButton, IonSelect, IonSelectOption, IonText, IonDatetime, IonIcon, IonSpinner, toastController, alertController } from '@ionic/vue';
import { createReport, compressImage } from '../services/report';
import { addReportToFirestore } from '../services/firestoreReport';
import api from '../services/api';
import { useRouter } from 'vue-router';
import { useUserRole } from '../composables/useUserRole';
import { addIcons } from 'ionicons';
import { camera, image, trash } from 'ionicons/icons';

addIcons({ camera, image, trash });

const router = useRouter();
const { canCreateReport, isAuthenticated, userData, fetchUserRole } = useUserRole();

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
const targetType = ref('nid_de_poule');
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

// Photo
const cameraInput = ref<HTMLInputElement | null>(null);
const galleryInput = ref<HTMLInputElement | null>(null);
const photos = ref<Blob[]>([]);
const photoPreviews = ref<string[]>([]);

const triggerCamera = () => {
  cameraInput.value?.click();
};
const triggerGallery = () => {
  galleryInput.value?.click();
};

const onPhotoSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement;
  const files = input.files;
  if (!files || !files.length) return;
  for (let i = 0; i < files.length && photos.value.length < 5; i++) {
    const file = files[i];
    try {
      const compressed = await compressImage(file, 1024, 0.7);
      photos.value.push(compressed);
      photoPreviews.value.push(URL.createObjectURL(compressed));
    } catch (err) {
      photos.value.push(file);
      photoPreviews.value.push(URL.createObjectURL(file));
    }
  }
  input.value = '';
};

const removePhoto = (idx: number) => {
  if (photoPreviews.value[idx]) {
    URL.revokeObjectURL(photoPreviews.value[idx]);
  }
  photos.value.splice(idx, 1);
  photoPreviews.value.splice(idx, 1);
};

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
      photos: photos.value.length > 0 ? photos.value : undefined,
    });
    console.log('Réponse Laravel complète :', laravelResponse);
    console.log('Statut:', laravelResponse?.status);
    console.log('Données:', laravelResponse?.data);
    
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
    console.log('Message de succès affiché, redirection dans 3 secondes...');
    
    // Afficher un toast bien visible
    const toast = await toastController.create({
      message: '✅ Signalement envoyé avec succès !',
      duration: 3000,
      position: 'top',
      color: 'success',
      cssClass: 'success-toast',
    });
    await toast.present();
    
    reportDate.value = defaultDate;
    reason.value = '';
    roadId.value = null;
    // Reset photos
    photos.value.forEach((_, idx) => removePhoto(idx));
    photos.value = [];
    photoPreviews.value = [];
    
    // Rediriger vers le dashboard après succès
    setTimeout(() => {
      console.log('Redirection vers dashboard...');
      router.push('/dashboard');
    }, 3000); // Délai de 3 secondes pour que le toast soit vu
    
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
.photo-section {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 12px;
  margin-top: 8px;
}
.photo-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}
.photo-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}
.photo-preview {
  margin-top: 8px;
  text-align: center;
}
.photo-preview img {
  max-width: 100%;
  max-height: 200px;
  border-radius: 8px;
  border: 2px solid #ddd;
  object-fit: cover;
}
.photo-info {
  font-size: 12px;
  color: #888;
  margin-top: 4px;
}
</style>
