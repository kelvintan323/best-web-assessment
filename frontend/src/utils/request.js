const BASE_URL = import.meta.env.VITE_API_URL || '/api'
const DEFAULT_TIMEOUT = 10000 // 10 seconds

// Get XSRF token from cookies
const getXsrfToken = () => {
	const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
	return match ? decodeURIComponent(match[1]) : null
}

// Fetch CSRF cookie before login
export const fetchCsrfCookie = async () => {
	await fetch('/sanctum/csrf-cookie', {
		credentials: 'include',
	})
}

const handleRequest = async (endpoint, config) => {
	const url = `${BASE_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`

	const isFormData = config.body instanceof FormData
	const xsrfToken = getXsrfToken()

	const headers = {
		Accept: 'application/json',
		...(!isFormData && { 'Content-Type': 'application/json' }),
		...(xsrfToken && { 'X-XSRF-TOKEN': xsrfToken }),
		...config.headers,
	}

	const controller = new AbortController()
	const timeout = config.timeout || DEFAULT_TIMEOUT

	const timeoutId = setTimeout(() => {
		controller.abort()
	}, timeout)

	try {
		const response = await fetch(url, {
			method: config.method,
			headers,
			body: config.body ? (isFormData ? config.body : JSON.stringify(config.body)) : null,
			signal: controller.signal,
			credentials: 'include',
		})

		clearTimeout(timeoutId)

		const contentType = response.headers.get('content-type')
		const data = contentType?.includes('application/json')
			? await response.json()
			: await response.text()

		if (!response.ok) {
			const errorResponse = {
				message: data?.message || `HTTP error! status: ${response.status}`,
				status: response.status,
				data,
			}
			throw errorResponse
		}

		return data
	} catch (error) {
		clearTimeout(timeoutId)

		if (error.name === 'AbortError') {
			const timeoutError = {
				message: `Request timeout after ${timeout}ms`,
				status: 408, // 408 Request Timeout
				data: { timeout },
			}
			throw handleError(timeoutError)
		}

		throw handleError(error)
	}
}

const handleError = (error) => {
	if (error.status && error.message) {
		const errorResponse = error

		switch (errorResponse.status) {
			case 400:
				console.error('Bad Request:', errorResponse.message)
				break
			case 401:
				console.error('Unauthorized - Please login')
				break
			case 403:
				console.error("Forbidden - You don't have permission")
				break
			case 404:
				console.error('Not Found:', errorResponse.message)
				break
			case 408:
				console.error('Request Timeout:', errorResponse.message)
				break
			case 413:
				console.error('Payload Too Large - File or request is too big')
				break
			case 419:
				console.error('CSRF Token Mismatch')
				break
			case 422:
				console.error('Validation Error:', errorResponse.data)
				break
			case 429:
				console.error('Too Many Requests - Please try again later')
				break
			case 500:
				console.error('Server Error:', errorResponse.message)
				break
			case 503:
				console.error('Service Unavailable - Server is down')
				break
			default:
				console.error('API Error:', errorResponse)
		}

		return errorResponse
	}

	console.error('Network or unexpected error:', error)

	const genericError = {
		message: error.message || 'An unexpected error occurred',
		status: 0,
		data: error,
	}

	return genericError
}

const handleBlobRequest = async (endpoint, config) => {
	const url = `${BASE_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`

	const xsrfToken = getXsrfToken()

	const headers = {
		...(xsrfToken && { 'X-XSRF-TOKEN': xsrfToken }),
		...config.headers,
	}

	const controller = new AbortController()
	const timeout = config.timeout || DEFAULT_TIMEOUT

	const timeoutId = setTimeout(() => {
		controller.abort()
	}, timeout)

	try {
		const response = await fetch(url, {
			method: config.method,
			headers,
			signal: controller.signal,
			credentials: 'include',
		})

		clearTimeout(timeoutId)

		if (!response.ok) {
			const errorResponse = {
				message: `HTTP error! status: ${response.status}`,
				status: response.status,
			}
			throw errorResponse
		}

		return await response.blob()
	} catch (error) {
		clearTimeout(timeoutId)

		if (error.name === 'AbortError') {
			const timeoutError = {
				message: `Request timeout after ${timeout}ms`,
				status: 408,
				data: { timeout },
			}
			throw handleError(timeoutError)
		}

		throw handleError(error)
	}
}

const request = {
	get: (endpoint, options) =>
		handleRequest(endpoint, {
			method: 'GET',
			headers: options?.headers,
			timeout: options?.timeout,
		}),

	getBlob: (endpoint, options) =>
		handleBlobRequest(endpoint, {
			method: 'GET',
			headers: options?.headers,
			timeout: options?.timeout,
		}),

	post: (endpoint, options) =>
		handleRequest(endpoint, {
			method: 'POST',
			body: options?.body,
			headers: options?.headers,
			timeout: options?.timeout,
		}),

	put: (endpoint, options) =>
		handleRequest(endpoint, {
			method: 'PUT',
			body: options?.body,
			headers: options?.headers,
			timeout: options?.timeout,
		}),

	delete: (endpoint, options) =>
		handleRequest(endpoint, {
			method: 'DELETE',
			headers: options?.headers,
			timeout: options?.timeout,
		}),
}

export default request
