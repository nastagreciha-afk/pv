<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InvoiceForm from '~/components/InvoiceForm.vue'
import { useInvoices } from '~/composables/useInvoices'

const route = useRoute()
const router = useRouter()
const { getInvoice, updateInvoice, isLoading, error, saveError, successMessage } = useInvoices()

const invoice = ref<any>(null)
const invoiceId = computed(() => Number(route.params.id))

const fetchInvoice = async () => {
  try {
    const data = await getInvoice(invoiceId.value)
    invoice.value = data
  } catch (err) {
    console.error('Failed to fetch invoice:', err)
  }
}

const handleSubmit = async (formData: any) => {
  try {
    await updateInvoice(invoiceId.value, formData)
    router.push(`/invoices/${invoiceId.value}`)
  } catch (err) {
    console.error('Failed to update invoice:', err)
  }
}

const handleCancel = () => {
  router.push(`/invoices/${invoiceId.value}`)
}

onMounted(() => {
  fetchInvoice()
})
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-2">
        <button
          @click="() => router.push(`/invoices/${invoiceId}`)"
          class="inline-flex items-center rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Invoice
        </button>
      </div>
      <h1 class="mt-4 text-2xl font-bold text-gray-900">Edit Invoice</h1>
    </div>

    <!-- Save error (form stays visible) -->
    <div v-if="saveError" class="mb-4 rounded-md border border-red-200 bg-red-50 p-4">
      <p class="text-sm font-medium text-red-800">{{ saveError }}</p>
    </div>

    <!-- Success message -->
    <div v-if="successMessage" class="mb-4 rounded-md bg-green-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
        </div>
      </div>
    </div>

    <!-- Edit Form (error = only load error; saveError is shown above) -->
    <InvoiceForm
      :invoice="invoice"
      :loading="isLoading"
      :error="error"
      :on-submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>