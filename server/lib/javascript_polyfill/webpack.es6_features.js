module.exports = {
  mode: 'production',

  entry: [
    './es/es6_features.js',
    './es/es_promise_ie11.js',
    './dom/dom_features_ie11.js',
  ],

  optimization: {
    minimize: false,
  },

  performance: {
    hints: false
  },

  output: {
    path: __dirname + '/src',
    filename: 'es6_dom_features.bundle.js',
  },

  target: ['web', 'es5'],
};
