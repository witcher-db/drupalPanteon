

// @type { import('@storybook/html-vite').StorybookConfig }

const config = {
  "stories": [
    "../components/**/*.stories.@(js|jsx|mjs|ts|tsx)",
    "../../components/**/*.stories.@(js|jsx|mjs|ts|tsx)"
  ],
  "addons": [
    "@storybook/addon-docs"
  ],
  "framework": {
    "name": "@storybook/html-vite",
    "options": {}
  },
  "viteFinal": async (config) => {
    // Add support for .twig files
    config.assetsInclude = [/\.twig$/];
    return config;
  }
};
export default config;
