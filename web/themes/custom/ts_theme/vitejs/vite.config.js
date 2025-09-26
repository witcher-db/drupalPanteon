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
  resolve: {
    alias: {
      twig: 'twig',
    },
  },
  plugins: [
    twig({
      namespaces: {
        components: join(__dirname, "../components"),
      },
      importContext: true, // Enable proper context handling for imports
      twigOptions: {
        allowInlineIncludes: true,
        namespaces: {
          components: join(__dirname, "../components"),
        }
      },
      // Add explicit twig import handling
      transformInclude: (id) => id.endsWith('.twig'),
      transform: (code, id) => {
        if (id.endsWith('.twig')) {
          return {
            code: `import 'twig'; export default ${code}`,
            map: null
          };
        }
      }
    }),
  ],
});
