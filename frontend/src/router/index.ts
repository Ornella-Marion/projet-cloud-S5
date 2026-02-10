import NotificationsList from '../views/NotificationsList.vue';
import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import HomePage from '../views/HomePage.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import ForgotPassword from '../views/ForgotPassword.vue'
import Dashboard from '../views/Dashboard.vue'
import Report from '../views/Report.vue';
import ReportsList from '../views/ReportsList.vue';
import Map from '../views/Map.vue';
import { auth } from '../firebase'
import { onAuthStateChanged } from 'firebase/auth'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/report-detail/:id',
    name: 'DetailSignalement',
    component: () => import('../views/DetailSignalement.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'NotificationsList',
    component: NotificationsList,
    meta: { requiresAuth: true }
  },
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/home',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresAuth: false }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPassword,
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/report',
    name: 'Report',
    component: Report,
    meta: { requiresAuth: true }
  },
  {
    path: '/reports-list',
    name: 'ReportsList',
    component: ReportsList,
    meta: { requiresAuth: true }
  },
  {
    path: '/map',
    name: 'Map',
    component: Map,
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Guard de navigation
// Vérifie Firebase Auth ET le token Laravel (localStorage)
// Le token Laravel est la source principale d'authentification
let isAuthChecked = false;
let currentUser: any = null;
onAuthStateChanged(auth, (user) => {
  isAuthChecked = true;
  currentUser = user;
});

// Vérifier si l'utilisateur est authentifié (Firebase OU token Laravel)
const isUserAuthenticated = (): boolean => {
  // Token Laravel dans localStorage = authentifié
  const token = localStorage.getItem('token');
  if (token) return true;
  // Ou Firebase currentUser
  if (currentUser) return true;
  return false;
};

router.beforeEach((to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);

  // Si Firebase n'a pas encore vérifié, on attend SAUF si on a un token Laravel
  if (!isAuthChecked && !localStorage.getItem('token')) {
    const unwatch = onAuthStateChanged(auth, (user) => {
      isAuthChecked = true;
      currentUser = user;
      unwatch();
      proceed();
    });
  } else {
    proceed();
  }

  function proceed() {
    const authenticated = isUserAuthenticated();
    if (requiresAuth && !authenticated) {
      next('/login');
    } else if (!requiresAuth && authenticated && (to.path === '/login' || to.path === '/register')) {
      next('/dashboard');
    } else {
      next();
    }
  }
});

export default router
