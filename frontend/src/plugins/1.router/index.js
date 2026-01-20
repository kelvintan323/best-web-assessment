import { setupLayouts } from 'virtual:meta-layouts'
import { createRouter, createWebHistory } from 'vue-router/auto'
import { useAuthStore } from '@/stores/auth'

function recursiveLayouts(route) {
	if (route.children) {
		for (let i = 0; i < route.children.length; i++)
			route.children[i] = recursiveLayouts(route.children[i])

		return route
	}

	return setupLayouts([route])[0]
}

const router = createRouter({
	history: createWebHistory(import.meta.env.BASE_URL),
	scrollBehavior(to) {
		if (to.hash) return { el: to.hash, behavior: 'smooth', top: 60 }

		return { top: 0 }
	},
	extendRoutes: (pages) => [...[...pages].map((route) => recursiveLayouts(route))],
})

const publicRoutes = ['/login']

router.beforeEach(async (to, from, next) => {
	const authStore = useAuthStore()
	const isPublicRoute = publicRoutes.includes(to.path)

	await authStore.checkAuth()

	if (!authStore.isAuthenticated && !isPublicRoute) {
		next('/login')
	} else if (authStore.isAuthenticated && to.path === '/login') {
		next('/')
	} else {
		next()
	}
})

export { router }
export default function (app) {
	app.use(router)
}
