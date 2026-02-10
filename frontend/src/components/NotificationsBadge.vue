<template>
  <ion-badge v-if="unreadCount > 0" color="danger">{{ unreadCount }}</ion-badge>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../services/api';

const unreadCount = ref(0);

async function fetchUnread() {
  try {
    const res = await api.get('/api/notifications/unread-count');
    unreadCount.value = res.data.count || 0;
  } catch {
    unreadCount.value = 0;
  }
}

onMounted(fetchUnread);
</script>
