import { defineConfig } from 'vite';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: './assets/script.js',
                style: './assets/style.css'
            }
        }
    }
});