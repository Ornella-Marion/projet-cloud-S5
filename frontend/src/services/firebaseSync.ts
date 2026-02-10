/**
 * Service de synchronisation Firebase en temps réel
 * et gestion du cache hors ligne pour les données
 */

import { 
  collection, 
  onSnapshot, 
  doc, 
  setDoc, 
  getDocs, 
  query, 
  orderBy,
  Timestamp,
  writeBatch
} from 'firebase/firestore';
import { db } from '../firebase';
import api from './api';

// Clés de cache localStorage
const CACHE_KEYS = {
  ROADS: 'cached_roads',
  ROADS_DETAILS: 'cached_roads_details',
  REPORTS: 'cached_reports',
  STATISTICS: 'cached_statistics',
  LAST_SYNC: 'last_sync_timestamp',
};

// Durée de validité du cache (5 minutes)
const CACHE_DURATION = 5 * 60 * 1000;

// ============== INTERFACES ==============

export interface RoadDetails {
  id: number;
  designation: string;
  longitude: number;
  latitude: number;
  area: number;
  created_at: string;
  updated_at: string;
  reports_count: number;
  roadwork?: {
    budget: number;
    finished_at: string;
    status: string | null;
    status_percentage: number | null;
    enterprise: string | null;
  } | null;
}

export interface Statistics {
  total_roads: number;
  total_roadworks: number;
  total_reports: number;
  total_budget: number;
  total_area: number;
  roadworks_by_status: { [key: string]: number };
  reports_by_type: { [key: string]: number };
}

// ============== CACHE MANAGEMENT ==============

/**
 * Sauvegarder des données dans le cache
 */
export const saveToCache = (key: string, data: any): void => {
  try {
    const cacheEntry = {
      data,
      timestamp: Date.now(),
    };
    localStorage.setItem(key, JSON.stringify(cacheEntry));
    console.log(`💾 Cache sauvegardé: ${key}`);
  } catch (error) {
    console.error(`❌ Erreur sauvegarde cache ${key}:`, error);
  }
};

/**
 * Récupérer des données du cache
 */
export const getFromCache = <T>(key: string): T | null => {
  try {
    const cached = localStorage.getItem(key);
    if (!cached) return null;
    
    const { data, timestamp } = JSON.parse(cached);
    const isExpired = Date.now() - timestamp > CACHE_DURATION;
    
    if (isExpired) {
      console.log(`⏰ Cache expiré: ${key}`);
      return null;
    }
    
    console.log(`📦 Cache utilisé: ${key}`);
    return data as T;
  } catch (error) {
    console.error(`❌ Erreur lecture cache ${key}:`, error);
    return null;
  }
};

/**
 * Vérifier si le cache est valide
 */
export const isCacheValid = (key: string): boolean => {
  try {
    const cached = localStorage.getItem(key);
    if (!cached) return false;
    
    const { timestamp } = JSON.parse(cached);
    return Date.now() - timestamp < CACHE_DURATION;
  } catch {
    return false;
  }
};

/**
 * Vider tout le cache
 */
export const clearCache = (): void => {
  Object.values(CACHE_KEYS).forEach(key => {
    localStorage.removeItem(key);
  });
  console.log('🗑️ Cache vidé');
};

// ============== FIREBASE SYNC ==============

type UnsubscribeFunction = () => void;
const listeners: UnsubscribeFunction[] = [];

/**
 * Synchroniser les routes vers Firebase
 */
export const syncRoadsToFirebase = async (roads: RoadDetails[]): Promise<void> => {
  try {
    const batch = writeBatch(db);
    
    roads.forEach(road => {
      const roadRef = doc(db, 'roads', road.id.toString());
      batch.set(roadRef, {
        ...road,
        synced_at: Timestamp.now(),
      });
    });
    
    await batch.commit();
    console.log(`🔥 ${roads.length} routes synchronisées vers Firebase`);
  } catch (error) {
    console.error('❌ Erreur sync Firebase:', error);
  }
};

/**
 * Écouter les changements des routes en temps réel depuis Firebase
 */
export const subscribeToRoads = (
  callback: (roads: RoadDetails[]) => void
): UnsubscribeFunction => {
  const roadsRef = collection(db, 'roads');
  
  const unsubscribe = onSnapshot(roadsRef, (snapshot) => {
    const roads: RoadDetails[] = [];
    snapshot.forEach(doc => {
      roads.push(doc.data() as RoadDetails);
    });
    console.log(`🔄 Firebase: ${roads.length} routes reçues en temps réel`);
    callback(roads);
  }, (error) => {
    console.error('❌ Erreur écoute Firebase roads:', error);
  });
  
  listeners.push(unsubscribe);
  return unsubscribe;
};

/**
 * Écouter les changements des signalements en temps réel
 */
export const subscribeToReports = (
  callback: (reports: any[]) => void
): UnsubscribeFunction => {
  const reportsRef = collection(db, 'reports');
  const q = query(reportsRef, orderBy('created_at', 'desc'));
  
  const unsubscribe = onSnapshot(q, (snapshot) => {
    const reports: any[] = [];
    snapshot.forEach(doc => {
      reports.push({ id: doc.id, ...doc.data() });
    });
    console.log(`🔄 Firebase: ${reports.length} signalements reçus en temps réel`);
    callback(reports);
  }, (error) => {
    console.error('❌ Erreur écoute Firebase reports:', error);
  });
  
  listeners.push(unsubscribe);
  return unsubscribe;
};

/**
 * Écouter les statistiques en temps réel
 */
export const subscribeToStatistics = (
  callback: (stats: Statistics) => void
): UnsubscribeFunction => {
  const statsRef = doc(db, 'metadata', 'statistics');
  
  const unsubscribe = onSnapshot(statsRef, (snapshot) => {
    if (snapshot.exists()) {
      const stats = snapshot.data() as Statistics;
      console.log('🔄 Firebase: Statistiques reçues en temps réel');
      callback(stats);
    }
  }, (error) => {
    console.error('❌ Erreur écoute Firebase stats:', error);
  });
  
  listeners.push(unsubscribe);
  return unsubscribe;
};

/**
 * Arrêter tous les écouteurs Firebase
 */
export const unsubscribeAll = (): void => {
  listeners.forEach(unsub => unsub());
  listeners.length = 0;
  console.log('🔇 Tous les écouteurs Firebase arrêtés');
};

// ============== DATA FETCHING WITH CACHE ==============

/**
 * Récupérer les routes avec détails (cache + API + Firebase)
 */
export const fetchRoadsWithDetails = async (forceRefresh = false): Promise<RoadDetails[]> => {
  // 1. Vérifier le cache d'abord
  if (!forceRefresh) {
    const cached = getFromCache<RoadDetails[]>(CACHE_KEYS.ROADS_DETAILS);
    if (cached) return cached;
  }
  
  // 2. Si hors ligne, essayer de récupérer depuis Firebase
  if (!navigator.onLine) {
    console.log('📴 Mode hors ligne - Récupération depuis cache/Firebase');
    try {
      const roadsRef = collection(db, 'roads');
      const snapshot = await getDocs(roadsRef);
      const roads: RoadDetails[] = [];
      snapshot.forEach(doc => roads.push(doc.data() as RoadDetails));
      if (roads.length > 0) {
        saveToCache(CACHE_KEYS.ROADS_DETAILS, roads);
        return roads;
      }
    } catch (error) {
      console.error('❌ Erreur récupération Firebase:', error);
    }
    
    // Fallback sur le cache expiré
    const expiredCache = localStorage.getItem(CACHE_KEYS.ROADS_DETAILS);
    if (expiredCache) {
      const { data } = JSON.parse(expiredCache);
      console.log('⚠️ Utilisation du cache expiré');
      return data;
    }
    
    return [];
  }
  
  // 3. En ligne - Récupérer depuis l'API Laravel
  try {
    const response = await api.get('/roads-details');
    const roads = response.data as RoadDetails[];
    
    // Sauvegarder dans le cache
    saveToCache(CACHE_KEYS.ROADS_DETAILS, roads);
    
    // Synchroniser vers Firebase pour le temps réel
    syncRoadsToFirebase(roads);
    
    return roads;
  } catch (error) {
    console.error('❌ Erreur API roads-details:', error);
    
    // Fallback sur le cache
    const cached = localStorage.getItem(CACHE_KEYS.ROADS_DETAILS);
    if (cached) {
      const { data } = JSON.parse(cached);
      return data;
    }
    
    return [];
  }
};

/**
 * Récupérer les statistiques (cache + API)
 */
export const fetchStatistics = async (forceRefresh = false): Promise<Statistics | null> => {
  // 1. Vérifier le cache
  if (!forceRefresh) {
    const cached = getFromCache<Statistics>(CACHE_KEYS.STATISTICS);
    if (cached) return cached;
  }
  
  // 2. Si hors ligne, utiliser le cache
  if (!navigator.onLine) {
    const expiredCache = localStorage.getItem(CACHE_KEYS.STATISTICS);
    if (expiredCache) {
      const { data } = JSON.parse(expiredCache);
      return data;
    }
    return null;
  }
  
  // 3. En ligne - Récupérer depuis l'API
  try {
    const response = await api.get('/statistics');
    const stats = response.data as Statistics;
    
    // Sauvegarder dans le cache
    saveToCache(CACHE_KEYS.STATISTICS, stats);
    
    // Synchroniser vers Firebase
    const statsRef = doc(db, 'metadata', 'statistics');
    await setDoc(statsRef, {
      ...stats,
      synced_at: Timestamp.now(),
    });
    
    return stats;
  } catch (error) {
    console.error('❌ Erreur API statistics:', error);
    
    const cached = localStorage.getItem(CACHE_KEYS.STATISTICS);
    if (cached) {
      const { data } = JSON.parse(cached);
      return data;
    }
    
    return null;
  }
};

/**
 * Récupérer les détails d'une route spécifique
 */
export const fetchRoadDetails = async (roadId: number): Promise<RoadDetails | null> => {
  const cacheKey = `${CACHE_KEYS.ROADS_DETAILS}_${roadId}`;
  
  // Vérifier le cache
  const cached = getFromCache<RoadDetails>(cacheKey);
  if (cached) return cached;
  
  // Si hors ligne
  if (!navigator.onLine) {
    // Chercher dans le cache général
    const allRoads = getFromCache<RoadDetails[]>(CACHE_KEYS.ROADS_DETAILS);
    if (allRoads) {
      return allRoads.find(r => r.id === roadId) || null;
    }
    return null;
  }
  
  // En ligne
  try {
    const response = await api.get(`/roads/${roadId}/details`);
    const road = response.data as RoadDetails;
    saveToCache(cacheKey, road);
    return road;
  } catch (error) {
    console.error(`❌ Erreur API road details ${roadId}:`, error);
    return null;
  }
};

// ============== CONNECTIVITY MANAGEMENT ==============

/**
 * Initialiser la gestion de connectivité
 */
export const initConnectivityManager = (
  onOnline: () => void,
  onOffline: () => void
): void => {
  window.addEventListener('online', () => {
    console.log('🌐 Connexion rétablie');
    onOnline();
  });
  
  window.addEventListener('offline', () => {
    console.log('📴 Connexion perdue');
    onOffline();
  });
};

/**
 * Vérifier si en ligne
 */
export const isOnline = (): boolean => navigator.onLine;
