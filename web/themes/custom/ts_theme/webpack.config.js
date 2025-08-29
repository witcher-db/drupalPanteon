const path = require("node:path");

module.exports = {
  entry: [path.resolve(__dirname, "./src/scss/style.scss")],
  module: {
    rules: [
      {
        test: /\.scss$/,
        // exclude: /node_modules/,
        type: "asset/resource",
        generator: {
          filename: "css/style.css",
        },
        use: ["sass-loader"],
      },
    ],
  },
};
