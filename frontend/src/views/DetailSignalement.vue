<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Détail du Signalement</ion-title>
        <ion-buttons slot="start">
          <ion-back-button default-href="/reports-list" />
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content>
      <div v-if="loading" class="loading-spinner">
        <ion-spinner name="crescent"></ion-spinner>
        <p>Chargement du signalement...</p>
      </div>
      <div v-else-if="report">
        <h2>{{ report.target_type }}</h2>
        <p><strong>Utilisateur :</strong> {{ report.user?.name || 'Inconnu' }}</p>
        <p><strong>Email :</strong> {{ report.user?.email || '-' }}</p>
        <p v-if="report.road"><strong>Route :</strong> {{ report.road.designation }} ({{ report.road.latitude }}, {{ report.road.longitude }})</p>
        <p v-else><strong>Route :</strong> -</p>
        <p><strong>Date :</strong> {{ formatDate(report.report_date) }}</p>
        <p><strong>Raison :</strong> {{ report.reason }}</p>
        <div v-if="photoUrls.length" class="gallery">
          <h3>Photos associées</h3>
          <ion-slides :options="slideOpts" class="gallery-slides">
            <ion-slide v-for="(url, idx) in photoUrls" :key="idx">
              <div class="slide-img-container">
                <img :src="url" class="gallery-img-zoom" alt="Photo signalement" @click="toggleZoom(idx)" :class="{ zoomed: zoomedIndex === idx }" />
              </div>
            </ion-slide>
          </ion-slides>
          <p class="gallery-tip">Swipez pour naviguer, touchez une photo pour zoomer/dézoomer.</p>
        </div>
        <p class="created-at"><small>Créé le : {{ formatDateTime(report.created_at) }}</small></p>
      </div>
      <div v-else>
        <ion-text color="danger">Signalement introuvable.</ion-text>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonSpinner, IonText } from '@ionic/vue';
import api from '../services/api';

const route = useRoute();
const report = ref<any>(null);
const loading = ref(true);
const photoUrls = ref<string[]>([]);

// Options pour ion-slides (galerie)
const slideOpts = {
  initialSlide: 0,
  speed: 400,
  zoom: false,
};

// Gestion du zoom sur les images
const zoomedIndex = ref<number | null>(null);
const toggleZoom = (idx: number) => {
  zoomedIndex.value = zoomedIndex.value === idx ? null : idx;
};

const formatDate = (dateString: string): string => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR');
};
const formatDateTime = (dateTimeString: string): string => {
  if (!dateTimeString) return '-';
  const date = new Date(dateTimeString);
  return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR');
};

onMounted(async () => {
  const id = route.params.id;
  try {
    const response = await api.get(`/reports/${id}`);
    report.value = response.data;
    // Si plusieurs photos, adapter ici. Pour l'instant, on suppose photo_path unique
    if (report.value.photo_path) {
      photoUrls.value = Array.isArray(report.value.photo_path)
        ? report.value.photo_path.map((p: string) => `/storage/${p}`)
        : [`/storage/${report.value.photo_path}`];
    }
  } catch (e) {
    report.value = null;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.gallery {
  margin-top: 20px;
}
.gallery-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.gallery-img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #ccc;
}
/* Ajout styles pour zoom/swipe */
.gallery-slides {
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
}
.slide-img-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 250px;
}
.gallery-img-zoom {
  width: 100%;
  max-width: 350px;
  max-height: 240px;
  object-fit: contain;
  transition: transform 0.3s;
  cursor: zoom-in;
}
.gallery-img-zoom.zoomed {
  transform: scale(2.2);
  cursor: zoom-out;
  z-index: 10;
}
.gallery-tip {
  text-align: center;
  color: #888;
  font-size: 0.95em;
  margin-top: 8px;
}
</style>
