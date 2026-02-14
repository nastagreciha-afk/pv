<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useInvoiceApi } from '~/composables/useInvoiceApi';
import { useFormatters } from '~/composables/useFormatters';

const { getInvoices, isLoading, error } = useInvoiceApi();
const { formatCurrency, formatDate, getStatusClasses, getStatusText } = useFormatters();

// State
const invoices = ref<any[]>([]);
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
});
const currentPage = ref(1);

// Fetch invoices
const fetchInvoices = async (page: number = 1) => {
  try {
    const response = await getInvoices(page, pagination.value.per_page);
    invoices.value = response.data;
    pagination.value = response.meta;
    currentPage.value = page;
  } catch (err) {
    console.error('Failed to fetch invoices:', err);
  }
};

// Pagination methods
const goToPage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchInvoices(page);
  }
};

const nextPage = () => {
  if (currentPage.value < pagination.value.last_page) {
    goToPage(currentPage.value + 1);
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1);
  }
};

// Fetch on mount
onMounted(() => {
  fetchInvoices(1);
});
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between gap-4">
      <h1 class="text-2xl font-bold text-slate-900">Invoices</h1>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
        @click="fetchInvoices(1)"
        :disabled="isLoading"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="rounded-lg border border-slate-200 bg-white p-8 text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-sky-600"></div>
      <p class="mt-4 text-sm text-slate-500">Loading invoices...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 1a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008c.414 0 .75-.336.75-.75V10.75A.75.75 0 0110 10z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-rose-800">Error loading invoices</h3>
          <div class="mt-2 text-sm text-rose-700">
            <p>{{ error }}</p>
          </div>
          <div class="mt-4">
            <button
              @click="fetchInvoices(1)"
              class="inline-flex items-center rounded-md border border-transparent bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
            >
              Try again
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!invoices.length" class="rounded-lg border border-slate-200 bg-white p-8 text-center">
      <svg class="mx-auto h-12 w-12 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <h3 class="mt-4 text-sm font-medium text-slate-900">No invoices found</h3>
      <p class="mt-1 text-sm text-slate-500">Get started by creating a new invoice.</p>
    </div>

    <!-- Invoice list -->
    <div v-else class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Invoice Number
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Supplier
            </th>
            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
              Gross Amount
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Status
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Due Date
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr
            v-for="invoice in invoices"
            :key="invoice.id"
            class="cursor-pointer hover:bg-slate-50"
            @click="navigateTo(`/invoices/${invoice.id}`)"
          >
            <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-slate-900">
              {{ invoice.number }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700">
              {{ invoice.supplier_name }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-right tabular-nums text-slate-900">
              {{ formatCurrency(invoice.gross_amount, invoice.currency) }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-sm">
              <span :class="getStatusClasses(invoice.status)">
                {{ getStatusText(invoice.status) }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-700">
              {{ formatDate(invoice.due_date) }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="border-t border-slate-200 bg-white px-4 py-3 flex items-center justify-between sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Previous
          </button>
          <button
            @click="nextPage"
            :disabled="currentPage === pagination.last_page"
            class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-slate-700">
              Showing
              <span class="font-medium">{{ pagination.current_page * pagination.per_page - pagination.per_page + 1 }}</span>
              to
              <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
              of
              <span class="font-medium">{{ pagination.total }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
              <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                </svg>
              </button>
              <button
                v-for="page in Math.min(pagination.last_page, 5)"
                :key="page"
                @click="goToPage(page)"
                :class="[
                  currentPage === page
                    ? 'z-10 bg-sky-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600'
                    : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50',
                  'relative inline-flex items-center px-4 py-2 text-sm font-medium focus:z-20'
                ]"
              >
                {{ page }}
              </button>
              <button
                @click="nextPage"
                :disabled="currentPage === pagination.last_page"
                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Add any component-specific styles here */
</style>

