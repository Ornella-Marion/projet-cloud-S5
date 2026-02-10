<template>
  <div v-if="isLoading" class="auth-loading">
    <ion-spinner name="crescent" class="loading-spinner"></ion-spinner>
    <p>Vérification de l'authentification...</p>
  </div>
  <router-view v-else />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { IonSpinner } from '@ionic/vue'
import { onAuthStateChanged } from 'firebase/auth'
import { auth } from '../firebase'

const isLoading = ref(true)

onMounted(() => {
  const unsubscribe = onAuthStateChanged(auth, (user) => {
    isLoading.value = false
    unsubscribe() // Se désabonner après la première vérification
  })
})
</script>

<style scoped>
.auth-loading {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  margin-bottom: 20px;
}

.auth-loading p {
  font-size: 16px;
  opacity: 0.8;
}
</style>
