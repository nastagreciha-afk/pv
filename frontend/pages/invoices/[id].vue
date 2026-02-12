<script setup lang="ts">
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';

const route = useRoute();
const config = useRuntimeConfig();

type Invoice = {
  id: number;
  number: string;
  supplier_name: string;
  supplier_tax_id: string;
  net_amount: string | number;
  vat_amount: string | number;
  gross_amount: string | number;
  currency: string;
  status: 'pending' | 'approved' | 'rejected';
  issue_date: string;
  due_date: string;
  created_at: string;
  updated_at: string;
};

const {
  data,
  pending,
  error,
  refresh,
} = await useFetch<Invoice>(() => `${config.public.apiBase}/invoices/${route.params.id}`);

const invoice = computed(() => data.value);

const formatMoney = (value: string | number, currency: string) =>
  new Intl.NumberFormat('uk-UA', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(Number(value));

const formatDate = (value: string) =>
  new Intl.DateTimeFormat('uk-UA', { dateStyle: 'medium', timeStyle: undefined }).format(
    new Date(value),
  );

const formatDateTime = (value: string) =>
  new Intl.DateTimeFormat('uk-UA', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));

const isPending = computed(() => invoice.value?.status === 'pending');

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

const grossComputed = computed(() => {
  const net = Number(values.net_amount || 0);
  const vat = Number(values.vat_amount || 0);
  return (net + vat).toFixed(2);
});

const toast = useState<'message' | null>('flash', () => null);

const onSubmit = handleSubmit(async (formValues) => {
  if (!invoice.value) return;

  try {
    const updated = await $fetch<Invoice>(`${config.public.apiBase}/invoices/${invoice.value.id}`, {
      method: 'PUT',
      body: {
        ...invoice.value,
        net_amount: formValues.net_amount,
        vat_amount: formValues.vat_amount,
        gross_amount: Number(grossComputed.value),
        due_date: formValues.due_date,
      },
    });

    data.value = updated;
    toast.value = 'Invoice updated successfully';
  } catch (e: any) {
    toast.value = e?.data?.message || 'Failed to update invoice';
  }
});

const statusColor = (status: Invoice['status']) => {
  switch (status) {
    case 'approved':
      return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    case 'rejected':
      return 'bg-rose-100 text-rose-700 ring-rose-200';
    default:
      return 'bg-amber-100 text-amber-700 ring-amber-200';
  }
};
</script>

<template>
  <section>
    <button
      type="button"
      class="mb-4 text-sm text-slate-600 hover:text-slate-900"
      @click="$router.back()"
    >
      ← Back to invoices
    </button>

    <div v-if="pending" class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
      Loading invoice…
    </div>

    <div
      v-else-if="error || !invoice"
      class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      Failed to load invoice.
    </div>

    <div v-else class="space-y-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold tracking-tight">
            Invoice {{ invoice.number }}
          </h2>
          <p class="mt-1 text-sm text-slate-600">
            {{ invoice.supplier_name }} · {{ invoice.supplier_tax_id }}
          </p>
        </div>
        <span
          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1"
          :class="statusColor(invoice.status)"
        >
          {{ invoice.status }}
        </span>
      </div>

      <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 text-sm md:grid-cols-3">
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Issue date
          </div>
          <div class="mt-1 text-slate-900">
            {{ formatDate(invoice.issue_date) }}
          </div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Due date
          </div>
          <div class="mt-1 text-slate-900">
            {{ formatDate(invoice.due_date) }}
          </div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Last updated
          </div>
          <div class="mt-1 text-slate-900">
            {{ formatDateTime(invoice.updated_at) }}
          </div>
        </div>
      </div>

      <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 text-sm md:grid-cols-3">
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Net amount
          </div>
          <div class="mt-1 text-slate-900">
            {{ formatMoney(invoice.net_amount, invoice.currency) }}
          </div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            VAT amount
          </div>
          <div class="mt-1 text-slate-900">
            {{ formatMoney(invoice.vat_amount, invoice.currency) }}
          </div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Gross amount
          </div>
          <div class="mt-1 text-slate-900">
            {{ formatMoney(invoice.gross_amount, invoice.currency) }}
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="mb-3 flex items-center justify-between gap-4">
          <h3 class="text-sm font-semibold tracking-tight">
            Edit invoice
          </h3>
          <p v-if="!isPending" class="text-xs text-amber-700">
            Editing is disabled because status is not <strong>pending</strong>.
          </p>
        </div>

        <form
          class="grid gap-4 md:grid-cols-3"
          novalidate
          @submit.prevent="onSubmit"
        >
          <div class="space-y-1">
            <label class="block text-xs font-medium text-slate-700" for="net_amount">
              Net amount
            </label>
            <input
              id="net_amount"
              v-model="values.net_amount"
              type="number"
              step="0.01"
              :disabled="!isPending || isSubmitting"
              class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-500"
            />
            <p v-if="errors.net_amount" class="text-xs text-rose-600">
              {{ errors.net_amount }}
            </p>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-medium text-slate-700" for="vat_amount">
              VAT amount
            </label>
            <input
              id="vat_amount"
              v-model="values.vat_amount"
              type="number"
              step="0.01"
              :disabled="!isPending || isSubmitting"
              class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-500"
            />
            <p v-if="errors.vat_amount" class="text-xs text-rose-600">
              {{ errors.vat_amount }}
            </p>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-medium text-slate-700" for="due_date">
              Due date
            </label>
            <input
              id="due_date"
              v-model="values.due_date"
              type="date"
              :disabled="!isPending || isSubmitting"
              class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-500"
            />
            <p v-if="errors.due_date" class="text-xs text-rose-600">
              {{ errors.due_date }}
            </p>
          </div>

          <div class="space-y-1 md:col-span-3">
            <label class="block text-xs font-medium text-slate-700">
              Gross amount (auto)
            </label>
            <div class="flex items-center justify-between rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800">
              <span class="font-medium tabular-nums">
                {{ grossComputed }}
              </span>
              <span class="text-xs text-slate-500">
                = net_amount + vat_amount
              </span>
            </div>
          </div>

          <div class="md:col-span-3 flex items-center justify-between gap-4 pt-2">
            <p class="text-xs text-slate-500">
              All changes are validated on the server and must respect original business rules.
            </p>
            <button
              type="submit"
              :disabled="!isPending || isSubmitting || !meta.valid"
              class="inline-flex items-center rounded-md bg-sky-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-300"
            >
              Save changes
            </button>
          </div>
        </form>
      </div>

      <transition name="fade">
        <div
          v-if="toast"
          class="fixed bottom-4 right-4 rounded-md bg-slate-900 px-4 py-2 text-sm text-slate-50 shadow-lg"
        >
          {{ toast }}
        </div>
      </transition>
    </div>
  </section>
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

