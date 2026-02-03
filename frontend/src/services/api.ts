import axios from 'axios';
import { signOut } from 'firebase/auth';
import { auth } from '../firebase';

const api = axios.create({
  baseURL: 'http://localhost:8000/api', // URL du backend Laravel
  headers: {
    'Content-Type': 'application/json',
  },
});

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
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Token invalide, déconnecter Firebase, supprimer token et rediriger
      await signOut(auth);
      localStorage.removeItem('token');
      window.location.href = '/login';
      return Promise.resolve();
    }
    return Promise.reject(error);
  }
);

export default api;