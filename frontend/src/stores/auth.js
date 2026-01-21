import { defineStore } from 'pinia'
import request from '@/utils/request'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

    const isAuthenticated = computed(() => !!user.value)

    const setUser = (newUser) => {
        user.value = newUser
        if (newUser) {
            localStorage.setItem('user', JSON.stringify(newUser))
        } else {
            localStorage.removeItem('user')
        }
    }

    const logout = async () => {
        try {
            await request.post('/logout')
        } catch (error) {
            // Ignore logout errors
        }
        setUser(null)
    }

    const checkAuth = async () => {
        if (!user.value) {
            return false
        }

        try {
            const response = await request.get('/me')

            if (response?.data?.user) {
                setUser(response.data.user)
                return true
            }

            setUser(null)
            return false
        } catch (error) {
            if (error.status === 401) {
                setUser(null)
            }
            return false
        }
    }

    return {
        user,
        isAuthenticated,
        setUser,
        logout,
        checkAuth,
    }
})
