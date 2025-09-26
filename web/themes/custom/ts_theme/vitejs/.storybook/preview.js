/** @type { import('@storybook/html-vite').Preview } */
import "../../dest/css/style.css";

// Function to render Twig templates
const renderTwig = (template, context = {}) => {
  // Return the rendered HTML string
  return template(context);
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
  // Add global decorators
  decorators: [
    (Story) => {
      // If the story returns a string (like a rendered Twig template), use it directly
      const rendered = Story();
      if (typeof rendered === 'string') {
        const container = document.createElement('div');
        container.innerHTML = rendered;
        return container;
      }
      // Otherwise, return the story as is
      return Story();
    },
  ],
};

// Export the renderTwig function to make it available to stories
export { renderTwig };
export default preview;
