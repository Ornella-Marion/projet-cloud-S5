import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import HomePage from '../views/HomePage.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import ForgotPassword from '../views/ForgotPassword.vue'
import Dashboard from '../views/Dashboard.vue'
import Report from '../views/Report.vue';
import ReportsList from '../views/ReportsList.vue';
import { auth } from '../firebase'
import { onAuthStateChanged } from 'firebase/auth'

const routes: Array<RouteRecordRaw> = [
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
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Guard de navigation (corrigé pour éviter les appels multiples à onAuthStateChanged)
let isAuthChecked = false;
let currentUser: any = null;
onAuthStateChanged(auth, (user) => {
  isAuthChecked = true;
  currentUser = user;
});

router.beforeEach((to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  // Si l'état d'auth n'est pas encore connu, attendre
  if (!isAuthChecked) {
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
    if (requiresAuth && !currentUser) {
      next('/login');
    } else if (!requiresAuth && currentUser && (to.path === '/login' || to.path === '/register')) {
      next('/dashboard');
    } else {
      next();
    }
  }
});

export default router
