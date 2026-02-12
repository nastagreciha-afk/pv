<script setup lang="ts">
const router = useRouter();
</script>

<template>
  <section class="space-y-4">
    <h2 class="text-lg font-semibold tracking-tight">
      Invoice dashboard
    </h2>
    <p class="text-sm text-slate-600">
      Use this simple tool to browse and edit accounting invoices.
    </p>
    <button
      type="button"
      class="inline-flex items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700"
      @click="router.push('/invoices')"
    >
      Go to invoices
    </button>
  </section>
</template>

