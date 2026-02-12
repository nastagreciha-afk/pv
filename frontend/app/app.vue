<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <NuxtRouteAnnouncer />
    <header class="border-b bg-white">
      <div class="mx-auto max-w-5xl px-4 py-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold tracking-tight">
          Invoice Manager
        </h1>
        <span class="text-xs text-slate-500">
          Nuxt 4 · Vue 3.5 · TailwindCSS 4
        </span>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6">
      <NuxtPage />
    </main>
  </div>
</template>
