import axios from 'axios';
import { signOut } from 'firebase/auth';
import { auth } from '../firebase';

// L'API passe par le proxy Vite (/api -> http://localhost:8000/api)
// Cela évite les problèmes CORS et mixed content HTTP/HTTPS
const api = axios.create({
  baseURL: '/api',
  // Ne PAS définir Content-Type ici, axios le fait automatiquement
  // Pour JSON → application/json
  // Pour FormData → multipart/form-data avec boundary
});

console.log('🌐 API via proxy Vite:', window.location.origin + '/api');

// Intercepteur pour ajouter le token JWT
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      // Log pour déboguer les requêtes authentifiées
      console.log(`🔑 Requête API: ${config.method?.toUpperCase()} ${config.url}`);
    } else {
      console.warn(`⚠️ Requête API sans token: ${config.method?.toUpperCase()} ${config.url}`);
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs de réponse
let isRedirecting = false;
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401 && !isRedirecting) {
      const url = error.config?.url || '';
      // Ne pas rediriger si c'est un appel /auth/login ou /auth/me (éviter boucle)
      if (url.includes('/auth/login') || url.includes('/auth/signup')) {
        return Promise.reject(error);
      }
      console.warn('🔒 Token expiré ou invalide, déconnexion...');
      isRedirecting = true;
      try { await signOut(auth); } catch(e) { /* ignore */ }
      localStorage.removeItem('token');
      window.location.href = '/login';
      return Promise.resolve();
    }
    return Promise.reject(error);
  }
);

export default api;