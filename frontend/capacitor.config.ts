import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'mg.roadwatch.app',
  appName: 'RoadWatch',
  webDir: 'dist',
  server: {
    // Décommenter pour le dev avec live reload sur mobile
    // url: 'http://VOTRE_IP:5173',
    // cleartext: true
  }
};

export default config;
