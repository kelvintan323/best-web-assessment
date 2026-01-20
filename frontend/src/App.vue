<script setup>
import { useTheme } from 'vuetify'
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import {
  initConfigStore,
  useConfigStore,
} from '@core/stores/config'
import { hexToRgb } from '@core/utils/colorConverter'
import { useSnackbarStore } from '@/stores/snackbar'

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()
const snackbarStore = useSnackbarStore()
</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
    <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
      <RouterView />

      <ScrollToTop />

      <VSnackbar
        v-model="snackbarStore.show"
        :color="snackbarStore.color"
        :timeout="3000"
        location="top end"
      >
        {{ snackbarStore.message }}
        <template #actions>
          <VBtn variant="text" @click="snackbarStore.hideSnackbar()">
            Close
          </VBtn>
        </template>
      </VSnackbar>
    </VApp>
  </VLocaleProvider>
</template>
