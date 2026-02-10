<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="map-toolbar">
        <ion-buttons slot="start">
          <ion-back-button></ion-back-button>
        </ion-buttons>
        <ion-title>Carte des Routes - Antananarivo</ion-title>
        <ion-buttons slot="end">
          <!-- Indicateur de connexion -->
          <ion-chip :color="isConnected ? 'success' : 'warning'" class="connection-chip">
            <ion-icon :name="isConnected ? 'cloud-done' : 'cloud-offline'"></ion-icon>
            <ion-label>{{ isConnected ? 'En ligne' : 'Hors ligne' }}</ion-label>
          </ion-chip>
          <!-- Bouton de rafraîchissement -->
          <ion-button @click="refreshData" :disabled="isLoading">
            <ion-icon slot="icon-only" name="refresh" :class="{ 'rotating': isLoading }"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content class="map-content" fullscreen>
      <!-- Indicateur de chargement -->
      <div v-if="isLoading" class="loading-overlay">
        <ion-spinner name="crescent" color="primary"></ion-spinner>
        <p>Chargement des données...</p>
      </div>
      
      <div id="map" ref="mapContainer" class="map-container"></div>
      
      <!-- Statistiques rapides -->
      <div v-if="statistics" class="stats-bar">
        <div class="stat-item">
          <span class="stat-value">{{ statistics.total_roads }}</span>
          <span class="stat-label">Routes</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ statistics.total_reports }}</span>
          <span class="stat-label">Signalements</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">{{ formatBudgetShort(statistics.total_budget) }}</span>
          <span class="stat-label">Budget Total</span>
        </div>
      </div>
      
      <!-- Indicateur de statut géolocalisation -->
      <div v-if="geoStatus === 'loading'" class="geo-status-bar loading">
        <ion-spinner name="dots" color="light"></ion-spinner>
        <span>Localisation en cours...</span>
      </div>
      <div v-if="geoStatus === 'error'" class="geo-status-bar error" @click="getUserLocation(false, true)">
        <ion-icon name="warning"></ion-icon>
        <span>{{ geoErrorMsg }} — Appuyez pour réessayer</span>
      </div>
      <div v-if="geoStatus === 'active' && isTrackingPosition" class="geo-status-bar active">
        <ion-icon name="navigate"></ion-icon>
        <span>Position suivie en temps réel</span>
        <ion-button size="small" fill="clear" color="light" @click="stopWatchingPosition">
          <ion-icon name="close" slot="icon-only"></ion-icon>
        </ion-button>
      </div>

      <!-- Contrôles de zoom -->
      <div class="zoom-controls">
        <ion-button class="zoom-btn" @click="zoomIn">
          <ion-icon name="add"></ion-icon>
        </ion-button>
        <ion-button class="zoom-btn" @click="zoomOut">
          <ion-icon name="remove"></ion-icon>
        </ion-button>
        <ion-button class="zoom-btn reset-btn" @click="resetZoom">
          <ion-icon name="home"></ion-icon>
        </ion-button>
        <ion-button class="zoom-btn locate-btn" :class="{ 'geo-active': geoStatus === 'active', 'geo-loading': geoStatus === 'loading', 'geo-error': geoStatus === 'error' }" @click="getUserLocation(false, true)" title="Ma position">
          <ion-icon name="navigate"></ion-icon>
          <span v-if="geoStatus === 'loading'" class="geo-pulse"></span>
          <span v-if="geoStatus === 'active'" class="geo-dot active"></span>
          <span v-if="geoStatus === 'error'" class="geo-dot error"></span>
        </ion-button>
        <ion-button class="zoom-btn report-btn" @click="openReportModal" title="Signaler un problème">
          <ion-icon name="alert-circle"></ion-icon>
        </ion-button>
      </div>

      <!-- Filtre des signalements -->
      <div class="filter-controls">
        <ion-segment v-model="reportFilter" @ionChange="applyReportFilter">
          <ion-segment-button value="all">
            <ion-label>Tous</ion-label>
          </ion-segment-button>
          <ion-segment-button value="mine">
            <ion-label>Mes signalements</ion-label>
          </ion-segment-button>
        </ion-segment>
      </div>

      <!-- Bandeau mode sélection de localisation -->
      <div v-if="isSelectingLocation" class="location-picker-bar">
        <ion-icon name="location"></ion-icon>
        <span>Appuyez sur la carte pour choisir l'emplacement du problème</span>
        <ion-button size="small" fill="clear" color="light" @click="cancelLocationSelection">
          <ion-icon name="close" slot="icon-only"></ion-icon>
        </ion-button>
      </div>

      <!-- Modal de signalement rapide -->
      <ion-modal :is-open="showReportModal" @didDismiss="showReportModal = false">
        <ion-header>
          <ion-toolbar>
            <ion-buttons slot="start">
              <ion-button @click="showReportModal = false">Fermer</ion-button>
            </ion-buttons>
            <ion-title>Signaler un problème</ion-title>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <!-- Position actuelle ou sélectionnée -->
          <div class="location-section">
            <div v-if="reportLocation" class="location-info">
              <ion-icon name="location"></ion-icon>
              <span>Position: {{ reportLocation.lat.toFixed(6) }}, {{ reportLocation.lng.toFixed(6) }}</span>
            </div>
            <div v-else class="location-info warning">
              <ion-icon name="warning"></ion-icon>
              <span>Aucune position sélectionnée</span>
            </div>
            <div class="location-actions">
              <ion-button size="small" fill="outline" @click="useGpsForReport">
                <ion-icon name="navigate" slot="start"></ion-icon>
                Ma position GPS
              </ion-button>
              <ion-button size="small" fill="outline" color="tertiary" @click="pickLocationOnMap">
                <ion-icon name="map" slot="start"></ion-icon>
                Choisir sur la carte
              </ion-button>
            </div>
          </div>
          
          <ion-item>
            <ion-label position="floating">Route (optionnel)</ion-label>
            <ion-select v-model="reportForm.roadId">
              <ion-select-option :value="null">-- Aucune route --</ion-select-option>
              <ion-select-option v-for="road in roads" :key="road.id" :value="road.id">
                {{ road.designation }}
              </ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item>
            <ion-label position="floating">Type de problème</ion-label>
            <ion-select v-model="reportForm.targetType" interface="action-sheet">
              <ion-select-option v-for="(info, key) in REPORT_TYPES" :key="key" :value="key">
                {{ info.icon }} {{ info.label }}
              </ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item>
            <ion-label position="floating">Description du problème</ion-label>
            <ion-textarea v-model="reportForm.reason" :rows="4" placeholder="Décrivez le problème observé..."></ion-textarea>
          </ion-item>

          <!-- Photo section -->
          <div class="photo-section-modal">
            <ion-label class="photo-label-modal">📷 Photo (optionnel)</ion-label>
            <div class="photo-btns-modal">
              <ion-button size="small" fill="outline" @click="($refs.modalGalleryInput as HTMLInputElement)?.click()" :disabled="reportLoading">
                <ion-icon slot="start" name="image"></ion-icon>
                📁 Photo
              </ion-button>
              <ion-button size="small" fill="outline" @click="($refs.modalCameraInput as HTMLInputElement)?.click()" :disabled="reportLoading">
                <ion-icon slot="start" name="camera"></ion-icon>
                📸 Caméra
              </ion-button>
              <ion-button v-if="reportPhotoPreview" size="small" fill="outline" color="danger" @click="removeReportPhoto" :disabled="reportLoading">
                <ion-icon slot="start" name="trash"></ion-icon>
                Supprimer
              </ion-button>
            </div>
            <input ref="modalCameraInput" type="file" accept="image/*" capture="environment" style="display:none" @change="onReportPhotoSelected" />
            <input ref="modalGalleryInput" type="file" accept="image/*" style="display:none" @change="onReportPhotoSelected" />
            <div v-if="reportPhotoPreview" class="photo-preview-modal">
              <img :src="reportPhotoPreview" alt="Aperçu" />
            </div>
          </div>
          
          <ion-button expand="block" color="danger" @click="submitQuickReport" :disabled="reportLoading" class="submit-report-btn">
            <ion-spinner v-if="reportLoading" name="crescent"></ion-spinner>
            <span v-else>Envoyer le signalement</span>
          </ion-button>
          
          <div v-if="reportError" class="error-message">
            <ion-text color="danger">{{ reportError }}</ion-text>
          </div>
          <div v-if="reportSuccess" class="success-message">
            <ion-text color="success">{{ reportSuccess }}</ion-text>
          </div>
          
          <div v-if="isOfflineMode" class="offline-notice">
            <ion-icon name="cloud-offline"></ion-icon>
            <span>Mode hors ligne - Le signalement sera synchronisé automatiquement</span>
          </div>
        </ion-content>
      </ion-modal>

      <!-- Panneau d'informations des routes -->
      <div :class="['roads-info-panel', { collapsed: panelCollapsed }]">
        <div class="panel-header" @click="togglePanel">
          <div class="header-content">
            <h3>{{ roads.length }} Routes</h3>
            <p class="subtitle">Antananarivo</p>
          </div>
          <ion-icon :name="panelCollapsed ? 'chevron-up-outline' : 'chevron-down-outline'" class="toggle-icon"></ion-icon>
        </div>
        
        <div v-if="!panelCollapsed" class="roads-list-wrapper">
          <ion-list class="roads-list">
            <ion-item v-for="(road, index) in roads" :key="road.id" @click="centerMapOnRoad(road)" class="road-info-item">
              <ion-icon slot="start" :name="isRoadReported(road.id) ? 'warning' : 'location'" :class="isRoadReported(road.id) ? 'road-icon reported' : 'road-icon normal'"></ion-icon>
              <ion-label>
                <h4>{{ index + 1 }}. {{ road.designation }}</h4>
                <p>📍 Lat: {{ Number(road.latitude).toFixed(4) }}, Lon: {{ Number(road.longitude).toFixed(4) }}</p>
                <p v-if="isRoadReported(road.id)" class="report-status">⚠️ Signalée</p>
              </ion-label>
            </ion-item>
          </ion-list>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons, IonIcon, IonBackButton, IonSegment, IonSegmentButton, IonModal, IonSelect, IonSelectOption, IonTextarea, IonSpinner, IonText, IonInput, IonBadge, IonChip, toastController } from '@ionic/vue';
import { addIcons } from 'ionicons';
import { add, remove, home, map as mapIcon, location, list, alertCircle, chevronUpOutline, chevronDownOutline, navigate, warning, cloudOffline, statsChart, business, cash, calendar, refresh, cloudDone, close, mapOutline } from 'ionicons/icons';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import api from '../services/api';
import { Geolocation } from '@capacitor/geolocation';
import { useUserRole } from '../composables/useUserRole';
import { createReportWithOfflineSupport, isOnline, getPendingCount } from '../services/offlineSync';
import { 
  fetchRoadsWithDetails, 
  fetchStatistics, 
  subscribeToRoads, 
  subscribeToReports,
  unsubscribeAll,
  initConnectivityManager,
  type RoadDetails,
  type Statistics
} from '../services/firebaseSync';
// Import de la base de données locale
import localDB from '../services/localDatabase';

// Enregistrer les icônes
addIcons({
  add, remove, home, map: mapIcon, location, list, alertCircle, navigate, warning, close,
  'map-outline': mapOutline,
  'chevron-up-outline': chevronUpOutline,
  'chevron-down-outline': chevronDownOutline,
  'cloud-offline': cloudOffline,
  'cloud-done': cloudDone,
  'stats-chart': statsChart,
  'business': business,
  'cash': cash,
  'calendar': calendar,
  'refresh': refresh
});

// Corriger les icônes par défaut de Leaflet pour Vite
import markerIcon from 'leaflet/dist/images/marker-icon.png?url';
import markerShadow from 'leaflet/dist/images/marker-shadow.png?url';

// Créer une icône personnalisée pour les routes
const createCustomIcon = (color: string = '#1877f2') => {
  return L.divIcon({
    html: `
      <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: ${color};
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        cursor: pointer;
      ">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-13c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/>
        </svg>
      </div>
    `,
    className: 'custom-marker',
    iconSize: [40, 40],
    iconAnchor: [20, 20],
    popupAnchor: [0, -20],
  });
};

const DefaultIcon = L.icon({
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
});

L.Marker.prototype.setIcon(DefaultIcon);

interface Road {
  id: number;
  designation: string;
  longitude: number;
  latitude: number;
  area: number;
}

interface RoadWithDetails extends Road {
  created_at?: string;
  updated_at?: string;
  reports_count?: number;
  roadwork?: {
    budget: number;
    finished_at: string;
    status: string | null;
    status_percentage: number | null;
    enterprise: string | null;
  } | null;
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
  user?: any;
  road?: Road;
  latitude?: number;
  longitude?: number;
}

// Types de problèmes routiers avec icônes et couleurs
const REPORT_TYPES: Record<string, { label: string; icon: string; color: string }> = {
  'nid_de_poule': { label: 'Nid-de-poule', icon: '🕳️', color: '#e53935' },
  'fissure': { label: 'Fissure / Crevasse', icon: '⚡', color: '#d84315' },
  'effondrement': { label: 'Effondrement de chaussée', icon: '🚧', color: '#b71c1c' },
  'inondation': { label: 'Inondation / Eau stagnante', icon: '🌊', color: '#0277bd' },
  'feu_signalisation': { label: 'Feu de signalisation défaillant', icon: '🚦', color: '#f57f17' },
  'panneau': { label: 'Panneau manquant / endommagé', icon: '🪧', color: '#ef6c00' },
  'accident': { label: 'Accident', icon: '💥', color: '#c62828' },
  'eclairage': { label: 'Éclairage défaillant', icon: '💡', color: '#ff8f00' },
  'road': { label: 'Route endommagée (général)', icon: '🛣️', color: '#6d4c41' },
  'signalisation': { label: 'Signalisation', icon: '⚠️', color: '#ff6f00' },
  'autre': { label: 'Autre problème', icon: '📋', color: '#546e7a' },
};

const getReportTypeInfo = (type: string) => {
  return REPORT_TYPES[type] || REPORT_TYPES['autre'];
};

const mapContainer = ref<HTMLElement | null>(null);
const roads = ref<Road[]>([]);
const roadsWithDetails = ref<RoadWithDetails[]>([]);
const reports = ref<Report[]>([]);
const myReports = ref<Report[]>([]);
const panelCollapsed = ref(false);
const statistics = ref<Statistics | null>(null);
const isConnected = ref(navigator.onLine);
const isLoading = ref(false);
const lastSyncTime = ref<Date | null>(null);
let map: L.Map | null = null;
let markerGroup: L.FeatureGroup | null = null;
let reportMarkerGroup: L.FeatureGroup | null = null;
const markers: { [key: number]: any } = {};
const reportMarkers: { [key: string]: any } = {};
let userMarker: L.Marker | null = null;
let userCircle: L.Circle | null = null;
let geoWatchId: string | null = null;

// État de la géolocalisation
const geoStatus = ref<'idle' | 'loading' | 'active' | 'error'>('idle');
const geoErrorMsg = ref('');
const isTrackingPosition = ref(false);
const lastGeoUpdate = ref<Date | null>(null);

// Filtre des signalements
const reportFilter = ref<'all' | 'mine'>('all');
const { userData } = useUserRole();

// Modal de signalement
const showReportModal = ref(false);
const currentLocation = ref<{ lat: number; lng: number } | null>(null);
const reportForm = ref({
  roadId: null as number | null,
  targetType: 'nid_de_poule',
  reason: ''
});
const reportLoading = ref(false);
const reportError = ref('');
const reportSuccess = ref('');

// Photo dans le modal signalement
const reportPhotoFile = ref<Blob | null>(null);
const reportPhotoPreview = ref<string | null>(null);

const onReportPhotoSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  try {
    const { compressImage } = await import('../services/report');
    const compressed = await compressImage(file, 1024, 0.7);
    reportPhotoFile.value = compressed;
    reportPhotoPreview.value = URL.createObjectURL(compressed);
  } catch {
    reportPhotoFile.value = file;
    reportPhotoPreview.value = URL.createObjectURL(file);
  }
  input.value = '';
};

const removeReportPhoto = () => {
  if (reportPhotoPreview.value) URL.revokeObjectURL(reportPhotoPreview.value);
  reportPhotoFile.value = null;
  reportPhotoPreview.value = null;
};

// Mode sélection de position sur la carte
const isSelectingLocation = ref(false);
const selectedLocation = ref<{ lat: number; lng: number } | null>(null);
let locationPickerMarker: L.Marker | null = null;

// ==========================================
// Fonctions pour charger depuis la base locale
// ==========================================

const loadFromLocalDB = (): any[] => {
  const localRoadworks = localDB.getRoadworks();
  return localRoadworks.map(rw => ({
    id: rw.id,
    designation: rw.name,
    latitude: rw.latitude,
    longitude: rw.longitude,
    area: rw.surface || 0,
    created_at: rw.start_date,
    roadwork: {
      budget: rw.budget,
      finished_at: rw.end_date,
      status: rw.status?.name || null,
      status_percentage: null,
      enterprise: rw.enterprise?.name || null
    }
  }));
};

const loadReportsFromLocalDB = (): Report[] => {
  const localReports = localDB.getReports();
  return localReports.map((r, index) => ({
    id: index + 1,
    user_id: 0,
    road_id: r.roadwork_id,
    target_type: 'road',
    report_date: r.created_at,
    reason: r.description,
    created_at: r.created_at
  }));
};
const isOfflineMode = ref(!isOnline());

// Créer un icône personnalisé pour l'utilisateur
const createUserIcon = () => {
  return L.divIcon({
    html: `
      <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: radial-gradient(circle, #4CAF50 0%, #2E7D32 100%);
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 2px 12px rgba(76, 175, 80, 0.6), inset 0 0 0 3px rgba(255, 255, 255, 0.5);
        cursor: pointer;
        position: relative;
      ">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2">
          <circle cx="12" cy="12" r="8"/>
          <path d="M12 2v8m0 4v8M2 12h8m4 0h8"/>
        </svg>
        <div style="
          position: absolute;
          bottom: -8px;
          right: -8px;
          width: 16px;
          height: 16px;
          background: #1976d2;
          border-radius: 50%;
          border: 2px solid white;
          font-size: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: bold;
        ">✓</div>
      </div>
    `,
    iconSize: [50, 50],
    iconAnchor: [25, 25],
    popupAnchor: [0, -25],
  });
};

// Mettre à jour le marqueur et le cercle de position utilisateur sur la carte
const updateUserMarker = (latitude: number, longitude: number, accuracy: number, centerMap: boolean = false) => {
  if (!map) return;

  // Mettre à jour la position courante pour le modal de signalement
  currentLocation.value = { lat: latitude, lng: longitude };
  lastGeoUpdate.value = new Date();

  if (userMarker) {
    // Mise à jour fluide du marqueur existant
    userMarker.setLatLng([latitude, longitude]);
    userMarker.setPopupContent(`
      <div style="text-align: center; font-size: 13px; font-family: Arial;">
        <strong style="color: #4CAF50;">📍 Votre Position</strong><br>
        Lat: ${latitude.toFixed(6)}<br>
        Lon: ${longitude.toFixed(6)}<br>
        Précision: ${accuracy ? accuracy.toFixed(0) : '?'} m<br>
        <span style="color:#888;font-size:11px;">Mise à jour: ${new Date().toLocaleTimeString('fr-FR')}</span>
      </div>
    `);
  } else {
    // Créer le marqueur utilisateur pour la première fois
    userMarker = L.marker([latitude, longitude], {
      icon: createUserIcon(),
      zIndexOffset: 1000,
    })
      .bindPopup(`
        <div style="text-align: center; font-size: 13px; font-family: Arial;">
          <strong style="color: #4CAF50;">📍 Votre Position</strong><br>
          Lat: ${latitude.toFixed(6)}<br>
          Lon: ${longitude.toFixed(6)}<br>
          Précision: ${accuracy ? accuracy.toFixed(0) : '?'} m
        </div>
      `)
      .addTo(map);
  }

  if (userCircle) {
    userCircle.setLatLng([latitude, longitude]);
    userCircle.setRadius(accuracy || 50);
  } else {
    userCircle = L.circle([latitude, longitude], {
      color: '#4CAF50',
      weight: 2,
      opacity: 0.3,
      fill: true,
      fillColor: '#4CAF50',
      fillOpacity: 0.1,
      radius: accuracy || 50,
    }).addTo(map);
  }

  if (centerMap) {
    map.setView([latitude, longitude], 17);
  }
};

// Vérifier et demander les permissions de géolocalisation
const checkAndRequestGeoPermissions = async (silent: boolean = false): Promise<boolean> => {
  try {
    const permission = await Geolocation.checkPermissions();
    console.log('📍 Permission actuelle:', permission.location);

    if (permission.location === 'denied') {
      if (!silent) {
        alert('Vous avez refusé l\'accès à votre position. Veuillez l\'autoriser dans les paramètres de votre navigateur.');
      }
      return false;
    }

    if (permission.location === 'prompt') {
      const requested = await Geolocation.requestPermissions();
      console.log('📍 Permission demandée:', requested.location);
      if (requested.location === 'denied') {
        if (!silent) {
          alert('Permission de localisation refusée');
        }
        return false;
      }
    }
    return true;
  } catch (permError) {
    console.warn('⚠️ Vérification permission non disponible (Web):', permError);
    // Continuer quand même sur navigateur web
    return true;
  }
};

// Obtenir les coordonnées via Capacitor ou fallback Web
const getCoordinates = async (options: { enableHighAccuracy: boolean; timeout: number; maximumAge: number }) => {
  try {
    return await Geolocation.getCurrentPosition(options);
  } catch (capacitorError: any) {
    console.warn('⚠️ Capacitor Geolocation échoué, essai avec API Web:', capacitorError);
    return await new Promise<GeolocationPosition>((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, options);
    });
  }
};

// Gérer les erreurs de géolocalisation
const handleGeoError = (error: any, silent: boolean = false) => {
  let errorMsg = 'Impossible de vous localiser.';
  if (error.code === 1 || error.message?.includes('denied')) {
    errorMsg = 'Permission de localisation refusée. Autorisez l\'accès à votre position dans les paramètres du navigateur.';
  } else if (error.code === 2 || error.message?.includes('unavailable')) {
    errorMsg = 'Position non disponible. Vérifiez que le GPS est activé.';
  } else if (error.code === 3 || error.message?.includes('timeout')) {
    errorMsg = 'Délai d\'attente dépassé. Réessayez dans un endroit avec meilleure réception GPS.';
  } else if (error.message) {
    errorMsg = `Erreur: ${error.message}`;
  }

  geoErrorMsg.value = errorMsg;
  geoStatus.value = 'error';
  console.error('❌ Erreur de géolocalisation:', errorMsg);

  if (!silent) {
    alert(errorMsg);
  }
};

/**
 * Obtenir la position utilisateur.
 * @param silent - Si true, pas d'alerte en cas d'erreur (pour le chargement automatique)
 * @param centerMap - Si true, centre la carte sur la position obtenue
 */
const getUserLocation = async (silent: boolean = false, centerMap: boolean = true) => {
  try {
    console.log('📍 Demande de localisation...');
    geoStatus.value = 'loading';

    // Vérifier si la géolocalisation est disponible
    if (!navigator.geolocation) {
      if (!silent) alert('La géolocalisation n\'est pas supportée par votre navigateur');
      geoStatus.value = 'error';
      geoErrorMsg.value = 'Géolocalisation non supportée';
      return;
    }

    // Vérifier les permissions
    const hasPermission = await checkAndRequestGeoPermissions(silent);
    if (!hasPermission) {
      geoStatus.value = 'error';
      geoErrorMsg.value = 'Permission refusée';
      return;
    }

    // Obtenir la position
    const coordinates = await getCoordinates({
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 0,
    });

    const { latitude, longitude, accuracy } = coordinates.coords;
    console.log(`✅ Position obtenue: ${latitude}, ${longitude}, Précision: ${accuracy}m`);

    geoStatus.value = 'active';
    geoErrorMsg.value = '';

    // Mettre à jour le marqueur sur la carte
    updateUserMarker(latitude, longitude, accuracy || 50, centerMap);

    // Ouvrir le popup seulement si action manuelle
    if (!silent && userMarker) {
      userMarker.openPopup();
    }

    // Démarrer le suivi continu si pas encore actif
    if (!isTrackingPosition.value) {
      startWatchingPosition();
    }
  } catch (error: any) {
    handleGeoError(error, silent);
  }
};

/**
 * Démarrer le suivi continu de la position utilisateur via watchPosition.
 * Met à jour le marqueur et la position en temps réel.
 */
const startWatchingPosition = async () => {
  if (isTrackingPosition.value) {
    console.log('📍 Suivi de position déjà actif');
    return;
  }

  try {
    console.log('📍 Démarrage du suivi continu de la position...');
    isTrackingPosition.value = true;

    geoWatchId = await Geolocation.watchPosition(
      {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 5000,
      },
      (position: any, err: any) => {
        if (err) {
          console.warn('⚠️ Erreur watchPosition:', err);
          // Ne pas changer le statut en erreur pour une erreur ponctuelle du watch
          return;
        }

        if (position) {
          const { latitude, longitude, accuracy } = position.coords;
          console.log(`🔄 Position mise à jour: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}, Précision: ${(accuracy || 0).toFixed(0)}m`);
          
          geoStatus.value = 'active';
          geoErrorMsg.value = '';
          
          // Mettre à jour le marqueur sans recentrer la carte (l'utilisateur peut avoir bougé la vue)
          updateUserMarker(latitude, longitude, accuracy || 50, false);
        }
      }
    );

    console.log('✅ Suivi continu de la position démarré (watchId:', geoWatchId, ')');
  } catch (watchError: any) {
    console.warn('⚠️ Impossible de démarrer le suivi continu:', watchError);
    isTrackingPosition.value = false;

    // Fallback: utiliser un setInterval avec getCurrentPosition pour les navigateurs web
    console.log('📍 Fallback: suivi par intervalle (10s)');
    isTrackingPosition.value = true;
    const intervalId = setInterval(async () => {
      if (!isTrackingPosition.value) {
        clearInterval(intervalId);
        return;
      }
      try {
        const coords = await getCoordinates({
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 5000,
        });
        const { latitude, longitude, accuracy } = coords.coords;
        geoStatus.value = 'active';
        updateUserMarker(latitude, longitude, accuracy || 50, false);
      } catch (e) {
        console.warn('⚠️ Erreur mise à jour position intervalle:', e);
      }
    }, 10000);

    // Stocker l'interval ID comme watchId pour le nettoyage
    geoWatchId = intervalId.toString();
  }
};

/**
 * Arrêter le suivi continu de la position.
 */
const stopWatchingPosition = async () => {
  if (!isTrackingPosition.value) return;

  console.log('📍 Arrêt du suivi de position...');
  isTrackingPosition.value = false;

  if (geoWatchId) {
    try {
      await Geolocation.clearWatch({ id: geoWatchId });
    } catch (e) {
      // Fallback: essayer de clear l'intervalle
      try {
        clearInterval(parseInt(geoWatchId));
      } catch (_) {
        // ignore
      }
    }
    geoWatchId = null;
  }

  geoStatus.value = 'idle';
  console.log('✅ Suivi de position arrêté');
};

// Charger les routes depuis l'API avec détails complets et cache
// Utilise la base locale en mode hors ligne
const fetchRoads = async (forceRefresh = false) => {
  isLoading.value = true;
  
  try {
    let roadsData: any[] = [];
    let reportsData: any[] = [];
    
    // Vérifier si on est en ligne
    if (navigator.onLine) {
      console.log('🌐 Mode en ligne - Chargement depuis l\'API/Firebase...');
      
      // 1. Récupérer les routes avec détails (utilise cache + Firebase sync)
      try {
        roadsData = await fetchRoadsWithDetails(forceRefresh);
        
        // Sauvegarder dans la base locale pour le mode hors ligne
        // (sans typage strict pour la sauvegarde)
        try {
          const localRoads = roadsData.map((r: any) => ({
            id: r.id,
            name: r.designation || r.name,
            description: r.description || '',
            latitude: typeof r.latitude === 'string' ? parseFloat(r.latitude) : r.latitude,
            longitude: typeof r.longitude === 'string' ? parseFloat(r.longitude) : r.longitude,
            start_date: r.roadwork?.finished_at || r.created_at || new Date().toISOString(),
            status_id: r.roadwork?.status_id || 2,
            enterprise_id: r.roadwork?.enterprise_id,
            budget: r.roadwork?.budget,
            surface: r.area
          }));
          localDB.saveRoadworks(localRoads as any);
          console.log('💾 Routes sauvegardées localement');
        } catch (saveError) {
          console.warn('⚠️ Erreur sauvegarde locale:', saveError);
        }
      } catch (apiError) {
        console.warn('⚠️ Erreur API, utilisation de la base locale:', apiError);
        roadsData = loadFromLocalDB();
      }
      
      // 2. Récupérer les signalements
      try {
        const reportsResponse = await api.get('/reports');
        reportsData = reportsResponse.data;
        console.log('📋 Signalements chargés:', reportsData.length);
      } catch (e) {
        console.warn('⚠️ Impossible de charger les signalements depuis API:', e);
        reportsData = loadReportsFromLocalDB();
      }
      
      // 3. Récupérer les statistiques
      const stats = await fetchStatistics(forceRefresh);
      if (stats) {
        statistics.value = stats;
        console.log('📊 Statistiques chargées:', stats);
      }
    } else {
      console.log('📴 Mode hors ligne - Chargement depuis la base locale...');
      
      // Charger depuis la base locale
      roadsData = loadFromLocalDB();
      reportsData = loadReportsFromLocalDB();
      
      // Statistiques locales
      const localStats = localDB.getStatistics();
      statistics.value = {
        total_roads: localStats.totalRoadworks,
        total_roadworks: localStats.totalRoadworks,
        total_reports: localStats.totalReports,
        total_budget: localStats.totalBudget,
        total_area: 0,
        roadworks_by_status: localStats.byStatus,
        reports_by_type: {}
      };
      console.log('📊 Statistiques locales:', localStats);
    }
    
    // Stocker les détails
    roadsWithDetails.value = roadsData.map((road: any) => ({
      ...road,
      latitude: typeof road.latitude === 'string' ? parseFloat(road.latitude) : road.latitude,
      longitude: typeof road.longitude === 'string' ? parseFloat(road.longitude) : road.longitude,
      area: typeof road.area === 'string' ? parseFloat(road.area) : (road.surface || 0),
    }));
    
    // Copier pour la liste simple
    roads.value = roadsWithDetails.value;
    reports.value = reportsData;
    
    console.log('🗺️ Routes chargées:', roadsWithDetails.value.length);
    
    // Mettre à jour l'heure de dernière sync
    lastSyncTime.value = new Date();
    
    // Créer un groupe pour les marqueurs
    if (!markerGroup && map) {
      markerGroup = L.featureGroup().addTo(map);
    } else if (markerGroup) {
      // Vider les marqueurs existants
      markerGroup.clearLayers();
    }
    
    // Ajouter les marqueurs sur la carte
    roads.value.forEach(road => {
      addMarkerToMap(road);
    });

    // Ajouter les marqueurs de signalement (Task 206)
    addReportMarkersToMap();

    // Ajuster automatiquement la vue pour voir tous les marqueurs
    if (roads.value.length > 0 && markerGroup) {
      setTimeout(() => {
        fitAllMarkers();
      }, 500);
    }
  } catch (error) {
    console.error('❌ Erreur lors de la récupération des routes:', error);
  } finally {
    isLoading.value = false;
  }
};

// Rafraîchir les données (forcer la synchronisation)
const refreshData = async () => {
  console.log('🔄 Rafraîchissement des données...');
  await fetchRoads(true);
};

// Vérifier si une route a été signalée
const isRoadReported = (roadId: number): boolean => {
  return reports.value.some(report => report.road_id === roadId);
};

// Obtenir les détails d'une route
const getRoadDetails = (roadId: number): RoadWithDetails | undefined => {
  return roadsWithDetails.value.find(r => r.id === roadId);
};

// Formater le budget
const formatBudget = (budget: number): string => {
  if (budget >= 1000000) {
    return `${(budget / 1000000).toFixed(1)}M Ar`;
  } else if (budget >= 1000) {
    return `${(budget / 1000).toFixed(0)}K Ar`;
  }
  return `${budget} Ar`;
};

// Formater le budget pour la barre de stats
const formatBudgetShort = (budget: number | undefined): string => {
  if (!budget) return '0';
  if (budget >= 1000000000) {
    return `${(budget / 1000000000).toFixed(1)}G`;
  } else if (budget >= 1000000) {
    return `${(budget / 1000000).toFixed(1)}M`;
  } else if (budget >= 1000) {
    return `${(budget / 1000).toFixed(0)}K`;
  }
  return `${budget}`;
};

// Formater la date
const formatDate = (dateStr: string | undefined): string => {
  if (!dateStr) return 'Non définie';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Déterminer la couleur du statut
const getStatusColor = (status: string | null): string => {
  if (!status) return '#9e9e9e';
  const statusLower = status.toLowerCase();
  if (statusLower.includes('terminé') || statusLower.includes('complet')) return '#4caf50';
  if (statusLower.includes('cours') || statusLower.includes('progress')) return '#ff9800';
  if (statusLower.includes('planifié') || statusLower.includes('prévu')) return '#2196f3';
  return '#9e9e9e';
};

// Ajouter un marqueur à la carte avec infos complètes
const addMarkerToMap = (road: Road) => {
  if (!map || !markerGroup) return;

  // Récupérer les détails de la route
  const details = getRoadDetails(road.id);
  const isReported = isRoadReported(road.id);
  
  // Déterminer la couleur selon le statut
  let markerColor = '#1877f2'; // Bleu par défaut
  if (isReported) {
    markerColor = '#FF6B6B'; // Rouge si signalée
  } else if (details?.roadwork?.status) {
    markerColor = getStatusColor(details.roadwork.status);
  }

  // Construire le contenu du popup avec toutes les infos
  const roadwork = details?.roadwork;
  const popupContent = `
    <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; width: 300px; max-height: 400px; overflow-y: auto;">
      <div style="
        background: linear-gradient(135deg, ${markerColor} 0%, ${markerColor}dd 100%);
        color: white;
        padding: 12px;
        border-radius: 8px 8px 0 0;
        margin: -12px -12px 12px -12px;
      ">
        <h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600;">📍 ${road.designation}</h3>
        ${roadwork?.status ? `<span style="
          background: rgba(255,255,255,0.2);
          padding: 2px 8px;
          border-radius: 12px;
          font-size: 11px;
        ">${roadwork.status} ${roadwork.status_percentage ? `(${roadwork.status_percentage}%)` : ''}</span>` : ''}
      </div>
      
      <div style="padding: 0 4px;">
        <!-- Coordonnées -->
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
          <div style="flex: 1; background: #f8f9fa; padding: 8px; border-radius: 6px;">
            <div style="color: #666; font-size: 11px;">Latitude</div>
            <div style="font-family: monospace; color: #333;">${road.latitude.toFixed(6)}</div>
          </div>
          <div style="flex: 1; background: #f8f9fa; padding: 8px; border-radius: 6px;">
            <div style="color: #666; font-size: 11px;">Longitude</div>
            <div style="font-family: monospace; color: #333;">${road.longitude.toFixed(6)}</div>
          </div>
        </div>
        
        <!-- Surface -->
        <div style="
          background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
          border-left: 4px solid #4caf50;
          padding: 10px;
          border-radius: 6px;
          margin-bottom: 10px;
        ">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 18px;">📐</span>
            <div>
              <div style="color: #2e7d32; font-weight: 600;">Surface</div>
              <div style="color: #1b5e20; font-size: 16px;">${road.area} km²</div>
            </div>
          </div>
        </div>
        
        ${roadwork ? `
        <!-- Budget -->
        <div style="
          background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
          border-left: 4px solid #ff9800;
          padding: 10px;
          border-radius: 6px;
          margin-bottom: 10px;
        ">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 18px;">💰</span>
            <div>
              <div style="color: #e65100; font-weight: 600;">Budget</div>
              <div style="color: #bf360c; font-size: 16px;">${formatBudget(roadwork.budget)}</div>
            </div>
          </div>
        </div>
        
        <!-- Entreprise -->
        ${roadwork.enterprise ? `
        <div style="
          background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
          border-left: 4px solid #2196f3;
          padding: 10px;
          border-radius: 6px;
          margin-bottom: 10px;
        ">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 18px;">🏢</span>
            <div>
              <div style="color: #1565c0; font-weight: 600;">Entreprise</div>
              <div style="color: #0d47a1; font-size: 14px;">${roadwork.enterprise}</div>
            </div>
          </div>
        </div>
        ` : ''}
        
        <!-- Date de fin -->
        <div style="
          background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
          border-left: 4px solid #e91e63;
          padding: 10px;
          border-radius: 6px;
          margin-bottom: 10px;
        ">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 18px;">📅</span>
            <div>
              <div style="color: #c2185b; font-weight: 600;">Date de fin prévue</div>
              <div style="color: #880e4f; font-size: 14px;">${formatDate(roadwork.finished_at)}</div>
            </div>
          </div>
        </div>
        ` : `
        <div style="
          background: #f5f5f5;
          border: 1px dashed #bdbdbd;
          padding: 12px;
          border-radius: 6px;
          text-align: center;
          color: #757575;
          margin-bottom: 10px;
        ">
          <span style="font-size: 24px;">🚧</span>
          <div style="margin-top: 4px;">Aucun travaux planifiés</div>
        </div>
        `}
        
        <!-- Signalements -->
        <div style="
          background: ${isReported ? '#ffebee' : '#f5f5f5'};
          border-left: 4px solid ${isReported ? '#f44336' : '#9e9e9e'};
          padding: 10px;
          border-radius: 6px;
          margin-bottom: 10px;
        ">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 18px;">${isReported ? '⚠️' : '✅'}</span>
            <div>
              <div style="color: ${isReported ? '#c62828' : '#616161'}; font-weight: 600;">Signalements</div>
              <div style="color: ${isReported ? '#b71c1c' : '#424242'}; font-size: 14px;">
                ${details?.reports_count || 0} signalement(s)
              </div>
            </div>
          </div>
        </div>
        
        <!-- Dates -->
        <div style="
          font-size: 11px;
          color: #9e9e9e;
          text-align: center;
          padding-top: 8px;
          border-top: 1px solid #eee;
        ">
          Créé: ${formatDate(details?.created_at)} | Modifié: ${formatDate(details?.updated_at)}
        </div>
      </div>
    </div>
  `;
  
  // Créer le marqueur avec l'icône personnalisée
  const marker = L.marker([road.latitude, road.longitude], {
    icon: createCustomIcon(markerColor),
  })
    .bindPopup(popupContent, {
      maxWidth: 350,
      className: 'custom-popup',
      minWidth: 300,
    });
  
  marker.on('click', () => {
    console.log(`Route cliquée: ${road.designation}`);
  });
  
  marker.addTo(markerGroup);
  markers[road.id] = marker;
};

// Créer une icône pour les marqueurs de signalement
const createReportIcon = (type: string) => {
  const info = getReportTypeInfo(type);
  return L.divIcon({
    html: `
      <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background-color: ${info.color};
        border-radius: 50% 50% 50% 0;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.35);
        transform: rotate(-45deg);
        cursor: pointer;
      ">
        <span style="transform: rotate(45deg); font-size: 16px;">${info.icon}</span>
      </div>
    `,
    className: 'report-marker',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32],
  });
};

// Ajouter les marqueurs de signalements sur la carte (Task 206)
const addReportMarkersToMap = () => {
  if (!map) return;

  // Créer le groupe de marqueurs de signalement s'il n'existe pas
  if (!reportMarkerGroup) {
    reportMarkerGroup = L.featureGroup().addTo(map);
  } else {
    reportMarkerGroup.clearLayers();
  }

  // Vider le dictionnaire
  Object.keys(reportMarkers).forEach(k => delete reportMarkers[k as any]);

  reports.value.forEach((report: Report) => {
    let lat: number | undefined;
    let lng: number | undefined;

    // Position directe du signalement
    if (report.latitude && report.longitude) {
      lat = typeof report.latitude === 'string' ? parseFloat(report.latitude as any) : report.latitude;
      lng = typeof report.longitude === 'string' ? parseFloat(report.longitude as any) : report.longitude;
    }
    // Sinon, déduire de la route associée
    else if (report.road_id) {
      const road = roads.value.find((r: Road) => r.id === report.road_id);
      if (road) {
        lat = road.latitude + (Math.random() - 0.5) * 0.0008;
        lng = road.longitude + (Math.random() - 0.5) * 0.0008;
      }
    }
    // Sinon, utiliser les données de la route incluse
    else if (report.road) {
      lat = report.road.latitude;
      lng = report.road.longitude;
    }

    if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

    const info = getReportTypeInfo(report.target_type);
    const photoHtml = report.photo_path 
      ? `<div style="margin: 8px 0; text-align: center;"><img src="/storage/${report.photo_path}" alt="Photo" style="max-width:100%; max-height:120px; border-radius:6px; object-fit:cover;" onerror="this.style.display='none'" /></div>` 
      : '';
    const popupContent = `
      <div style="font-family: 'Segoe UI', sans-serif; font-size: 13px; min-width: 200px;">
        <div style="
          background: ${info.color};
          color: white;
          padding: 10px 12px;
          border-radius: 8px 8px 0 0;
          margin: -10px -10px 10px -10px;
        ">
          <strong>${info.icon} ${info.label}</strong>
        </div>
        <div style="padding: 0 4px;">
          <p style="margin: 0 0 6px; color: #333;"><strong>Description:</strong> ${report.reason || 'Non renseignée'}</p>
          ${photoHtml}
          <p style="margin: 0 0 6px; color: #666; font-size: 12px;">📅 ${report.report_date || report.created_at || 'Date inconnue'}</p>
          ${report.road ? `<p style="margin: 0 0 6px; color: #666; font-size: 12px;">🛣️ Route: ${report.road.designation || 'N/A'}</p>` : ''}
          <p style="margin: 0; color: #999; font-size: 11px;">📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}</p>
        </div>
      </div>
    `;

    const marker = L.marker([lat, lng], {
      icon: createReportIcon(report.target_type),
      zIndexOffset: 500,
    }).bindPopup(popupContent, {
      maxWidth: 280,
      className: 'report-popup',
    });

    marker.addTo(reportMarkerGroup!);
    reportMarkers[report.id] = marker;
  });

  console.log(`📌 ${Object.keys(reportMarkers).length} marqueurs de signalement ajoutés sur la carte`);
};

// Centrer la carte sur une route
const centerMapOnRoad = (road: Road) => {
  if (!map) return;
  
  map.setView([road.latitude, road.longitude], 16);
  
  // Ouvrir le popup du marqueur
  const marker = markers[road.id];
  if (marker) {
    marker.openPopup();
  }
};

// Ajuster la vue pour voir tous les marqueurs
const fitAllMarkers = () => {
  if (!map || !markerGroup || roads.value.length === 0) return;
  
  const bounds = markerGroup.getBounds();
  if (bounds.isValid()) {
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
  }
};

// Zoom in
const zoomIn = () => {
  if (map) {
    map.zoomIn();
  }
};

// Zoom out
const zoomOut = () => {
  if (map) {
    map.zoomOut();
  }
};

// Reset zoom
const resetZoom = () => {
  fitAllMarkers();
};

// Toggle du panneau
const togglePanel = () => {
  panelCollapsed.value = !panelCollapsed.value;
};

// Position effective pour le signalement (GPS ou sélection carte)
const reportLocation = computed(() => {
  return selectedLocation.value || currentLocation.value;
});

// Ouvrir le modal de signalement
const router = useRouter();
const openReportModal = async () => {
  // Vérifier le mode en ligne/hors ligne
  isOfflineMode.value = !isOnline();
  
  // Reset du formulaire
  selectedLocation.value = null;
  reportForm.value = { roadId: null, targetType: 'nid_de_poule', reason: '' };
  reportError.value = '';
  reportSuccess.value = '';
  
  // ⚡ Ouvrir le modal IMMÉDIATEMENT (ne pas attendre le GPS)
  showReportModal.value = true;

  // Récupérer la position GPS en arrière-plan si pas déjà disponible
  if (!currentLocation.value) {
    try {
      const coordinates = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: 10000,
      });
      currentLocation.value = {
        lat: coordinates.coords.latitude,
        lng: coordinates.coords.longitude
      };
    } catch (error) {
      console.warn('Position non disponible:', error);
    }
  }
};

// Utiliser la position GPS pour le signalement
const useGpsForReport = async () => {
  try {
    const coordinates = await getCoordinates({
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    });
    selectedLocation.value = null; // Effacer la sélection manuelle
    currentLocation.value = {
      lat: coordinates.coords.latitude,
      lng: coordinates.coords.longitude
    };
    console.log('📍 Position GPS utilisée pour le signalement');
  } catch (error) {
    console.warn('Position GPS non disponible:', error);
    reportError.value = 'Impossible d\'obtenir la position GPS';
  }
};

// Activer le mode sélection de position sur la carte (Task 209)
const pickLocationOnMap = () => {
  showReportModal.value = false; // Fermer le modal temporairement
  isSelectingLocation.value = true;
  
  if (map) {
    map.getContainer().style.cursor = 'crosshair';
    map.once('click', onMapClickForLocation);
  }
};

// Quand l'utilisateur clique sur la carte pour choisir un emplacement
const onMapClickForLocation = (e: L.LeafletMouseEvent) => {
  if (!isSelectingLocation.value) return;
  
  const { lat, lng } = e.latlng;
  selectedLocation.value = { lat, lng };
  
  // Afficher un marqueur temporaire à l'emplacement sélectionné
  if (map) {
    if (locationPickerMarker) {
      map.removeLayer(locationPickerMarker);
    }
    locationPickerMarker = L.marker([lat, lng], {
      icon: L.divIcon({
        html: `
          <div style="
            width: 36px; height: 36px;
            background: #e53935;
            border-radius: 50% 50% 50% 0;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
            transform: rotate(-45deg);
            display: flex; align-items: center; justify-content: center;
          ">
            <span style="transform: rotate(45deg); font-size: 18px;">📍</span>
          </div>
        `,
        className: 'picker-marker',
        iconSize: [36, 36],
        iconAnchor: [18, 36],
      }),
    }).addTo(map)
      .bindPopup(`<strong>Position sélectionnée</strong><br>Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`)
      .openPopup();
  }
  
  // Fin du mode sélection
  isSelectingLocation.value = false;
  if (map) {
    map.getContainer().style.cursor = '';
  }
  
  console.log(`📍 Position sélectionnée sur la carte: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
  
  // Réouvrir le modal de signalement
  showReportModal.value = true;
};

// Annuler la sélection de position
const cancelLocationSelection = () => {
  isSelectingLocation.value = false;
  if (map) {
    map.getContainer().style.cursor = '';
    map.off('click', onMapClickForLocation);
  }
  if (locationPickerMarker && map) {
    map.removeLayer(locationPickerMarker);
    locationPickerMarker = null;
  }
  // Réouvrir le modal
  showReportModal.value = true;
};

// Soumettre un signalement rapide
const submitQuickReport = async () => {
  if (!reportForm.value.reason.trim()) {
    reportError.value = 'Veuillez décrire le problème';
    return;
  }
  
  if (!userData.value) {
    reportError.value = 'Vous devez être connecté pour signaler';
    return;
  }
  
  reportLoading.value = true;
  reportError.value = '';
  
  const today = new Date();
  const reportDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  
  try {
    const result = await createReportWithOfflineSupport(
      {
        target_type: reportForm.value.targetType,
        report_date: reportDate,
        reason: reportForm.value.reason.trim(),
        road_id: reportForm.value.roadId,
        latitude: reportLocation.value?.lat,
        longitude: reportLocation.value?.lng,
      },
      userData.value.id
    );
    
    if (result.success) {
      if (result.offline) {
        reportSuccess.value = '📴 Signalement sauvegardé localement. Il sera envoyé automatiquement quand la connexion sera rétablie.';
        const offlineToast = await toastController.create({
          message: '📴 Sauvegardé localement (mode hors-ligne)',
          duration: 3000,
          position: 'top',
          color: 'warning',
        });
        await offlineToast.present();
      } else {
        reportSuccess.value = '✅ Signalement envoyé avec succès!';
        
        // Toast visible
        const toast = await toastController.create({
          message: '✅ Signalement envoyé avec succès!',
          duration: 3000,
          position: 'top',
          color: 'success',
        });
        await toast.present();

        // Si une photo a été prise et qu'on est en ligne, uploader via API directe
        if (reportPhotoFile.value && result.data?.report?.id) {
          try {
            const formData = new FormData();
            // Laravel ne lit pas les fichiers avec PUT HTTP natif
            // Il faut utiliser POST + _method=PUT pour le method spoofing
            formData.append('_method', 'PUT');
            formData.append('photo', reportPhotoFile.value, 'photo.jpg');
            formData.append('target_type', reportForm.value.targetType);
            formData.append('report_date', reportDate);
            formData.append('reason', reportForm.value.reason.trim());
            if (reportForm.value.roadId) formData.append('road_id', reportForm.value.roadId.toString());
            await api.post(`/reports/${result.data.report.id}`, formData);
            console.log('📷 Photo uploadée pour le signalement');
          } catch (photoErr) {
            console.warn('⚠️ Photo non uploadée (non bloquant):', photoErr);
          }
        }

        // Recharger les signalements
        await fetchRoads();
      }
      
      // Nettoyer photo
      removeReportPhoto();

      // Nettoyer le marqueur de sélection
      if (locationPickerMarker && map) {
        map.removeLayer(locationPickerMarker);
        locationPickerMarker = null;
      }
      selectedLocation.value = null;

      // Fermer le modal après 2 secondes
      setTimeout(() => {
        showReportModal.value = false;
        reportSuccess.value = '';
      }, 2500);
    } else {
      reportError.value = result.error || 'Erreur lors de l\'envoi';
    }
  } catch (error: any) {
    reportError.value = error.message || 'Erreur lors de l\'envoi';
  } finally {
    reportLoading.value = false;
  }
};

// Appliquer le filtre des signalements
const applyReportFilter = async () => {
  console.log('🎛️ Filtre carte:', reportFilter.value);
  
  if (reportFilter.value === 'mine' && userData.value) {
    // Charger mes signalements
    try {
      const response = await api.get('/reports/my');
      myReports.value = response.data;
      reports.value = myReports.value;
      console.log('👤 Mes signalements chargés:', myReports.value.length);
    } catch (error) {
      console.error('Erreur chargement mes signalements:', error);
    }
  } else {
    // Charger tous les signalements
    try {
      const response = await api.get('/reports');
      reports.value = response.data;
      console.log('🌍 Tous les signalements chargés:', reports.value.length);
    } catch (error) {
      console.error('Erreur chargement signalements:', error);
    }
  }
  
  // Rafraîchir les marqueurs
  refreshMarkers();
};

// Rafraîchir les marqueurs sur la carte
const refreshMarkers = () => {
  if (!map || !markerGroup) return;
  
  // Supprimer tous les marqueurs existants
  markerGroup.clearLayers();
  
  // Recréer les marqueurs
  roads.value.forEach(road => {
    addMarkerToMap(road);
  });

  // Rafraîchir aussi les marqueurs de signalement
  addReportMarkersToMap();
};

const openReportForm = () => {
  router.push('/report');
};

// Initialiser la carte
const initMap = () => {
  if (!mapContainer.value || map) return;
  
  // Créer la carte avec les coordonnées d'Antananarivo
  map = L.map(mapContainer.value, {
    zoomControl: false, // Désactiver les contrôles par défaut
    attributionControl: true,
  }).setView([-18.8788, 47.5227], 12);
  
  // Ajouter la couche OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 20,
    minZoom: 10,
  }).addTo(map);
  
  // Ajouter l'échelle
  L.control.scale({ position: 'bottomright' }).addTo(map);
  
  console.log('Carte Leaflet initialisée pour Antananarivo');
};

onMounted(() => {
  console.log('Map.vue montée');
  
  // Initialiser la gestion de connectivité
  initConnectivityManager(
    () => {
      // Quand on revient en ligne
      isConnected.value = true;
      console.log('🌐 Connexion rétablie - Synchronisation...');
      refreshData();
    },
    () => {
      // Quand on perd la connexion
      isConnected.value = false;
      console.log('📴 Mode hors ligne activé');
    }
  );
  
  // Souscrire aux mises à jour en temps réel Firebase
  subscribeToRoads((firebaseRoads) => {
    if (firebaseRoads.length > 0 && !isLoading.value) {
      console.log('🔄 Mise à jour en temps réel depuis Firebase');
      // Mettre à jour seulement si on a des données plus récentes
      roadsWithDetails.value = firebaseRoads.map(r => ({
        ...r,
        latitude: typeof r.latitude === 'string' ? parseFloat(r.latitude as any) : r.latitude,
        longitude: typeof r.longitude === 'string' ? parseFloat(r.longitude as any) : r.longitude,
        area: typeof r.area === 'string' ? parseFloat(r.area as any) : r.area,
      }));
      roads.value = roadsWithDetails.value;
    }
  });
  
  subscribeToReports((firebaseReports) => {
    if (firebaseReports.length > 0) {
      console.log('🔄 Signalements mis à jour depuis Firebase');
      reports.value = firebaseReports;
      // Rafraîchir les marqueurs de signalement en temps réel
      addReportMarkersToMap();
    }
  });
  
  // Attendre que le DOM soit prêt
  setTimeout(async () => {
    initMap();
    await fetchRoads();
    
    // 🚀 Géolocalisation automatique au chargement de la carte
    // Mode silencieux: pas d'alerte si l'utilisateur refuse ou si la position est indisponible
    // Ne centre pas la carte si des routes sont déjà affichées
    const shouldCenter = roads.value.length === 0;
    console.log('📍 Géolocalisation automatique au chargement...');
    await getUserLocation(true, shouldCenter);
  }, 100);
});

// Nettoyer les écouteurs Firebase et géolocalisation quand le composant est détruit
onUnmounted(async () => {
  console.log('Map.vue démontée - Nettoyage des écouteurs Firebase et géolocalisation');
  unsubscribeAll();
  
  // Arrêter le suivi de position
  await stopWatchingPosition();
  
  // Nettoyer les marqueurs de position
  if (map) {
    if (userMarker) {
      map.removeLayer(userMarker);
      userMarker = null;
    }
    if (userCircle) {
      map.removeLayer(userCircle);
      userCircle = null;
    }
    if (locationPickerMarker) {
      map.removeLayer(locationPickerMarker);
      locationPickerMarker = null;
    }
    if (reportMarkerGroup) {
      map.removeLayer(reportMarkerGroup);
      reportMarkerGroup = null;
    }
  }
});
</script>

<style scoped>
:deep(.map-content) {
  --padding-start: 0;
  --padding-end: 0;
  --padding-top: 0;
  --padding-bottom: 0;
  --offset-top: 0;
  --offset-bottom: 0;
}

.map-toolbar {
  --background: #1877f2;
  --color: white;
  --border-color: transparent;
}

.map-content {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.map-container {
  width: 100% !important;
  height: 100% !important;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
}

/* Indicateur de chargement */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-overlay p {
  margin-top: 16px;
  color: #1877f2;
  font-weight: 500;
}

/* Chip de connexion */
.connection-chip {
  --padding-start: 8px;
  --padding-end: 8px;
  font-size: 11px;
  height: 28px;
}

.connection-chip ion-icon {
  font-size: 14px;
  margin-right: 4px;
}

/* Animation de rotation pour le refresh */
.rotating {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Barre de statistiques */
.stats-bar {
  position: fixed;
  top: 120px;
  left: 10px;
  right: 10px;
  z-index: 998;
  background: linear-gradient(135deg, #1877f2 0%, #0d5bba 100%);
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(24, 119, 242, 0.3);
  padding: 12px;
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.stat-item {
  text-align: center;
  color: white;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: bold;
}

.stat-label {
  display: block;
  font-size: 10px;
  opacity: 0.85;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Contrôles de zoom */
.zoom-controls {
  position: fixed;
  bottom: 280px;
  right: 15px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 999;
}

/* Contrôles de filtre */
.filter-controls {
  position: fixed;
  top: 70px;
  left: 10px;
  right: 10px;
  z-index: 999;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
  padding: 5px;
}

.filter-controls ion-segment {
  --background: #f5f5f5;
}

.filter-controls ion-segment-button {
  --indicator-color: #1877f2;
  --color-checked: white;
  font-size: 12px;
}

/* Bouton localisation */
.locate-btn {
  --background: #4CAF50 !important;
  --color: white !important;
  position: relative;
}

.locate-btn.geo-active {
  --background: #2E7D32 !important;
  box-shadow: 0 0 12px rgba(76, 175, 80, 0.6) !important;
}

.locate-btn.geo-loading {
  --background: #FF9800 !important;
  animation: geo-pulse-btn 1.5s ease-in-out infinite;
}

.locate-btn.geo-error {
  --background: #f44336 !important;
}

@keyframes geo-pulse-btn {
  0%, 100% { box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3); }
  50% { box-shadow: 0 3px 20px rgba(255, 152, 0, 0.7); }
}

/* Points indicateurs de statut géolocalisation */
.geo-dot {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid white;
}

.geo-dot.active {
  background: #4CAF50;
  animation: geo-blink 2s ease-in-out infinite;
}

.geo-dot.error {
  background: #f44336;
}

@keyframes geo-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.geo-pulse {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 40px;
  height: 40px;
  margin-top: -20px;
  margin-left: -20px;
  border-radius: 50%;
  border: 3px solid rgba(255, 255, 255, 0.6);
  animation: geo-pulse-ring 1.5s ease-out infinite;
  pointer-events: none;
}

@keyframes geo-pulse-ring {
  0% { transform: scale(0.5); opacity: 1; }
  100% { transform: scale(1.5); opacity: 0; }
}

/* Barre de statut géolocalisation */
.geo-status-bar {
  position: fixed;
  top: 180px;
  left: 10px;
  right: 10px;
  z-index: 998;
  border-radius: 10px;
  padding: 8px 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 500;
  color: white;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: opacity 0.3s ease;
}

.geo-status-bar.loading {
  background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
}

.geo-status-bar.error {
  background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
}

.geo-status-bar.active {
  background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
}

.geo-status-bar ion-icon {
  font-size: 16px;
}

.geo-status-bar ion-spinner {
  width: 16px;
  height: 16px;
}

.geo-status-bar span {
  flex: 1;
}

.geo-status-bar ion-button {
  --color: white;
  margin: 0;
  height: 24px;
}

/* Modal de signalement - Forcer mode clair */
.location-section {
  margin-bottom: 16px;
}

ion-modal {
  --ion-background-color: #ffffff;
  --ion-text-color: #1a1a1a;
  --ion-item-background: #ffffff;
}

ion-modal ion-toolbar {
  --background: #1877f2;
  --color: white;
}

ion-modal ion-title {
  color: white;
}

ion-modal ion-button {
  --color: white;
}

ion-modal ion-content {
  --background: #ffffff;
  --color: #1a1a1a;
}

ion-modal ion-item {
  --background: #ffffff;
  --color: #333333;
  --border-color: #e0e0e0;
}

ion-modal ion-label {
  color: #333333 !important;
  --color: #333333;
}

ion-modal ion-select {
  color: #333333;
  --color: #333333;
  --placeholder-color: #999999;
}

ion-modal ion-textarea {
  color: #333333;
  --color: #333333;
  --placeholder-color: #999999;
}

ion-modal ion-input {
  color: #333333;
  --color: #333333;
}

.location-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #e3f2fd;
  border-radius: 8px;
  margin-bottom: 10px;
  font-size: 13px;
  color: #1565c0;
}

.location-info.warning {
  background: #fff3e0;
  color: #e65100;
}

.location-info ion-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.location-actions {
  display: flex;
  gap: 8px;
}

.location-actions ion-button {
  font-size: 12px;
  --padding-start: 10px;
  --padding-end: 10px;
}

.submit-report-btn {
  margin-top: 16px;
}

/* Photo section in report modal */
.photo-section-modal {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 12px;
  margin: 12px 0;
}
.photo-label-modal {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}
.photo-btns-modal {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}
.photo-preview-modal {
  text-align: center;
  margin-top: 8px;
}
.photo-preview-modal img {
  max-width: 100%;
  max-height: 150px;
  border-radius: 8px;
  border: 2px solid #ddd;
  object-fit: cover;
}

/* Bandeau mode sélection de localisation */
.location-picker-bar {
  position: fixed;
  top: 70px;
  left: 10px;
  right: 10px;
  z-index: 1001;
  background: linear-gradient(135deg, #e53935 0%, #c62828 100%);
  color: white;
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  box-shadow: 0 4px 15px rgba(229, 57, 53, 0.4);
  animation: picker-pulse 2s ease-in-out infinite;
}

.location-picker-bar ion-icon {
  font-size: 20px;
}

.location-picker-bar span {
  flex: 1;
}

@keyframes picker-pulse {
  0%, 100% { box-shadow: 0 4px 15px rgba(229, 57, 53, 0.4); }
  50% { box-shadow: 0 4px 25px rgba(229, 57, 53, 0.7); }
}

/* Styles pour les popups de signalements */
:deep(.report-popup) {
  .leaflet-popup-content-wrapper {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border: 2px solid #e53935;
    padding: 0;
    overflow: hidden;
  }

  .leaflet-popup-content {
    margin: 0;
    padding: 0;
    line-height: 1.5;
  }

  .leaflet-popup-tip-container {
    display: none;
  }
}

/* Marqueur de sélection de position */
:deep(.picker-marker) {
  animation: picker-bounce 0.5s ease-out;
}

@keyframes picker-bounce {
  0% { transform: translateY(-20px); opacity: 0; }
  60% { transform: translateY(5px); opacity: 1; }
  100% { transform: translateY(0); }
}

/* Marqueurs de signalement - animation */
:deep(.report-marker) {
  transition: transform 0.2s ease;
}

:deep(.report-marker:hover) {
  transform: scale(1.2);
  filter: brightness(1.1);
}

.error-message {
  margin-top: 15px;
  padding: 10px;
  background: #fee;
  border-radius: 4px;
  text-align: center;
}

.success-message {
  margin-top: 15px;
  padding: 10px;
  background: #efe;
  border-radius: 4px;
  text-align: center;
}

.offline-notice {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 15px;
  padding: 12px;
  background: #fff3e0;
  border-radius: 8px;
  font-size: 13px;
  color: #e65100;
}

.offline-notice ion-icon {
  font-size: 20px;
}

.zoom-btn {
  --background: white;
  --color: #1877f2;
  --border-radius: 50%;
  width: 50px;
  height: 50px;
  min-width: 50px;
  min-height: 50px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
  --padding-start: 0;
  --padding-end: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
}

.zoom-btn:active {
  --background: #f0f0f0;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.35);
}

.zoom-btn.reset-btn {
  margin-top: 5px;
}

/* Panneau d'informations des routes */
.roads-info-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-radius: 16px 16px 0 0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
  z-index: 998;
  max-height: 60vh;
  overflow: hidden;
  transition: max-height 0.3s ease, transform 0.3s ease;
  transform: translateY(0);
}

.roads-info-panel.collapsed {
  max-height: 60px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: linear-gradient(135deg, #1877f2 0%, #1255c0 100%);
  color: white;
  border-radius: 16px 16px 0 0;
  cursor: pointer;
  user-select: none;
  flex-shrink: 0;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.panel-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.panel-header .subtitle {
  margin: 0;
  font-size: 12px;
  opacity: 0.9;
}

.toggle-icon {
  font-size: 24px;
  cursor: pointer;
}

.roads-list-wrapper {
  height: calc(60vh - 70px);
  overflow-y: auto;
  overflow-x: hidden;
}

.roads-list {
  padding: 8px 0;
  margin: 0;
  background: white;
}

.roads-list::before,
.roads-list::after {
  display: none;
}

.road-info-item {
  --padding-start: 12px;
  --padding-end: 12px;
  --min-height: 60px;
  --border-color: #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
}

.road-info-item:last-child {
  border-bottom: none;
}

.road-info-item:active {
  --background: #f5f5f5;
  transform: scale(0.98);
}

.road-icon {
  color: #1877f2;
  font-size: 20px;
  margin-right: 8px;
}

.road-icon.reported {
  color: #FF6B6B;
}

.road-icon.normal {
  color: #1877f2;
}

.report-status {
  color: #FF6B6B !important;
  font-weight: bold;
  font-size: 11px !important;
  margin-top: 4px !important;
}

.road-info-item h4 {
  color: #1877f2;
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.road-info-item p {
  color: #666;
  margin: 4px 0 0 0;
  font-size: 12px;
}

/* Scrollbar personnalisée */
.roads-list-wrapper::-webkit-scrollbar {
  width: 8px;
}

.roads-list-wrapper::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.roads-list-wrapper::-webkit-scrollbar-thumb {
  background: #1877f2;
  border-radius: 4px;
}

.roads-list-wrapper::-webkit-scrollbar-thumb:hover {
  background: #1255c0;
}

/* Mobile responsif */
@media (max-width: 768px) {
  .roads-info-panel {
    max-height: 50vh;
  }
  
  .roads-list-wrapper {
    height: calc(50vh - 70px);
  }
  
  .zoom-controls {
    bottom: 200px;
  }
}

/* Style personnalisé des popups Leaflet */
:deep(.custom-popup) {
  .leaflet-popup-content-wrapper {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.15);
    border: 2px solid #1877f2;
    padding: 0;
    overflow: hidden;
  }

  .leaflet-popup-content {
    margin: 0;
    padding: 0;
    line-height: 1.5;
  }

  .leaflet-popup-tip-container {
    display: none;
  }
}

:deep(.leaflet-popup-tip) {
  background: white;
  border: 2px solid #1877f2;
}

/* Animation de zoom lors du clic */
:deep(.leaflet-marker-icon) {
  transition: transform 0.2s ease;
}

:deep(.leaflet-marker-icon:hover) {
  transform: scale(1.1);
}

:deep(.leaflet-marker-icon.active) {
  filter: drop-shadow(0 0 8px rgba(24, 119, 242, 0.6));
}
</style>


