<script setup lang="ts">
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import { useInvoiceApi } from '~/composables/useInvoiceApi';
import { useFormatters } from '~/composables/useFormatters';

const route = useRoute();
const { getInvoice, updateInvoice, isLoading, error, successMessage, clearMessages } = useInvoiceApi();
const { formatCurrency, formatDate, formatDateTime, getStatusClasses, getStatusText } = useFormatters();

// State
const invoice = ref<any>(null);
const toast = useState<'message' | null>('flash', () => null);

// Fetch invoice data
const fetchInvoice = async () => {
  try {
    clearMessages();
    invoice.value = await getInvoice(Number(route.params.id));
  } catch (err) {
    console.error('Failed to fetch invoice:', err);
  }
};

// Fetch on mount
onMounted(() => {
  fetchInvoice();
});

const isPending = computed(() => invoice.value?.status === 'pending');

// Form validation schema
const schema = toTypedSchema(
  z
    .object({
      net_amount: z.coerce.number().gt(0, 'Net amount must be greater than 0'),
      vat_amount: z.coerce.number().min(0, 'VAT amount must be >= 0'),
      due_date: z.string().min(1, 'Due date is required'),
    })
    .refine(
      (val) => {
        if (!invoice.value) return true;
        const issue = new Date(invoice.value.issue_date);
        const due = new Date(val.due_date);
        return due >= issue;
      },
      {
        message: 'Due date must be on or after issue date',
        path: ['due_date'],
      },
    ),
);

const { handleSubmit, errors, values, setValues, meta, isSubmitting } = useForm({
  validationSchema: schema,
  initialValues: computed(() => ({
    net_amount: invoice.value?.net_amount ?? '',
    vat_amount: invoice.value?.vat_amount ?? '',
    due_date: invoice.value?.due_date ?? '',
  })),
});

// Watch for invoice changes and update form
watch(
  invoice,
  (inv) => {
    if (inv) {
      setValues({
        net_amount: inv.net_amount,
        vat_amount: inv.vat_amount,
        due_date: inv.due_date,
      });
    }
  },
  { immediate: true },
);

// Auto-calculate gross amount
const grossComputed = computed(() => {
  const net = Number(values.net_amount || 0);
  const vat = Number(values.vat_amount || 0);
  return (net + vat).toFixed(2);
});

// Form submission
const onSubmit = handleSubmit(async (formValues) => {
  if (!invoice.value) return;

  try {
    clearMessages();
    const updated = await updateInvoice(invoice.value.id, {
      ...invoice.value,
      net_amount: formValues.net_amount,
      vat_amount: formValues.vat_amount,
      gross_amount: Number(grossComputed.value),
      due_date: formValues.due_date,
    });

    invoice.value = updated;
    toast.value = successMessage.value || 'Invoice updated successfully';
  } catch (e: any) {
    toast.value = error.value || e?.data?.message || 'Failed to update invoice';
  }
});
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Back button -->
    <button
      type="button"
      class="mb-6 inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900"
      @click="$router.back()"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
      </svg>
      Back to invoices
    </button>

    <!-- Loading state -->
    <div v-if="isLoading && !invoice" class="rounded-lg border border-slate-200 bg-white p-8 text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-sky-600"></div>
      <p class="mt-4 text-sm text-slate-500">Loading invoice details...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-rose-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 1a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 01.75.75v.008c0 .414.336.75.75.75h.008c.414 0 .75-.336.75-.75V10.75A.75.75 0 0110 10z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-rose-800">Error loading invoice</h3>
          <div class="mt-2 text-sm text-rose-700">
            <p>{{ error }}</p>
          </div>
          <div class="mt-4">
            <button
              @click="fetchInvoice()"
              class="inline-flex items-center rounded-md border border-transparent bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-200 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
            >
              Try again
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Invoice not found -->
    <div v-else-if="!invoice" class="rounded-lg border border-slate-200 bg-white p-8 text-center">
      <svg class="mx-auto h-12 w-12 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
      <h3 class="mt-4 text-sm font-medium text-slate-900">Invoice not found</h3>
      <p class="mt-1 text-sm text-slate-500">The requested invoice does not exist.</p>
    </div>

    <!-- Invoice details -->
    <div v-else class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Invoice {{ invoice.number }}</h1>
          <p class="mt-1 text-sm text-slate-600">
            {{ invoice.supplier_name }} · {{ invoice.supplier_tax_id }}
          </p>
        </div>
        <span :class="getStatusClasses(invoice.status)">
          {{ getStatusText(invoice.status) }}
        </span>
      </div>

      <!-- Invoice info grid -->
      <div class="grid gap-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Issue Date</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ formatDate(invoice.issue_date) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Due Date</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ formatDate(invoice.due_date) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Updated</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ formatDateTime(invoice.updated_at) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Currency</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ invoice.currency }}</dd>
        </div>
      </div>

      <!-- Amounts grid -->
      <div class="grid gap-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-3">
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Net Amount</dt>
          <dd class="mt-1 text-lg font-medium text-slate-900">
            {{ formatCurrency(invoice.net_amount, invoice.currency) }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">VAT Amount</dt>
          <dd class="mt-1 text-lg font-medium text-slate-900">
            {{ formatCurrency(invoice.vat_amount, invoice.currency) }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gross Amount</dt>
          <dd class="mt-1 text-lg font-medium text-slate-900">
            {{ formatCurrency(invoice.gross_amount, invoice.currency) }}
          </dd>
        </div>
      </div>

      <!-- Edit form -->
      <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
          <h2 class="text-lg font-semibold text-slate-900">Edit Invoice</h2>
          <p v-if="!isPending" class="text-sm text-amber-700">
            Editing is disabled because status is not <strong>pending</strong>.
          </p>
        </div>

        <form class="grid gap-6 md:grid-cols-3" novalidate @submit.prevent="onSubmit">
          <!-- Net Amount -->
          <div class="space-y-2">
            <label for="net_amount" class="block text-sm font-medium text-slate-700">
              Net Amount
            </label>
            <input
              id="net_amount"
              v-model="values.net_amount"
              type="number"
              step="0.01"
              min="0.01"
              :disabled="!isPending || isSubmitting"
              class="block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-500 sm:text-sm"
              :class="{
                'border-rose-300 focus:border-rose-500 focus:ring-rose-500': errors.net_amount
              }"
            />
            <p v-if="errors.net_amount" class="text-sm text-rose-600">
              {{ errors.net_amount }}
            </p>
          </div>

          <!-- VAT Amount -->
          <div class="space-y-2">
            <label for="vat_amount" class="block text-sm font-medium text-slate-700">
              VAT Amount
            </label>
            <input
              id="vat_amount"
              v-model="values.vat_amount"
              type="number"
              step="0.01"
              min="0"
              :disabled="!isPending || isSubmitting"
              class="block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-500 sm:text-sm"
              :class="{
                'border-rose-300 focus:border-rose-500 focus:ring-rose-500': errors.vat_amount
              }"
            />
            <p v-if="errors.vat_amount" class="text-sm text-rose-600">
              {{ errors.vat_amount }}
            </p>
          </div>

          <!-- Due Date -->
          <div class="space-y-2">
            <label for="due_date" class="block text-sm font-medium text-slate-700">
              Due Date
            </label>
            <input
              id="due_date"
              v-model="values.due_date"
              type="date"
              :disabled="!isPending || isSubmitting"
              class="block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-500 sm:text-sm"
              :class="{
                'border-rose-300 focus:border-rose-500 focus:ring-rose-500': errors.due_date
              }"
            />
            <p v-if="errors.due_date" class="text-sm text-rose-600">
              {{ errors.due_date }}
            </p>
          </div>

          <!-- Gross Amount (read-only) -->
          <div class="space-y-2 md:col-span-3">
            <label class="block text-sm font-medium text-slate-700">
              Gross Amount (auto-calculated)
            </label>
            <div class="flex items-center justify-between rounded-md border border-dashed border-slate-300 bg-slate-50 p-3">
              <span class="font-medium tabular-nums text-slate-900">
                {{ grossComputed }}
              </span>
              <span class="text-xs text-slate-500">
                = net_amount + vat_amount
              </span>
            </div>
          </div>

          <!-- Submit button -->
          <div class="md:col-span-3 flex items-center justify-end gap-4 pt-4">
            <p class="text-sm text-slate-500">
              All changes are validated on the server and must respect original business rules.
            </p>
            <button
              type="submit"
              :disabled="!isPending || isSubmitting || !meta.valid"
              class="inline-flex items-center rounded-md border border-transparent bg-sky-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"
            >
              <span v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
              {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast notification -->
    <transition name="fade">
      <div
        v-if="toast"
        class="fixed bottom-4 right-4 rounded-md bg-slate-900 px-4 py-2 text-sm text-slate-50 shadow-lg"
      >
        {{ toast }}
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

