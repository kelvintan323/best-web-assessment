import { defineStore } from 'pinia'

export const useSnackbarStore = defineStore('snackbar', () => {
    const show = ref(false)
    const message = ref('')
    const color = ref('success')

    const showSnackbar = (msg, type = 'success') => {
        message.value = msg
        color.value = type
        show.value = true
    }

    const hideSnackbar = () => {
        show.value = false
    }

    return {
        show,
        message,
        color,
        showSnackbar,
        hideSnackbar,
    }
})
