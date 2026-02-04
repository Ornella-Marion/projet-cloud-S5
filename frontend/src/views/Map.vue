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
        <ion-button class="zoom-btn locate-btn" @click="getUserLocation" title="Ma position">
          <ion-icon name="navigate"></ion-icon>
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
          <div v-if="currentLocation" class="location-info">
            <ion-icon name="location"></ion-icon>
            <span>Position: {{ currentLocation.lat.toFixed(6) }}, {{ currentLocation.lng.toFixed(6) }}</span>
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
            <ion-select v-model="reportForm.targetType">
              <ion-select-option value="road">Route endommagée</ion-select-option>
              <ion-select-option value="signalisation">Signalisation</ion-select-option>
              <ion-select-option value="eclairage">Éclairage</ion-select-option>
              <ion-select-option value="autre">Autre</ion-select-option>
            </ion-select>
          </ion-item>
          
          <ion-item>
            <ion-label position="floating">Description du problème</ion-label>
            <ion-textarea v-model="reportForm.reason" :rows="4"></ion-textarea>
          </ion-item>
          
          <ion-button expand="block" color="danger" @click="submitQuickReport" :disabled="reportLoading">
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
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons, IonIcon, IonBackButton, IonSegment, IonSegmentButton, IonModal, IonSelect, IonSelectOption, IonTextarea, IonSpinner, IonText, IonInput, IonBadge, IonChip } from '@ionic/vue';
import { addIcons } from 'ionicons';
import { add, remove, home, map as mapIcon, location, list, alertCircle, chevronUpOutline, chevronDownOutline, navigate, warning, cloudOffline, statsChart, business, cash, calendar, refresh, cloudDone } from 'ionicons/icons';
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
  add, remove, home, map: mapIcon, location, list, alertCircle, navigate, warning,
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
  user?: any;
  road?: Road;
}

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
const markers: { [key: number]: any } = {};
let userMarker: L.Marker | null = null;
let userCircle: L.Circle | null = null;

// Filtre des signalements
const reportFilter = ref<'all' | 'mine'>('all');
const { userData } = useUserRole();

// Modal de signalement
const showReportModal = ref(false);
const currentLocation = ref<{ lat: number; lng: number } | null>(null);
const reportForm = ref({
  roadId: null as number | null,
  targetType: 'road',
  reason: ''
});
const reportLoading = ref(false);
const reportError = ref('');
const reportSuccess = ref('');

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

// Obtenir la position utilisateur
const getUserLocation = async () => {
  try {
    console.log('📍 Demande de localisation...');
    
    // Vérifier si la géolocalisation est disponible
    if (!navigator.geolocation) {
      alert('La géolocalisation n\'est pas supportée par votre navigateur');
      return;
    }
    
    // D'abord vérifier les permissions
    try {
      const permission = await Geolocation.checkPermissions();
      console.log('📍 Permission actuelle:', permission.location);
      
      if (permission.location === 'denied') {
        alert('Vous avez refusé l\'accès à votre position. Veuillez l\'autoriser dans les paramètres de votre navigateur.');
        return;
      }
      
      if (permission.location === 'prompt') {
        const requested = await Geolocation.requestPermissions();
        console.log('📍 Permission demandée:', requested.location);
        if (requested.location === 'denied') {
          alert('Permission de localisation refusée');
          return;
        }
      }
    } catch (permError) {
      console.warn('⚠️ Vérification permission non disponible:', permError);
      // Continuer quand même sur navigateur web
    }
    
    // Essayer avec l'API Capacitor
    let coordinates;
    try {
      coordinates = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0,
      });
    } catch (capacitorError: any) {
      console.warn('⚠️ Capacitor Geolocation échoué, essai avec API Web:', capacitorError);
      
      // Fallback sur l'API Web standard
      coordinates = await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 15000,
          maximumAge: 0,
        });
      });
    }
    
    const { latitude, longitude, accuracy } = coordinates.coords;
    console.log(`✓ Position obtenue: ${latitude}, ${longitude}, Precision: ${accuracy}m`);
    
    if (!map) return;
    
    // Supprimer l'ancien marqueur s'il existe
    if (userMarker) {
      map.removeLayer(userMarker);
    }
    if (userCircle) {
      map.removeLayer(userCircle);
    }
    
    // Ajouter le cercle de précision
    userCircle = L.circle([latitude, longitude], {
      color: '#4CAF50',
      weight: 2,
      opacity: 0.3,
      fill: true,
      fillColor: '#4CAF50',
      fillOpacity: 0.1,
      radius: accuracy || 50,
    }).addTo(map);
    
    // Ajouter le marqueur utilisateur
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
      .addTo(map)
      .openPopup();
    
    // Centrer la carte sur l'utilisateur avec un bon zoom
    map.setView([latitude, longitude], 17);
  } catch (error: any) {
    console.error('❌ Erreur de géolocalisation:', error);
    
    // Message d'erreur plus explicite
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
    
    alert(errorMsg);
  }
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

// Ouvrir le modal de signalement
const router = useRouter();
const openReportModal = async () => {
  // Récupérer la position actuelle
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
    currentLocation.value = null;
  }
  
  // Vérifier le mode en ligne/hors ligne
  isOfflineMode.value = !isOnline();
  
  // Reset du formulaire
  reportForm.value = { roadId: null, targetType: 'road', reason: '' };
  reportError.value = '';
  reportSuccess.value = '';
  
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
        latitude: currentLocation.value?.lat,
        longitude: currentLocation.value?.lng,
      },
      userData.value.id
    );
    
    if (result.success) {
      if (result.offline) {
        reportSuccess.value = '📴 Signalement sauvegardé localement. Il sera envoyé automatiquement quand la connexion sera rétablie.';
      } else {
        reportSuccess.value = '✅ Signalement envoyé avec succès!';
        // Recharger les signalements
        await fetchRoads();
      }
      
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
    }
  });
  
  // Attendre que le DOM soit prêt
  setTimeout(() => {
    initMap();
    fetchRoads();
  }, 100);
});

// Nettoyer les écouteurs Firebase quand le composant est détruit
onUnmounted(() => {
  console.log('Map.vue démontée - Nettoyage des écouteurs Firebase');
  unsubscribeAll();
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
}

/* Modal de signalement */
.location-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #e3f2fd;
  border-radius: 8px;
  margin-bottom: 15px;
  font-size: 13px;
  color: #1565c0;
}

.location-info ion-icon {
  font-size: 20px;
  color: #1877f2;
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


