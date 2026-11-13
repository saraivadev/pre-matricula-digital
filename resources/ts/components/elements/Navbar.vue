<template>
  <nav
    id="pmd_navbar"
    class="navbar navbar-expand-lg fixed-top navbar-light bg-withe d-flex justify-content-between"
  >
    <router-link
      to="/"
      class="navbar-brand font-size-title-pmd-xs-16 flex-grow-lg-1 pl-0 pl-lg-4 d-flex align-items-center"
    >
      <!-- Logo da prefeitura (PMD_LOGO) antes da marca do PMD -->
      <img
        v-if="hasCustomLogo"
        :src="store.logo"
        :alt="`${store.slogan}${store.entity.city}`"
        class="mr-3 pr-3"
        style="height: 44px; width: auto; border-right: 1px solid #dee2e6"
      />
      <icon class="mr-1" />
      Pré-matrícula Digital
    </router-link>
    <x-btn
      v-tooltip.bottom="'Abrir Menu'"
      icon="mdi-menu"
      data-toggle="collapse"
      data-target="#navbarSupportedContent"
      style="border-width: 1px !important"
      class="d-lg-none bg-white text-primary border-primary-light pr-3"
      size="lg"
      @click="toggleNavbar"
    />
    <div
      id="navbarSupportedContent"
      class="collapse navbar-collapse ml-auto text-right mt-4 mt-lg-0 d-lg-none"
      :class="{ show }"
    >
      <public-menu v-if="!isAuthenticated" :show="show" />
      <private-menu v-if="isAuthenticated" class="d-lg-none" />
    </div>
    <private-menu-desktop v-if="isAuthenticated" class="d-lg-block d-none" />
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import PrivateMenu from '@/components/elements/PrivateMenu.vue';
import PrivateMenuDesktop from '@/components/elements/PrivateMenuDesktop.vue';
import PublicMenu from '@/components/elements/PublicMenu.vue';
import XBtn from '@/components/elements/buttons/XBtn.vue';
import { useGeneralStore } from '@/store/general';
import { useRoute } from 'vue-router';

const route = useRoute();

const store = useGeneralStore();

const isAuthenticated = computed(() => store.isAuthenticated);

// Brasão padrão usado quando PMD_LOGO não é configurado (config/prematricula.php).
const DEFAULT_LOGO = '/intranet/imagens/brasao-republica.png';
const hasCustomLogo = computed(
  () => !!store.logo && store.logo !== DEFAULT_LOGO
);

const show = ref(false);

const toggleNavbar = () => {
  show.value = !show.value;
};

watch(
  [route],
  () => {
    show.value = false;
  },
  {
    deep: true,
  }
);
</script>
