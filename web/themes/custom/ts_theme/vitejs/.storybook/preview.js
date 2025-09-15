/** @type { import('@storybook/html-vite').Preview } */
import "../../dest/css/style.css";

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
