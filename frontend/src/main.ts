import { initFCM, requestNotificationPermission, getFCMToken, onFCMMessage } from './services/fcm';
import api from './services/api';
// --- FCM Notifications ---
async function setupFCM() {
  const supported = await initFCM();
  if (!supported) {
    console.warn('Notifications push non supportées sur ce navigateur.');
    return;
  }
  const granted = await requestNotificationPermission();
  if (!granted) {
    console.warn('Permission notifications refusée.');
    return;
  }
  // Récupérer le token FCM
  const vapidKey = undefined; // Ajoutez votre clé VAPID si besoin
  const token = await getFCMToken(vapidKey);
  if (token) {
    // Envoyer le token à l'API
    try {
      await api.post('/api/fcm-token', { token });
      console.log('FCM token envoyé à l’API:', token);
    } catch (e) {
      console.error('Erreur envoi token FCM à l’API', e);
    }
  } else {
    console.warn('Impossible de récupérer le token FCM');
  }
  // Gérer la réception des notifications push
  onFCMMessage((payload) => {
    console.log('Notification push reçue:', payload);
    // TODO: Afficher notification, badge, etc.
  });
}

setupFCM();
import { createApp } from 'vue'
import App from './App.vue'
import router from './router';

import { IonicVue } from '@ionic/vue';
import { addIcons } from 'ionicons';
import {
  camera, image, trash, map, list, alertCircle, personAdd, lockOpen,
  cloudDone, cloudOffline, logIn, refresh, warning, navigate, close,
  add, remove, home, location, checkmarkCircle, logOut
} from 'ionicons/icons';

/* Core CSS required for Ionic components to work properly */
import '@ionic/vue/css/core.css';

/* Basic CSS for apps built with Ionic */
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';

/* Optional CSS utils that can be commented out */
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/float-elements.css';
import '@ionic/vue/css/text-alignment.css';
import '@ionic/vue/css/text-transformation.css';
import '@ionic/vue/css/flex-utils.css';
import '@ionic/vue/css/display.css';

/**
 * Ionic Dark Mode
 * -----------------------------------------------------
 * DÉSACTIVÉ : Le thème sombre causait du texte blanc sur fond blanc
 * car nos composants custom utilisent des couleurs codées en dur.
 */

/* @import '@ionic/vue/css/palettes/dark.always.css'; */
/* @import '@ionic/vue/css/palettes/dark.class.css'; */
/* import '@ionic/vue/css/palettes/dark.system.css'; -- DÉSACTIVÉ */

/* Theme variables */
import './theme/variables.css';

/* Initialiser la base de données locale (migration) */
import { initializeLocalDatabase } from './services/localDatabase';
initializeLocalDatabase();
console.log('📦 Base de données locale initialisée');

// Register commonly used Ionicons globally to avoid runtime URL errors
addIcons({
  camera, image, trash, map, list, add, remove, home, close, refresh, warning, navigate, location,
  'alert-circle': alertCircle,
  'person-add': personAdd,
  'lock-open': lockOpen,
  'cloud-done': cloudDone,
  'cloud-offline': cloudOffline,
  'log-in': logIn,
  'log-out': logOut,
  'checkmark-circle': checkmarkCircle,
});

const app = createApp(App)
  .use(IonicVue)
  .use(router);

router.isReady().then(() => {
  app.mount('#app');
});
