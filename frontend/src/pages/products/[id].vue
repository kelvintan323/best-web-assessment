<script setup>
import request from '@/utils/request'
import { useSnackbarStore } from '@/stores/snackbar'
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

definePage({
	path: '/products/:id(\\d+|new)',
})

const route = useRoute()
const router = useRouter()
const snackbar = useSnackbarStore()

const product = ref(null)
const loading = ref(false)
const saving = ref(false)
const categories = ref([])
const formRef = ref(null)

const form = ref({
	name: '',
	category_id: null,
	description: '',
	price: null,
	stock: null,
	is_enabled: true,
})

const rules = {
	name: [(v) => !!v || 'Name is required'],
	category_id: [(v) => !!v || 'Category is required'],
	price: [
		(v) => (v !== null && v !== '') || 'Price is required',
		(v) => v >= 0 || 'Price must be positive',
	],
	stock: [
		(v) => (v !== null && v !== '') || 'Stock is required',
		(v) => v >= 0 || 'Stock must be positive',
	],
}

const isNewProduct = route.params.id === 'new'

const fetchProduct = () => {
	if (isNewProduct) return

	loading.value = true
	request
		.get(`/products/${route.params.id}`)
		.then((response) => {
			product.value = response.data.product
			form.value = {
				name: product.value.name,
				category_id: product.value.category_id,
				description: product.value.description || '',
				price: product.value.price / 100,
				stock: product.value.stock,
				is_enabled: Boolean(product.value.is_enabled),
			}
		})
		.catch((error) => {
			if (error.status === 404) {
				router.replace('/404')
			} else {
				snackbar.showSnackbar('Failed to load product', 'error')
				router.push('/')
			}
		})
		.finally(() => {
			loading.value = false
		})
}

const fetchCategories = () => {
	request.get('/categories').then((response) => {
		categories.value = response.data.categories
	})
}

const capitalizedLabel = (value) => {
	return value ? 'Enabled' : 'Disabled'
}

const saveProduct = async () => {
	const { valid } = await formRef.value.validate()
	if (!valid) return

	saving.value = true
	const payload = {
		...form.value,
		price: Math.round(form.value.price * 100),
	}

	const apiCall = isNewProduct
		? request.post('/products', { body: payload })
		: request.put(`/products/${route.params.id}`, { body: payload })

	apiCall
		.then((response) => {
			snackbar.showSnackbar(
				isNewProduct ? 'Product created successfully' : 'Product updated successfully'
			)
			if (isNewProduct) {
				router.push('/')
			} else {
				product.value = response.data.product
			}
		})
		.catch(() => {
			snackbar.showSnackbar('Failed to save product', 'error')
		})
		.finally(() => {
			saving.value = false
		})
}

const goBack = () => {
	router.push('/')
}

onMounted(() => {
	fetchCategories()
	fetchProduct()
})
</script>

<template>
	<div>
		<VCard :loading="loading">
			<template #title>
				<div class="d-flex align-center">
					<VBtn
						icon
						variant="text"
						class="me-2"
						@click="goBack">
						<VIcon icon="tabler-arrow-left" />
					</VBtn>
					<span>{{ isNewProduct ? 'Create Product' : 'Edit Product' }}</span>
				</div>
			</template>

			<VCardText v-if="!loading">
				<VForm ref="formRef">
					<VRow>
						<VCol
							cols="12"
							md="6">
							<VTextField
								v-model="form.name"
								label="Name"
								:rules="rules.name" />
						</VCol>
						<VCol
							cols="12"
							md="6">
							<VSelect
								v-model="form.category_id"
								label="Category"
								:items="categories"
								item-title="name"
								item-value="id"
								:rules="rules.category_id" />
						</VCol>
						<VCol cols="12">
							<VTextarea
								v-model="form.description"
								label="Description"
								rows="4" />
						</VCol>
						<VCol
							cols="12"
							md="4">
							<VTextField
								v-model.number="form.price"
								label="Price"
								type="number"
								prefix="$"
								:rules="rules.price" />
						</VCol>
						<VCol
							cols="12"
							md="4">
							<VTextField
								v-model.number="form.stock"
								label="Stock"
								type="number"
								:rules="rules.stock" />
						</VCol>
						<VCol
							cols="12"
							md="4">
							<VSwitch
								v-model="form.is_enabled"
								:label="capitalizedLabel(form.is_enabled)"
								color="primary" />
						</VCol>
					</VRow>
				</VForm>
			</VCardText>

			<VCardActions>
				<VSpacer />
				<VBtn
					variant="text"
					@click="goBack">
					Cancel
				</VBtn>
				<VBtn
					color="primary"
					:loading="saving"
					@click="saveProduct">
					{{ isNewProduct ? 'Create' : 'Save' }}
				</VBtn>
			</VCardActions>
		</VCard>
	</div>
</template>
