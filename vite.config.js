import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    base: process.env.ASSET_URL || '/',
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        // Bundle everything into single files
        rollupOptions: {
            output: {
                // All JS chunks go into single app file
                manualChunks: () => 'app',
                // Consistent naming without hashes (optional, remove if you want cache busting)
                entryFileNames: 'assets/app.js',
                chunkFileNames: 'assets/app.js',
                assetFileNames: 'assets/[name].[ext]',
            },
            // Suppress warnings about dynamic/static import conflicts - harmless with single bundle
            onwarn(warning, warn) {
                if (warning.message?.includes('dynamic import will not move module')) {
                    return; // Suppress this specific warning
                }
                warn(warning);
            },
        },
        // Disable CSS code splitting - bundle all CSS into one file
        cssCodeSplit: false,
    },
});
