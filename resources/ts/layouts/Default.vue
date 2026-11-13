<template>
  <div :class="background">
    <navbar />
    <main id="content" class="container">
      <router-view v-slot="{ Component }">
        <transition name="scale" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    <footer id="footer" class="pt-5 pb-5 text-center text-muted small">
      <div>
        © {{ year }} Prefeitura Municipal de Colônia do Gurguéia · Implantação
        e suporte: <strong>Gurgueia Tech</strong>
      </div>
      <div class="mt-1">
        Pré-Matrícula Digital, software livre (AGPL-3.0) desenvolvido pela
        Portabilis ·
        <a
          href="https://github.com/portabilis/pre-matricula-digital"
          target="_blank"
          rel="noopener"
          class="text-muted"
          >código-fonte</a
        >
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import Navbar from '@/components/elements/Navbar.vue';
import { computed } from 'vue';
import { getFormattedYearFromNow } from '@/datetime';
import { useRoute } from 'vue-router';

const route = useRoute();

const background = computed(() => ({
  'bg-white': !(route.meta.public || false),
}));

const year = computed(() => getFormattedYearFromNow());
</script>

<style scoped>
.scale-enter-active,
.scale-leave-active {
  transition: all 0.2s ease;
}

.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
</style>
