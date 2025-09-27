/** @type { import('@storybook/html-vite').Preview } */
import "../dest/css/style.css";
import Twig from 'twig';

// Function to render Twig templates
window.renderTwig = (template, variables) => {
  return Twig.twig({
    data: template
  }).render(variables);
};

const preview = {
  parameters: {
    controls: {
      matchers: {
       color: /(background|color)$/i,
       date: /Date$/i,
      },
    },
  },
};

export default preview;
