import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { auth } from '../firebase'

interface UserData {
  id: number
  email: string
  name: string
  role: 'visitor' | 'user' | 'manager'
  is_active: boolean
}

const userRole = ref<string | null>(null)
const userData = ref<UserData | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

export function useUserRole() {
  // Récupérer les informations utilisateur depuis Laravel
  const fetchUserRole = async () => {
    // Vérifier si on a un token Laravel (source principale d'auth)
    const token = localStorage.getItem('token')
    if (!auth.currentUser && !token) {
      userRole.value = null
      userData.value = null
      return
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/auth/me')
      const user = response.data.user || response.data
      userData.value = user
      userRole.value = user.role
      console.log(`👤 Rôle utilisateur: ${userRole.value}`)
    } catch (err: any) {
      console.warn('⚠️ Impossible de récupérer le rôle utilisateur:', err.message)
      // Si l'endpoint n'existe pas, on définit un rôle par défaut
      userRole.value = 'user'
    } finally {
      isLoading.value = false
    }
  }

  // Vérifications de permissions
  const isManager = computed(() => userRole.value === 'manager')
  const isUser = computed(() => userRole.value === 'user')
  const isVisitor = computed(() => userRole.value === 'visitor')
  const isAuthenticated = computed(() => auth.currentUser !== null || !!localStorage.getItem('token'))

  // Vérifier une permission spécifique
  const hasRole = (role: string | string[]): boolean => {
    if (Array.isArray(role)) {
      return role.includes(userRole.value || '')
    }
    return userRole.value === role
  }

  // Vérifier si l'utilisateur peut créer un signalement
  const canCreateReport = computed(() => {
    return isAuthenticated.value && (isManager.value || isUser.value)
  })

  // Vérifier si l'utilisateur peut débloquer un compte
  const canUnlockAccount = computed(() => {
    return isManager.value
  })

  // Vérifier si l'utilisateur peut accéder à la carte
  const canAccessMap = computed(() => {
    return isAuthenticated.value
  })

  // Vérifier si l'utilisateur peut accéder aux signalements
  const canViewReports = computed(() => {
    return isAuthenticated.value && (isManager.value || isUser.value)
  })

  // Initialiser automatiquement
  onMounted(() => {
    fetchUserRole()
  })

  return {
    // État
    userRole,
    userData,
    isLoading,
    error,

    // Permissions
    isManager,
    isUser,
    isVisitor,
    isAuthenticated,
    canCreateReport,
    canUnlockAccount,
    canAccessMap,
    canViewReports,

    // Méthodes
    fetchUserRole,
    hasRole
  }
}
