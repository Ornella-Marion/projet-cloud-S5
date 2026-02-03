<template>
  <div v-if="isManager" class="manager-panel">
    <ion-card>
      <ion-card-header>
        <ion-card-title>Outils Manager</ion-card-title>
      </ion-card-header>
      <ion-card-content>
        <!-- Bouton pour inscrire un utilisateur -->
        <ion-button expand="block" color="primary" @click="showRegisterModal = true" class="manager-btn">
          <ion-icon slot="start" name="person-add"></ion-icon>
          Inscrire un utilisateur
        </ion-button>
        
        <!-- Bouton pour débloquer un compte -->
        <ion-button expand="block" color="danger" @click="showUnlockModal = true" class="manager-btn">
          <ion-icon slot="start" name="lock-open"></ion-icon>
          Débloquer un compte
        </ion-button>
      </ion-card-content>
    </ion-card>

    <!-- Modal pour inscrire un utilisateur -->
    <ion-modal :is-open="showRegisterModal" @didDismiss="showRegisterModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-buttons slot="start">
            <ion-button @click="showRegisterModal = false">Fermer</ion-button>
          </ion-buttons>
          <ion-title>Inscrire un utilisateur</ion-title>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <ion-item>
          <ion-label position="floating">Nom</ion-label>
          <ion-input v-model="newUser.name" type="text"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Email</ion-label>
          <ion-input v-model="newUser.email" type="email"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Mot de passe</ion-label>
          <ion-input v-model="newUser.password" type="password"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Rôle</ion-label>
          <ion-select v-model="newUser.role">
            <ion-select-option value="user">Utilisateur</ion-select-option>
            <ion-select-option value="visitor">Visiteur</ion-select-option>
          </ion-select>
        </ion-item>
        
        <ion-button expand="block" color="success" @click="registerUser" :disabled="registerLoading" class="register-btn">
          <ion-spinner v-if="registerLoading" name="crescent"></ion-spinner>
          <span v-else>Créer le compte</span>
        </ion-button>
        
        <div v-if="registerError" class="error-message">
          <ion-text color="danger">{{ registerError }}</ion-text>
        </div>
        <div v-if="registerSuccess" class="success-message">
          <ion-text color="success">{{ registerSuccess }}</ion-text>
        </div>
      </ion-content>
    </ion-modal>

    <!-- Modal pour débloquer un compte -->
    <ion-modal :is-open="showUnlockModal" @didDismiss="showUnlockModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-buttons slot="start">
            <ion-button @click="showUnlockModal = false">Fermer</ion-button>
          </ion-buttons>
          <ion-title>Débloquer un compte</ion-title>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <ion-list>
          <ion-item v-for="user in lockedUsers" :key="user.id">
            <ion-label>
              <h3>{{ user.name }}</h3>
              <p>{{ user.email }}</p>
            </ion-label>
            <ion-button slot="end" color="success" size="small" @click="unlockAccount(user.id)">
              Débloquer
            </ion-button>
          </ion-item>
        </ion-list>
        <p v-if="lockedUsers.length === 0" class="no-data">
          Aucun compte bloqué.
        </p>
      </ion-content>
    </ion-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonButton, IonIcon, IonModal, IonHeader, IonToolbar, IonButtons, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonSelect, IonSelectOption, IonSpinner, IonText } from '@ionic/vue'
import { useUserRole } from '../composables/useUserRole'
import api from '../services/api'
import { createUserWithEmailAndPassword } from 'firebase/auth'
import { auth } from '../firebase'

const { isManager } = useUserRole()

interface LockedUser {
  id: number
  name: string
  email: string
}

const showUnlockModal = ref(false)
const showRegisterModal = ref(false)
const lockedUsers = ref<LockedUser[]>([])
const loading = ref(false)

// Données pour l'inscription
const newUser = ref({
  name: '',
  email: '',
  password: '',
  role: 'user'
})
const registerLoading = ref(false)
const registerError = ref('')
const registerSuccess = ref('')

// Inscrire un nouvel utilisateur (Manager uniquement)
const registerUser = async () => {
  registerError.value = ''
  registerSuccess.value = ''
  
  // Validation
  if (!newUser.value.name.trim()) {
    registerError.value = 'Le nom est requis'
    return
  }
  if (!newUser.value.email.trim() || !/\S+@\S+\.\S+/.test(newUser.value.email)) {
    registerError.value = 'Email invalide'
    return
  }
  if (newUser.value.password.length < 6) {
    registerError.value = 'Le mot de passe doit contenir au moins 6 caractères'
    return
  }
  
  registerLoading.value = true
  
  try {
    // 1. D'abord créer dans Laravel
    console.log('📝 Manager: Création de l\'utilisateur dans Laravel...')
    const laravelRes = await api.post('/auth/manager-signup', {
      name: newUser.value.name,
      email: newUser.value.email,
      password: newUser.value.password,
      role: newUser.value.role
    })
    console.log('✅ Utilisateur créé dans Laravel:', laravelRes.data)
    
    // 2. Créer dans Firebase Auth
    console.log('🔥 Manager: Création de l\'utilisateur dans Firebase...')
    try {
      // Sauvegarder l'utilisateur manager actuel
      const currentUser = auth.currentUser
      
      // Créer le nouvel utilisateur dans Firebase
      await createUserWithEmailAndPassword(auth, newUser.value.email, newUser.value.password)
      console.log('✅ Utilisateur créé dans Firebase')
      
      // Se reconnecter avec le compte manager
      if (currentUser) {
        // Le manager sera déconnecté car Firebase connecte automatiquement le nouvel utilisateur
        // On doit lui demander de se reconnecter
        console.log('⚠️ Manager déconnecté de Firebase - reconnexion nécessaire')
      }
    } catch (firebaseError: any) {
      console.warn('⚠️ Erreur Firebase (non bloquante):', firebaseError.message)
      // Si l'erreur est "email déjà utilisé", l'utilisateur existe peut-être déjà dans Firebase
      if (firebaseError.code !== 'auth/email-already-in-use') {
        registerError.value = `Compte créé dans Laravel mais erreur Firebase: ${firebaseError.message}. L'utilisateur devra s'inscrire lui-même sur Firebase.`
      }
    }
    
    registerSuccess.value = `Utilisateur ${newUser.value.name} créé avec succès dans Laravel et Firebase!`
    
    // Reset du formulaire
    newUser.value = { name: '', email: '', password: '', role: 'user' }
    
    // Fermer le modal après 2 secondes
    setTimeout(() => {
      showRegisterModal.value = false
      registerSuccess.value = ''
      // Recharger la page pour que le manager se reconnecte
      window.location.reload()
    }, 2000)
    
  } catch (error: any) {
    console.error('❌ Erreur inscription:', error)
    registerError.value = error.response?.data?.error || error.response?.data?.message || 'Erreur lors de la création du compte'
  } finally {
    registerLoading.value = false
  }
}

// Récupérer les comptes bloqués
const fetchLockedUsers = async () => {
  if (!isManager.value) return

  loading.value = true
  try {
    const response = await api.get('/auth/locked-accounts')
    lockedUsers.value = response.data
  } catch (error) {
    console.error('Erreur lors de la récupération des comptes bloqués:', error)
  } finally {
    loading.value = false
  }
}

// Débloquer un compte
const unlockAccount = async (userId: number) => {
  try {
    await api.post(`/auth/unlock-account/${userId}`)
    // Retirer l'utilisateur de la liste
    lockedUsers.value = lockedUsers.value.filter(u => u.id !== userId)
    alert('Compte débloqué avec succès!')
  } catch (error: any) {
    console.error('Erreur lors du déblocage:', error)
    alert('Erreur lors du déblocage du compte')
  }
}

onMounted(() => {
  if (isManager.value) {
    fetchLockedUsers()
  }
})
</script>

<style scoped>
.manager-panel {
  margin-top: 20px;
  padding: 0 16px;
}

ion-card {
  box-shadow: 0 2px 8px rgba(255, 107, 107, 0.15);
}

ion-card-header {
  background: linear-gradient(135deg, #FF6B6B 0%, #FF5252 100%);
  color: white;
}

.manager-btn {
  margin-bottom: 10px;
}

.register-btn {
  margin-top: 20px;
}

.error-message {
  margin-top: 15px;
  padding: 10px;
  background: #fee;
  border-radius: 4px;
  text-align: center;
}

.success-message {
  margin-top: 15px;
  padding: 10px;
  background: #efe;
  border-radius: 4px;
  text-align: center;
}

.no-data {
  text-align: center;
  color: #999;
  font-style: italic;
  padding: 20px;
}
</style>
