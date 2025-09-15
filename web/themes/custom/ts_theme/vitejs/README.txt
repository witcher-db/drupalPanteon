# Storybook Setup

This project uses **Storybook** for UI component development and documentation.

## How to Run Storybook

### Recommended way (inside DDEV container)
Run the following command in ~/web/themes/custom/ts_theme/vitejs:

  ddev npm run storybook

### Or locally
Run the following command in ~/web/themes/custom/ts_theme/vitejs:

  npm run storybook-local

###Known Issues

Storybook cannot import certain file types other than .js (including .json, .jsx, or others) and .css,.
