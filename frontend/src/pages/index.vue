<script setup>
import request from '@/utils/request'
import { useSnackbarStore } from '@/stores/snackbar'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const snackbar = useSnackbarStore()

const products = ref([])
const loading = ref(false)
const totalItems = ref(0)
const itemsPerPage = ref(10)
const currentPage = ref(1)
const sortBy = ref([])
let requestId = 0

// Filters
const filterStatus = ref(null)
const filterCategory = ref(null)
const categories = ref([])
const statusOptions = [
	{ title: 'All', value: null },
	{ title: 'Active', value: 1 },
	{ title: 'Inactive', value: 0 },
]

const headers = [
	{ title: 'Name', key: 'name' },
	{ title: 'Category', key: 'category.name', sortable: false },
	{ title: 'Price', key: 'price' },
	{ title: 'Stock', key: 'stock' },
	{ title: 'Status', key: 'is_enabled' },
	{ title: 'Actions', key: 'actions', sortable: false },
]

// Delete dialog
const deleteDialog = ref(false)
const deleteLoading = ref(false)
const deletingProduct = ref(null)

// Bulk delete
const selected = ref([])
const bulkDeleteDialog = ref(false)
const bulkDeleteLoading = ref(false)

const fetchProducts = () => {
	loading.value = true
	const currentRequestId = ++requestId

	const params = new URLSearchParams({
		page: currentPage.value,
		per_page: itemsPerPage.value,
	})

	if (sortBy.value.length > 0) {
		params.append('sort_key', sortBy.value[0].key)
		params.append('sort_order', sortBy.value[0].order)
	}

	if (filterStatus.value !== null) {
		params.append('status', filterStatus.value)
	}

	if (filterCategory.value !== null) {
		params.append('category_id', filterCategory.value)
	}

	request
		.get(`/products?${params.toString()}`)
		.then((response) => {
			if (currentRequestId !== requestId) return
			products.value = response.data.products.data
			totalItems.value = response.data.products.total
		})
		.finally(() => {
			if (currentRequestId !== requestId) return
			loading.value = false
		})
}

const fetchCategories = () => {
	request.get('/categories').then((response) => {
		categories.value = response.data.categories
	})
}

const formatPrice = (price) => {
	return new Intl.NumberFormat('en-US', {
		style: 'currency',
		currency: 'USD',
	}).format(price / 100)
}

const onUpdateOptions = (options) => {
	currentPage.value = options.page
	itemsPerPage.value = options.itemsPerPage
	sortBy.value = options.sortBy
	fetchProducts()
}

const applyFilters = () => {
	currentPage.value = 1
	fetchProducts()
}

const goToCreate = () => {
	router.push('/products/new')
}

const goToEdit = (product) => {
	router.push(`/products/${product.id}`)
}

const openDeleteDialog = (product) => {
	deletingProduct.value = product
	deleteDialog.value = true
}

const deleteProduct = () => {
	deleteLoading.value = true
	request
		.delete(`/products/${deletingProduct.value.id}`)
		.then(() => {
			deleteDialog.value = false
			fetchProducts()
			snackbar.showSnackbar('Product deleted successfully')
		})
		.finally(() => {
			deleteLoading.value = false
		})
}

const openBulkDeleteDialog = () => {
	bulkDeleteDialog.value = true
}

const bulkDeleteProducts = () => {
	bulkDeleteLoading.value = true
	request
		.post('/products/bulk-delete', { body: { ids: selected.value } })
		.then(() => {
			bulkDeleteDialog.value = false
			selected.value = []
			fetchProducts()
			snackbar.showSnackbar('Products deleted successfully')
		})
		.finally(() => {
			bulkDeleteLoading.value = false
		})
}

const exportLoading = ref(false)

const exportProducts = async () => {
	const params = new URLSearchParams()

	if (filterStatus.value !== null) {
		params.append('status', filterStatus.value)
	}

	if (filterCategory.value !== null) {
		params.append('category_id', filterCategory.value)
	}

	exportLoading.value = true

	try {
		const blob = await request.getBlob(`/products/export?${params.toString()}`)
		const url = URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = url
		link.download = `products_${new Date().toISOString().slice(0, 10)}.xlsx`
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		URL.revokeObjectURL(url)

		snackbar.showSnackbar('Products exported successfully')
	} catch {
		snackbar.showSnackbar('Failed to export products', 'error')
	} finally {
		exportLoading.value = false
	}
}

onMounted(() => {
	fetchCategories()
})
</script>

<template>
	<div>
		<VCard title="Products">
			<template #append>
				<VBtn
					v-if="selected.length > 0"
					color="error"
					class="me-2"
					@click="openBulkDeleteDialog">
					Delete Selected ({{ selected.length }})
				</VBtn>
				<VBtn
					color="success"
					class="me-2"
					:loading="exportLoading"
					@click="exportProducts">
					Export
				</VBtn>
				<VBtn
					color="primary"
					@click="goToCreate">
					Create Product
				</VBtn>
			</template>

			<VCardText>
				<VRow>
					<VCol
						cols="12"
						md="3">
						<VSelect
							v-model="filterStatus"
							label="Status"
							:items="statusOptions"
							item-title="title"
							item-value="value"
							clearable
							@update:model-value="applyFilters" />
					</VCol>
					<VCol
						cols="12"
						md="3">
						<VSelect
							v-model="filterCategory"
							label="Category"
							:items="categories"
							item-title="name"
							item-value="id"
							clearable
							@update:model-value="applyFilters" />
					</VCol>
				</VRow>
			</VCardText>
		</VCard>
		<div
			class="position-relative d-flex flex-column"
			style="min-height: 400px">
			<VOverlay
				:model-value="loading"
				contained
				persistent
				class="align-center justify-center">
				<VProgressCircular
					indeterminate
					size="64" />
			</VOverlay>
			<VDataTableServer
				v-model="selected"
				class="flex-grow-1 d-flex flex-column"
				:headers="headers"
				:items="products"
				:items-length="totalItems"
				:loading="false"
				:items-per-page="itemsPerPage"
				:items-per-page-options="[10, 25, 50, 100]"
				:page="currentPage"
				:sort-by="sortBy"
				show-select
				item-value="id"
				@update:options="onUpdateOptions">
				<template #item.price="{ item }">
					{{ formatPrice(item.price) }}
				</template>
				<template #item.is_enabled="{ item }">
					<VChip
						:color="item.is_enabled ? 'success' : 'error'"
						size="small">
						{{ item.is_enabled ? 'Active' : 'Inactive' }}
					</VChip>
				</template>
				<template #item.actions="{ item }">
					<VBtn
						icon
						size="small"
						variant="text"
						color="primary"
						@click="goToEdit(item)">
						<VIcon icon="tabler-edit" />
					</VBtn>
					<VBtn
						icon
						size="small"
						variant="text"
						color="error"
						@click="openDeleteDialog(item)">
						<VIcon icon="tabler-trash-off" />
					</VBtn>
				</template>
			</VDataTableServer>
		</div>

		<!-- Delete Confirmation Dialog -->
		<VDialog
			v-model="deleteDialog"
			max-width="400">
			<VCard title="Delete Product">
				<VCardText>Are you sure you want to delete "{{ deletingProduct?.name }}"?</VCardText>
				<VCardActions>
					<VSpacer />
					<VBtn
						variant="text"
						@click="deleteDialog = false">
						Cancel
					</VBtn>
					<VBtn
						color="error"
						:loading="deleteLoading"
						@click="deleteProduct">
						Delete
					</VBtn>
				</VCardActions>
			</VCard>
		</VDialog>

		<!-- Bulk Delete Confirmation Dialog -->
		<VDialog
			v-model="bulkDeleteDialog"
			max-width="400">
			<VCard title="Delete Products">
				<VCardText>
					Are you sure you want to delete {{ selected.length }} selected products?
				</VCardText>
				<VCardActions>
					<VSpacer />
					<VBtn
						variant="text"
						@click="bulkDeleteDialog = false">
						Cancel
					</VBtn>
					<VBtn
						color="error"
						:loading="bulkDeleteLoading"
						@click="bulkDeleteProducts">
						Delete
					</VBtn>
				</VCardActions>
			</VCard>
		</VDialog>
	</div>
</template>
