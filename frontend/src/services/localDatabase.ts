/**
 * Local Database Service - Mode Hors Ligne
 * Base de données locale avec données initiales (migration)
 * Synchronisation avec Firebase/API quand en ligne
 */

// Types
export interface LocalUser {
  id: string;
  email: string;
  name: string;
  role: 'user' | 'manager' | 'admin';
  enterprise_id?: number;
  created_at: string;
}

export interface LocalStatus {
  id: number;
  name: string;
  description: string;
  color: string;
}

export interface LocalEnterprise {
  id: number;
  name: string;
  contact?: string;
  email?: string;
  phone?: string;
}

export interface LocalRoadwork {
  id: number;
  name: string;
  description: string;
  latitude: number;
  longitude: number;
  start_date: string;
  end_date?: string;
  status_id: number;
  enterprise_id?: number;
  budget?: number;
  surface?: number;
  status?: LocalStatus;
  enterprise?: LocalEnterprise;
}

export interface LocalReport {
  id: string;
  roadwork_id: number;
  user_id: string;
  user_email?: string;
  description: string;
  photo_url?: string;
  latitude?: number;
  longitude?: number;
  created_at: string;
  synced: boolean;
}

// Clés de stockage
const STORAGE_KEYS = {
  USERS: 'local_db_users',
  STATUSES: 'local_db_statuses',
  ENTERPRISES: 'local_db_enterprises',
  ROADWORKS: 'local_db_roadworks',
  REPORTS: 'local_db_reports',
  LAST_SYNC: 'local_db_last_sync',
  DB_VERSION: 'local_db_version',
  INITIALIZED: 'local_db_initialized'
};

// Version de la base (incrémenter pour forcer une réinitialisation)
const DB_VERSION = '1.0.0';

// ==========================================
// DONNÉES INITIALES (MIGRATION/SEED)
// ==========================================

const SEED_STATUSES: LocalStatus[] = [
  { id: 1, name: 'Planifié', description: 'Travaux planifiés, pas encore commencés', color: '#3498db' },
  { id: 2, name: 'En cours', description: 'Travaux en cours d\'exécution', color: '#f39c12' },
  { id: 3, name: 'Presque terminé', description: 'Travaux à plus de 80% d\'avancement', color: '#9b59b6' },
  { id: 4, name: 'Terminé', description: 'Travaux terminés et réceptionnés', color: '#27ae60' },
  { id: 5, name: 'Suspendu', description: 'Travaux temporairement arrêtés', color: '#e74c3c' }
];

const SEED_ENTERPRISES: LocalEnterprise[] = [
  { id: 1, name: 'COLAS Madagascar', contact: 'Jean Dupont', email: 'contact@colas.mg', phone: '+261 34 00 000 01' },
  { id: 2, name: 'SOGEA-SATOM', contact: 'Marie Rakoto', email: 'info@sogea-satom.mg', phone: '+261 34 00 000 02' },
  { id: 3, name: 'RAVINALA Construction', contact: 'Paul Andria', email: 'contact@ravinala.mg', phone: '+261 34 00 000 03' },
  { id: 4, name: 'ENTREPRISE GENERALE', contact: 'Sophie Rabe', email: 'eg@entreprise.mg', phone: '+261 34 00 000 04' },
  { id: 5, name: 'MICTSL', contact: 'André Ratsima', email: 'mictsl@mictsl.mg', phone: '+261 34 00 000 05' },
  { id: 6, name: 'Travaux Publics SA', contact: 'Luc Razafy', email: 'info@tpsa.mg', phone: '+261 34 00 000 06' }
];

const SEED_ROADWORKS: LocalRoadwork[] = [
  {
    id: 1,
    name: 'Route Nationale 7 - Tronçon Antsirabe',
    description: 'Réhabilitation de la RN7 entre Ambatolampy et Antsirabe, revêtement bitumineux',
    latitude: -19.8659,
    longitude: 47.0333,
    start_date: '2024-01-15',
    end_date: '2024-12-31',
    status_id: 2,
    enterprise_id: 1,
    budget: 450000000,
    surface: 25000
  },
  {
    id: 2,
    name: 'Boulevard de l\'Indépendance - Antananarivo',
    description: 'Élargissement et rénovation du boulevard principal',
    latitude: -18.9149,
    longitude: 47.5316,
    start_date: '2024-03-01',
    end_date: '2024-09-30',
    status_id: 3,
    enterprise_id: 2,
    budget: 280000000,
    surface: 12000
  },
  {
    id: 3,
    name: 'Route vers Ivato Aéroport',
    description: 'Aménagement de la route d\'accès à l\'aéroport international',
    latitude: -18.7969,
    longitude: 47.4788,
    start_date: '2024-02-15',
    end_date: '2024-08-15',
    status_id: 4,
    enterprise_id: 3,
    budget: 320000000,
    surface: 18000
  },
  {
    id: 4,
    name: 'Corniche Mahajanga',
    description: 'Réfection de la route côtière de Mahajanga',
    latitude: -15.7167,
    longitude: 46.3167,
    start_date: '2024-04-01',
    end_date: '2025-03-31',
    status_id: 1,
    enterprise_id: 4,
    budget: 180000000,
    surface: 8500
  },
  {
    id: 5,
    name: 'RN2 - Toamasina',
    description: 'Maintenance de la route nationale vers Toamasina',
    latitude: -18.1443,
    longitude: 49.3958,
    start_date: '2024-05-01',
    end_date: '2024-11-30',
    status_id: 2,
    enterprise_id: 5,
    budget: 95000000,
    surface: 15000
  },
  {
    id: 6,
    name: 'Avenue de France - Fianarantsoa',
    description: 'Pavage et aménagement urbain',
    latitude: -21.4417,
    longitude: 47.0856,
    start_date: '2024-06-15',
    end_date: '2024-12-15',
    status_id: 2,
    enterprise_id: 6,
    budget: 125000000,
    surface: 6000
  },
  {
    id: 7,
    name: 'Route des Épices - Nosy Be',
    description: 'Construction d\'une nouvelle route touristique',
    latitude: -13.3254,
    longitude: 48.2674,
    start_date: '2024-07-01',
    end_date: '2025-06-30',
    status_id: 1,
    enterprise_id: 1,
    budget: 220000000,
    surface: 9500
  },
  {
    id: 8,
    name: 'Bretelle Bypass Antananarivo Sud',
    description: 'Nouveau contournement sud de la capitale',
    latitude: -18.9792,
    longitude: 47.5200,
    start_date: '2023-09-01',
    end_date: '2024-06-30',
    status_id: 5,
    enterprise_id: 2,
    budget: 380000000,
    surface: 22000
  }
];

const SEED_REPORTS: LocalReport[] = [
  {
    id: 'local-report-1',
    roadwork_id: 1,
    user_id: 'demo-user',
    user_email: 'demo@example.com',
    description: 'Nid de poule important sur la chaussée principale',
    latitude: -19.8660,
    longitude: 47.0334,
    created_at: '2024-06-15T10:30:00Z',
    synced: true
  },
  {
    id: 'local-report-2',
    roadwork_id: 2,
    user_id: 'demo-user',
    user_email: 'demo@example.com',
    description: 'Signalisation temporaire manquante au croisement',
    latitude: -18.9150,
    longitude: 47.5317,
    created_at: '2024-06-20T14:15:00Z',
    synced: true
  },
  {
    id: 'local-report-3',
    roadwork_id: 3,
    user_id: 'demo-user',
    user_email: 'demo@example.com',
    description: 'Travaux terminés, route en excellent état',
    latitude: -18.7970,
    longitude: 47.4789,
    created_at: '2024-07-01T09:00:00Z',
    synced: true
  },
  {
    id: 'local-report-4',
    roadwork_id: 5,
    user_id: 'demo-user',
    user_email: 'demo@example.com',
    description: 'Accumulation d\'eau sur le bas-côté après les pluies',
    latitude: -18.1444,
    longitude: 49.3959,
    created_at: '2024-07-10T16:45:00Z',
    synced: true
  },
  {
    id: 'local-report-5',
    roadwork_id: 6,
    user_id: 'demo-user',
    user_email: 'demo@example.com',
    description: 'Travaux bruyants pendant les heures de repos',
    latitude: -21.4418,
    longitude: 47.0857,
    created_at: '2024-07-15T13:30:00Z',
    synced: true
  }
];

// ==========================================
// FONCTIONS UTILITAIRES
// ==========================================

const getItem = <T>(key: string): T | null => {
  try {
    const data = localStorage.getItem(key);
    return data ? JSON.parse(data) : null;
  } catch {
    return null;
  }
};

const setItem = <T>(key: string, data: T): void => {
  try {
    localStorage.setItem(key, JSON.stringify(data));
  } catch (e) {
    console.error('Erreur localStorage:', e);
  }
};

// ==========================================
// INITIALISATION DE LA BASE LOCALE
// ==========================================

/**
 * Initialise la base de données locale avec les données seed
 * Comme une migration dans Laravel
 */
export const initializeLocalDatabase = (): void => {
  const currentVersion = localStorage.getItem(STORAGE_KEYS.DB_VERSION);
  
  // Vérifier si besoin de réinitialiser
  if (currentVersion === DB_VERSION && localStorage.getItem(STORAGE_KEYS.INITIALIZED)) {
    console.log('📦 Base locale déjà initialisée (v' + DB_VERSION + ')');
    return;
  }
  
  console.log('🔄 Initialisation de la base locale (migration)...');
  
  // Insérer les données seed
  setItem(STORAGE_KEYS.STATUSES, SEED_STATUSES);
  setItem(STORAGE_KEYS.ENTERPRISES, SEED_ENTERPRISES);
  
  // Enrichir les roadworks avec status et enterprise
  const enrichedRoadworks = SEED_ROADWORKS.map(rw => ({
    ...rw,
    status: SEED_STATUSES.find(s => s.id === rw.status_id),
    enterprise: SEED_ENTERPRISES.find(e => e.id === rw.enterprise_id)
  }));
  setItem(STORAGE_KEYS.ROADWORKS, enrichedRoadworks);
  
  // Insérer les rapports seed
  setItem(STORAGE_KEYS.REPORTS, SEED_REPORTS);
  
  // Marquer comme initialisé
  localStorage.setItem(STORAGE_KEYS.DB_VERSION, DB_VERSION);
  localStorage.setItem(STORAGE_KEYS.INITIALIZED, 'true');
  
  console.log('✅ Base locale initialisée avec succès');
  console.log(`   - ${SEED_STATUSES.length} statuts`);
  console.log(`   - ${SEED_ENTERPRISES.length} entreprises`);
  console.log(`   - ${SEED_ROADWORKS.length} chantiers`);
  console.log(`   - ${SEED_REPORTS.length} signalements`);
};

/**
 * Force la réinitialisation de la base locale
 */
export const resetLocalDatabase = (): void => {
  Object.values(STORAGE_KEYS).forEach(key => {
    localStorage.removeItem(key);
  });
  initializeLocalDatabase();
  console.log('🔄 Base locale réinitialisée');
};

// ==========================================
// GETTERS - Lecture des données
// ==========================================

export const getStatuses = (): LocalStatus[] => {
  return getItem<LocalStatus[]>(STORAGE_KEYS.STATUSES) || SEED_STATUSES;
};

export const getEnterprises = (): LocalEnterprise[] => {
  return getItem<LocalEnterprise[]>(STORAGE_KEYS.ENTERPRISES) || SEED_ENTERPRISES;
};

export const getRoadworks = (): LocalRoadwork[] => {
  const roadworks = getItem<LocalRoadwork[]>(STORAGE_KEYS.ROADWORKS);
  if (!roadworks) {
    // Retourner les données seed enrichies
    return SEED_ROADWORKS.map(rw => ({
      ...rw,
      status: SEED_STATUSES.find(s => s.id === rw.status_id),
      enterprise: SEED_ENTERPRISES.find(e => e.id === rw.enterprise_id)
    }));
  }
  return roadworks;
};

export const getRoadworkById = (id: number): LocalRoadwork | null => {
  const roadworks = getRoadworks();
  return roadworks.find(rw => rw.id === id) || null;
};

export const getReports = (): LocalReport[] => {
  return getItem<LocalReport[]>(STORAGE_KEYS.REPORTS) || SEED_REPORTS;
};

export const getReportsByRoadwork = (roadworkId: number): LocalReport[] => {
  const reports = getReports();
  return reports.filter(r => r.roadwork_id === roadworkId);
};

export const getReportsByUser = (userId: string): LocalReport[] => {
  const reports = getReports();
  return reports.filter(r => r.user_id === userId);
};

export const getUnsyncedReports = (): LocalReport[] => {
  const reports = getReports();
  return reports.filter(r => !r.synced);
};

// ==========================================
// SETTERS - Écriture des données
// ==========================================

export const saveRoadworks = (roadworks: LocalRoadwork[]): void => {
  setItem(STORAGE_KEYS.ROADWORKS, roadworks);
  console.log(`💾 ${roadworks.length} chantiers sauvegardés localement`);
};

export const saveStatuses = (statuses: LocalStatus[]): void => {
  setItem(STORAGE_KEYS.STATUSES, statuses);
};

export const saveEnterprises = (enterprises: LocalEnterprise[]): void => {
  setItem(STORAGE_KEYS.ENTERPRISES, enterprises);
};

export const addReport = (report: Omit<LocalReport, 'id' | 'created_at' | 'synced'>): LocalReport => {
  const reports = getReports();
  const newReport: LocalReport = {
    ...report,
    id: `local-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    created_at: new Date().toISOString(),
    synced: false
  };
  reports.push(newReport);
  setItem(STORAGE_KEYS.REPORTS, reports);
  console.log('📝 Nouveau signalement ajouté localement:', newReport.id);
  return newReport;
};

export const markReportSynced = (reportId: string): void => {
  const reports = getReports();
  const index = reports.findIndex(r => r.id === reportId);
  if (index !== -1) {
    reports[index].synced = true;
    setItem(STORAGE_KEYS.REPORTS, reports);
  }
};

export const saveReports = (reports: LocalReport[]): void => {
  setItem(STORAGE_KEYS.REPORTS, reports);
};

// ==========================================
// STATISTIQUES
// ==========================================

export const getStatistics = () => {
  const roadworks = getRoadworks();
  const reports = getReports();
  const statuses = getStatuses();
  
  const byStatus: Record<string, number> = {};
  statuses.forEach(s => {
    byStatus[s.name] = roadworks.filter(r => r.status_id === s.id).length;
  });
  
  const totalBudget = roadworks.reduce((sum, r) => sum + (r.budget || 0), 0);
  const totalSurface = roadworks.reduce((sum, r) => sum + (r.surface || 0), 0);
  
  return {
    totalRoadworks: roadworks.length,
    totalReports: reports.length,
    unsyncedReports: reports.filter(r => !r.synced).length,
    byStatus,
    totalBudget,
    totalSurface,
    averageBudget: roadworks.length > 0 ? totalBudget / roadworks.length : 0
  };
};

// ==========================================
// SYNCHRONISATION
// ==========================================

export const getLastSyncTime = (): Date | null => {
  const timestamp = localStorage.getItem(STORAGE_KEYS.LAST_SYNC);
  return timestamp ? new Date(timestamp) : null;
};

export const updateLastSyncTime = (): void => {
  localStorage.setItem(STORAGE_KEYS.LAST_SYNC, new Date().toISOString());
};

/**
 * Synchronise les données du serveur vers la base locale
 */
export const syncFromServer = async (serverData: {
  roadworks?: LocalRoadwork[];
  statuses?: LocalStatus[];
  enterprises?: LocalEnterprise[];
  reports?: LocalReport[];
}): Promise<void> => {
  if (serverData.statuses && serverData.statuses.length > 0) {
    saveStatuses(serverData.statuses);
  }
  if (serverData.enterprises && serverData.enterprises.length > 0) {
    saveEnterprises(serverData.enterprises);
  }
  if (serverData.roadworks && serverData.roadworks.length > 0) {
    saveRoadworks(serverData.roadworks);
  }
  if (serverData.reports && serverData.reports.length > 0) {
    // Fusionner avec les rapports locaux non synchronisés
    const localReports = getReports();
    const unsyncedLocal = localReports.filter(r => !r.synced);
    const merged = [...serverData.reports.map(r => ({ ...r, synced: true })), ...unsyncedLocal];
    saveReports(merged);
  }
  updateLastSyncTime();
  console.log('🔄 Synchronisation depuis le serveur terminée');
};

/**
 * Vérifie si on est en ligne
 */
export const isOnline = (): boolean => {
  return navigator.onLine;
};

/**
 * Écoute les changements de connexion
 */
export const onConnectionChange = (callback: (online: boolean) => void): () => void => {
  const handleOnline = () => callback(true);
  const handleOffline = () => callback(false);
  
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  
  return () => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
  };
};

// Initialiser automatiquement au chargement du module
initializeLocalDatabase();

export default {
  // Init
  initializeLocalDatabase,
  resetLocalDatabase,
  // Getters
  getStatuses,
  getEnterprises,
  getRoadworks,
  getRoadworkById,
  getReports,
  getReportsByRoadwork,
  getReportsByUser,
  getUnsyncedReports,
  getStatistics,
  // Setters
  saveRoadworks,
  saveStatuses,
  saveEnterprises,
  addReport,
  markReportSynced,
  saveReports,
  // Sync
  getLastSyncTime,
  updateLastSyncTime,
  syncFromServer,
  isOnline,
  onConnectionChange
};
