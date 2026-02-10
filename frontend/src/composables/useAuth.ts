import { ref, computed, onMounted, onUnmounted } from 'vue'
import { User, onAuthStateChanged, signOut as firebaseSignOut } from 'firebase/auth'
import { auth } from '../firebase'

const user = ref<User | null>(null)
const isLoading = ref(true)
let unsubscribe: (() => void) | null = null

export function useAuth() {
  // État réactif de l'utilisateur
  const isAuthenticated = computed(() => user.value !== null)
  const userEmail = computed(() => user.value?.email || '')
  const userId = computed(() => user.value?.uid || '')

  // Initialiser l'écouteur d'état d'authentification
  const initAuth = () => {
    if (unsubscribe) return // Éviter les doublons

    unsubscribe = onAuthStateChanged(auth, (firebaseUser) => {
      user.value = firebaseUser
      isLoading.value = false

      console.log('🔐 État d\'authentification changé:', firebaseUser ? 'Connecté' : 'Déconnecté')
    })
  }

  // Se déconnecter
  const signOut = async () => {
    try {
      // Supprimer le token Laravel du localStorage
      localStorage.removeItem('token')
      console.log('✅ Token Laravel supprimé')
      
      await firebaseSignOut(auth)
      console.log('✅ Déconnexion Firebase réussie')
    } catch (error) {
      console.error('❌ Erreur lors de la déconnexion:', error)
      // Même en cas d'erreur, supprimer le token
      localStorage.removeItem('token')
      throw error
    }
  }

  // Nettoyer l'écouteur
  const cleanup = () => {
    if (unsubscribe) {
      unsubscribe()
      unsubscribe = null
    }
  }

  // Initialiser automatiquement
  onMounted(() => {
    initAuth()
  })

  // Nettoyer automatiquement
  onUnmounted(() => {
    cleanup()
  })

  return {
    // État
    user: computed(() => user.value),
    isAuthenticated,
    isLoading,
    userEmail,
    userId,

    // Actions
    signOut,
    initAuth,
    cleanup
  }
}
