<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Notifications</ion-title>
        <ion-buttons slot="start">
          <ion-back-button default-href="/dashboard" />
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content>
      <ion-list>
        <ion-item v-for="notif in notifications" :key="notif.id" :button="true" @click="markAsRead(notif)">
          <ion-label>
            <h3 :style="notif.read_at ? '' : 'font-weight:bold'">{{ notif.title }}</h3>
            <p>{{ notif.body }}</p>
            <small>{{ formatDate(notif.created_at) }}</small>
          </ion-label>
          <ion-badge v-if="!notif.read_at" color="primary">Nouveau</ion-badge>
        </ion-item>
      </ion-list>
      <ion-text v-if="notifications.length === 0">Aucune notification.</ion-text>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const notifications = ref<any[]>([]);
const router = useRouter();

function formatDate(date: string) {
  return new Date(date).toLocaleString('fr-FR');
}

async function fetchNotifications() {
  const res = await api.get('/api/notifications');
  notifications.value = res.data;
}

async function markAsRead(notif: any) {
  if (!notif.read_at) {
    await api.post(`/api/notifications/${notif.id}/read`);
    notif.read_at = new Date().toISOString();
  }
  // Redirection si la notification concerne un signalement
  if (notif.report_id) {
    router.push({ name: 'DetailSignalement', params: { id: notif.report_id } });
  }
}

onMounted(fetchNotifications);
</script>
