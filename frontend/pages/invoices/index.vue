<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import InvoiceTable from '~/components/InvoiceTable.vue'
import { useInvoices } from '~/composables/useInvoices'

const PER_PAGE = 10

const router = useRouter()
const { getInvoices, isLoading, error } = useInvoices()

const invoices = ref<any[]>([])
const pagination = ref({
  current_page: 1,
  per_page: PER_PAGE,
  total: 0,
  last_page: 1,
})

const fetchInvoices = async (page: number = 1) => {
  try {
    const response = await getInvoices(page, PER_PAGE)
    invoices.value = response.data
    pagination.value = response.meta
  } catch (err) {
    console.error('Failed to fetch invoices:', err)
  }
}

const goToPage = (page: number) => {
  if (page < 1 || page > pagination.value.last_page) return
  fetchInvoices(page)
}

const handleRowClick = (invoiceId: number) => {
  router.push(`/invoices/${invoiceId}`)
}

const pageNumbers = computed(() => {
  const last = pagination.value.last_page
  const current = pagination.value.current_page
  const delta = 2
  const range: number[] = []
  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    range.push(i)
  }
  return range
})

onMounted(() => {
  fetchInvoices(1)
})
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between gap-4">
      <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        @click="fetchInvoices(pagination.current_page)"
        :disabled="isLoading"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <!-- Invoice Table -->
    <InvoiceTable
      :invoices="invoices"
      :loading="isLoading"
      :error="error"
      @row-click="handleRowClick"
    />

    <!-- Pagination -->
    <div
      v-if="!error && pagination.last_page > 1"
      class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 pt-4"
    >
      <p class="text-sm text-gray-600">
        Showing
        <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
        –
        <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
        of
        <span class="font-medium">{{ pagination.total }}</span>
      </p>
      <nav class="flex items-center gap-1">
        <button
          type="button"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none"
          :disabled="pagination.current_page <= 1 || isLoading"
          @click="goToPage(pagination.current_page - 1)"
        >
          Previous
        </button>
        <template v-for="p in pageNumbers" :key="p">
          <button
            type="button"
            :class="[
              'min-w-[2.25rem] rounded-md px-3 py-1.5 text-sm font-medium',
              p === pagination.current_page
                ? 'bg-blue-600 text-white'
                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
            ]"
            :disabled="isLoading"
            @click="goToPage(p)"
          >
            {{ p }}
          </button>
        </template>
        <button
          type="button"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none"
          :disabled="pagination.current_page >= pagination.last_page || isLoading"
          @click="goToPage(pagination.current_page + 1)"
        >
          Next
        </button>
      </nav>
    </div>
  </div>
</template>