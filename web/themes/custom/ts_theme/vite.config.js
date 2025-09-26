import { defineConfig } from 'vite';
import twig from 'vite-plugin-twig-drupal';
import { join } from "node:path"


export default defineConfig({
  build: {
    chunkSizeWarningLimit: 1500,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) return 'vendor';
          const match = id.match(/\/([\w_-]+)\.stories\./);
          if (match) return `${match[1]}_chunk`;
        },
      },
    },
  },
  plugins: [
    twig({
      namespaces: {
        components: join(__dirname, "../components"),
      },
    }),
  ],
});
