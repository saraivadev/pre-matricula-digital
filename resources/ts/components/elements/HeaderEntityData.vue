<template>
  <!-- Com logo própria (PMD_LOGO), ela já aparece no cabeçalho (Navbar.vue),
       então este bloco com nome e brasão não é exibido. -->
  <div v-if="!hasCustomLogo" class="container-sm col-10 col-lg-6 mt-4 pt-lg-4">
    <div class="d-flex align-items-center mb-5">
      <div class="font-muli" style="color: #003473">
        <div style="font-size: 24px">
          {{ getSlogan }}
        </div>
        <div style="font-size: 34px">
          {{ getEntityData.city }}
        </div>
      </div>
      <div class="ml-auto col-5">
        <img :src="getLogo" :alt="getEntityData.city" class="img-fluid" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useGeneralStore } from '@/store/general';

const store = useGeneralStore();

// Brasão padrão usado quando PMD_LOGO não é configurado (config/prematricula.php).
const DEFAULT_LOGO = '/intranet/imagens/brasao-republica.png';

const getEntityData = computed(() => store.getEntityData);
const getSlogan = computed(() => store.getSlogan);
const getLogo = computed(() => store.getLogo);
const hasCustomLogo = computed(
  () => !!getLogo.value && getLogo.value !== DEFAULT_LOGO
);
</script>
