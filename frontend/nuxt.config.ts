export default defineNuxtConfig({
    compatibilityDate: '2026-02-14',
    devtools: { enabled: true },
    srcDir: '.',
    modules: [],

    css: ['./assets/css/tailwind.css'],

    postcss: {
        plugins: {
            tailwindcss: {},
            autoprefixer: {},
        },
    },

    components: {
        global: true,
        dirs: ['~/components'],
    },

    runtimeConfig: {
        public: {
            apiBase: process.env.API_BASE_URL || 'http://localhost:8000/api',
        },
    },

    build: {
        transpile: ['vee-validate'],
    },

    imports: {
        autoImport: true,
    },

    typescript: {
        strict: true,
        typeCheck: false,
    },
})
