/**
 * Service de synchronisation hors ligne
 * 
 * Logique:
 * - Si connexion Internet → Utiliser Laravel (base principale) + Firestore (backup)
 * - Si PAS de connexion → Sauvegarder localement (IndexedDB)
 * - Quand connexion revient → Synchroniser avec Laravel
 */

import api from './api';
import { addReportToFirestore } from './firestoreReport';

const OFFLINE_REPORTS_KEY = 'offline_reports';
const OFFLINE_QUEUE_KEY = 'offline_sync_queue';

interface OfflineReport {
  id: string;
  target_type: string;
  report_date: string;
  reason: string;
  road_id?: number | null;
  user_id: number;
  latitude?: number;
  longitude?: number;
  created_at: string;
  synced: boolean;
}

/**
 * Vérifier si l'application est en ligne
 */
export const isOnline = (): boolean => {
  return navigator.onLine;
};

/**
 * Écouter les changements de connectivité
 */
export const onConnectivityChange = (callback: (online: boolean) => void) => {
  window.addEventListener('online', () => {
    console.log('🌐 Connexion rétablie');
    callback(true);
    // Synchroniser automatiquement les données en attente
    syncPendingReports();
  });
  
  window.addEventListener('offline', () => {
    console.log('📴 Connexion perdue - Mode hors ligne activé');
    callback(false);
  });
};

/**
 * Générer un ID unique pour les rapports hors ligne
 */
const generateOfflineId = (): string => {
  return `offline_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
};

/**
 * Sauvegarder un rapport localement (mode hors ligne)
 */
export const saveReportLocally = (report: Omit<OfflineReport, 'id' | 'created_at' | 'synced'>): OfflineReport => {
  const offlineReports = getOfflineReports();
  
  const newReport: OfflineReport = {
    ...report,
    id: generateOfflineId(),
    created_at: new Date().toISOString(),
    synced: false,
  };
  
  offlineReports.push(newReport);
  localStorage.setItem(OFFLINE_REPORTS_KEY, JSON.stringify(offlineReports));
  
  console.log('💾 Rapport sauvegardé localement:', newReport.id);
  return newReport;
};

/**
 * Récupérer tous les rapports hors ligne
 */
export const getOfflineReports = (): OfflineReport[] => {
  const stored = localStorage.getItem(OFFLINE_REPORTS_KEY);
  return stored ? JSON.parse(stored) : [];
};

/**
 * Récupérer les rapports non synchronisés
 */
export const getPendingReports = (): OfflineReport[] => {
  return getOfflineReports().filter(r => !r.synced);
};

/**
 * Marquer un rapport comme synchronisé
 */
const markReportAsSynced = (offlineId: string) => {
  const reports = getOfflineReports();
  const index = reports.findIndex(r => r.id === offlineId);
  
  if (index !== -1) {
    reports[index].synced = true;
    localStorage.setItem(OFFLINE_REPORTS_KEY, JSON.stringify(reports));
  }
};

/**
 * Supprimer les rapports synchronisés (nettoyage)
 */
export const cleanSyncedReports = () => {
  const reports = getOfflineReports().filter(r => !r.synced);
  localStorage.setItem(OFFLINE_REPORTS_KEY, JSON.stringify(reports));
  console.log('🧹 Rapports synchronisés nettoyés');
};

/**
 * Synchroniser les rapports en attente avec Laravel
 */
export const syncPendingReports = async (): Promise<{ success: number; failed: number }> => {
  const pending = getPendingReports();
  
  if (pending.length === 0) {
    console.log('✅ Aucun rapport à synchroniser');
    return { success: 0, failed: 0 };
  }
  
  console.log(`🔄 Synchronisation de ${pending.length} rapport(s) en attente...`);
  
  let success = 0;
  let failed = 0;
  
  for (const report of pending) {
    try {
      // Envoyer à Laravel
      const response = await api.post('/reports', {
        target_type: report.target_type,
        report_date: report.report_date,
        reason: report.reason,
        road_id: report.road_id,
      });
      
      console.log(`✅ Rapport ${report.id} synchronisé avec Laravel`);
      
      // Envoyer aussi à Firestore (backup)
      try {
        await addReportToFirestore({
          user_id: report.user_id,
          target_type: report.target_type,
          report_date: report.report_date,
          reason: report.reason,
          road_id: report.road_id,
        });
        console.log(`☁️ Rapport ${report.id} sauvegardé dans Firestore`);
      } catch (firestoreError) {
        console.warn(`⚠️ Erreur Firestore pour ${report.id}:`, firestoreError);
      }
      
      markReportAsSynced(report.id);
      success++;
    } catch (error) {
      console.error(`❌ Échec de synchronisation pour ${report.id}:`, error);
      failed++;
    }
  }
  
  // Nettoyer les rapports synchronisés après succès
  if (success > 0) {
    cleanSyncedReports();
  }
  
  console.log(`📊 Synchronisation terminée: ${success} réussis, ${failed} échoués`);
  return { success, failed };
};

/**
 * Créer un rapport (avec gestion hors ligne automatique)
 */
export const createReportWithOfflineSupport = async (
  reportData: {
    target_type: string;
    report_date: string;
    reason: string;
    road_id?: number | null;
    latitude?: number;
    longitude?: number;
  },
  userId: number
): Promise<{ success: boolean; offline: boolean; data?: any; error?: string }> => {
  
  if (isOnline()) {
    // Mode en ligne: envoyer directement à Laravel
    try {
      console.log('🌐 Mode en ligne - Envoi à Laravel...');
      
      const response = await api.post('/reports', reportData);
      console.log('✅ Rapport créé dans Laravel:', response.data);
      
      // Sauvegarder aussi dans Firestore (backup cloud)
      try {
        await addReportToFirestore({
          user_id: userId,
          ...reportData,
        });
        console.log('☁️ Backup Firestore réussi');
      } catch (firestoreError) {
        console.warn('⚠️ Erreur Firestore (non bloquante):', firestoreError);
      }
      
      return { success: true, offline: false, data: response.data };
    } catch (error: any) {
      console.error('❌ Erreur Laravel:', error);
      
      // Si erreur réseau, basculer en mode hors ligne
      if (!navigator.onLine || error.code === 'ERR_NETWORK') {
        console.log('📴 Basculement en mode hors ligne...');
        const offlineReport = saveReportLocally({ ...reportData, user_id: userId });
        return { success: true, offline: true, data: offlineReport };
      }
      
      return { success: false, offline: false, error: error.message };
    }
  } else {
    // Mode hors ligne: sauvegarder localement
    console.log('📴 Mode hors ligne - Sauvegarde locale...');
    const offlineReport = saveReportLocally({ ...reportData, user_id: userId });
    return { success: true, offline: true, data: offlineReport };
  }
};

/**
 * Obtenir le nombre de rapports en attente de synchronisation
 */
export const getPendingCount = (): number => {
  return getPendingReports().length;
};

// Initialiser les écouteurs de connectivité au chargement
if (typeof window !== 'undefined') {
  onConnectivityChange((online) => {
    if (online) {
      // Notification visuelle (optionnel)
      console.log('🔄 Tentative de synchronisation automatique...');
    }
  });
}
