
import { join } from 'path';

// @type { import('@storybook/html-vite').StorybookConfig }

const config = {
  "stories": [
    "../../components/**/*.stories.@(jsx|mjs|ts|tsx)"
  ],
  "addons": [
    "@storybook/addon-docs"
  ],
  "framework": {
    "name": "@storybook/html-vite",
    "options": {}
  },
  "viteFinal": async (config) => {
    // Add Twig plugin configuration
    config.plugins = config.plugins || [];
    
    // Import the Twig plugin dynamically to avoid issues with Storybook's bundling
    const twig = (await import('vite-plugin-twig-drupal')).default;
    
    // Add resolve configuration for twig imports
    config.resolve = config.resolve || {};
    config.resolve.alias = config.resolve.alias || {};
    config.resolve.alias.twig = 'twig';
    
    config.plugins.push(
      twig({
        namespaces: {
          components: join(__dirname, "../../components"),
        },
        importContext: true, // Enable proper context handling for imports
        twigOptions: {
          allowInlineIncludes: true,
          namespaces: {
            components: join(__dirname, "../../components"),
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
      })
    );
    
    return config;
  }
};
export default config;
