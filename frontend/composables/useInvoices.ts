import { ref } from 'vue'
import type { Ref } from 'vue'

// Type definitions
interface Invoice {
  id: number
  number: string
  supplier_name: string
  supplier_tax_id: string
  net_amount: number
  vat_amount: number
  gross_amount: number
  currency: string
  status: 'pending' | 'approved' | 'rejected'
  issue_date: string
  due_date: string
  created_at: string
  updated_at: string
}

interface ApiResponse<T> {
  data: T
  meta?: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
  links?: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export function useInvoices() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase

  // State management
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const saveError = ref<string | null>(null)
  const successMessage = ref<string | null>(null)

  // Clear messages
  const clearMessages = () => {
    error.value = null
    saveError.value = null
    successMessage.value = null
  }

  // Get all invoices
  const getInvoices = async (page: number = 1, perPage: number = 10): Promise<PaginatedResponse<Invoice>> => {
    try {
      isLoading.value = true
      clearMessages()

      const response = await $fetch<PaginatedResponse<Invoice>>(`${apiBase}/invoices`, {
        params: { page, per_page: perPage },
      })

      return response
    } catch (err) {
      error.value = 'Failed to fetch invoices. Please try again.'
      console.error('API Error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Get single invoice
  const getInvoice = async (id: number): Promise<Invoice> => {
    try {
      isLoading.value = true
      clearMessages()

      const response = await $fetch<Invoice>(`${apiBase}/invoices/${id}`)
      return response
    } catch (err) {
      error.value = 'Failed to fetch invoice details. Please try again.'
      console.error('API Error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Update invoice
  const updateInvoice = async (id: number, invoiceData: Partial<Invoice>): Promise<Invoice> => {
    try {
      isLoading.value = true
      saveError.value = null

      const response = await $fetch<Invoice>(`${apiBase}/invoices/${id}`, {
        method: 'PUT',
        body: invoiceData,
      })

      successMessage.value = 'Invoice updated successfully!'
      return response
    } catch (err: any) {
      const message = err?.data?.message || err?.message || 'Failed to update invoice. Please try again.'
      saveError.value = typeof message === 'string' ? message : 'Failed to update invoice. Please try again.'
      console.error('API Error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    isLoading,
    error,
    saveError,
    successMessage,
    clearMessages,
    getInvoices,
    getInvoice,
    updateInvoice,
  }
}