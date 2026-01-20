import { defineStore } from 'pinia'

const BASE_URL = 'http://127.0.0.1:8000/api'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

    const token = computed(() => user.value?.bearerToken || null)
    const isAuthenticated = computed(() => !!token.value)

    const setUser = (newUser) => {
        user.value = newUser
        if (newUser) {
            localStorage.setItem('user', JSON.stringify(newUser))
        } else {
            localStorage.removeItem('user')
        }
    }

    const logout = () => {
        setUser(null)
    }

    const checkAuth = async () => {
        if (!token.value) {
            return false
        }

        try {
            const response = await fetch(`${BASE_URL}/me`, {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token.value}`,
                },
            })

            if (response.status === 401) {
                logout()
                return false
            }

            if (response.ok) {
                const data = await response.json()
                setUser(data.data.user)
            }

            return true
        } catch (error) {
            logout()
            return false
        }
    }

    return {
        token,
        user,
        isAuthenticated,
        setUser,
        logout,
        checkAuth,
    }
})
