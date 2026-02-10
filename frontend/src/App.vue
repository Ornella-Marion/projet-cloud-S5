<template>
  <ion-app>
    <AuthGuard />
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp } from '@ionic/vue';
import AuthGuard from './components/AuthGuard.vue';
</script>
