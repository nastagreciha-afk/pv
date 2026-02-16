/// <reference types="node" />
// frontend/env.d.ts

declare global {
    namespace NodeJS {
        interface ProcessEnv {
            API_BASE_URL?: string
        }
    }
}
