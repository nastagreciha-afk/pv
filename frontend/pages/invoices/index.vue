<script setup lang="ts">
const config = useRuntimeConfig();

type Invoice = {
  id: number;
  number: string;
  supplier_name: string;
  gross_amount: string | number;
  status: 'pending' | 'approved' | 'rejected';
  due_date: string;
};

const {
  data,
  pending,
  error,
  refresh,
} = await useFetch(() => `${config.public.apiBase}/invoices`);

const invoices = computed<Invoice[]>(() => (data.value as any)?.data ?? []);

const formatMoney = (value: string | number) =>
  new Intl.NumberFormat('uk-UA', {
    style: 'currency',
    currency: 'UAH',
    minimumFractionDigits: 2,
  }).format(Number(value));

const formatDate = (value: string) =>
  new Intl.DateTimeFormat('uk-UA', { dateStyle: 'medium' }).format(
    new Date(value),
  );

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
    <div class="mb-4 flex items-center justify-between gap-4">
      <h2 class="text-lg font-semibold tracking-tight">
        Invoices
      </h2>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
        @click="refresh"
      >
        Refresh
      </button>
    </div>

    <div v-if="pending" class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
      Loading invoices…
    </div>

    <div
      v-else-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      Failed to load invoices. Please try again.
    </div>

    <div
      v-else
      class="overflow-hidden rounded-lg border border-slate-200 bg-white"
    >
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Number
            </th>
            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Supplier
            </th>
            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
              Gross amount
            </th>
            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Status
            </th>
            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              Due date
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr
            v-for="invoice in invoices"
            :key="invoice.id"
            class="cursor-pointer hover:bg-slate-50"
            @click="$router.push(`/invoices/${invoice.id}`)"
          >
            <td class="whitespace-nowrap px-4 py-2 font-medium text-slate-900">
              {{ invoice.number }}
            </td>
            <td class="px-4 py-2 text-slate-700">
              {{ invoice.supplier_name }}
            </td>
            <td class="whitespace-nowrap px-4 py-2 text-right font-medium tabular-nums text-slate-900">
              {{ formatMoney(invoice.gross_amount) }}
            </td>
            <td class="px-4 py-2">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1"
                :class="statusColor(invoice.status)"
              >
                {{ invoice.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-2 text-slate-700">
              {{ formatDate(invoice.due_date) }}
            </td>
          </tr>

          <tr v-if="!invoices.length">
            <td
              colspan="5"
              class="px-4 py-6 text-center text-sm text-slate-500"
            >
              No invoices yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

