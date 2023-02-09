module.exports = {
  root: true,
  env: {
    browser: true,
    commonjs: true,
    node: true,
    // some ES6 globals are emulated in IE11 via polyfill - see lib/javascript_polyfill/
    es6: true,
  },
  plugins: ['tui'],
  extends: [
    'eslint:recommended',
    'plugin:jest/recommended',
    'plugin:vue/recommended',
    // disable rules that would conflict with prettier
    'prettier',
    'prettier/vue',
  ],
  globals: {
    // tui global interface
    tui: true,
  },
  rules: {
    // we use console for error reporting
    'no-console': 'off',
    'vue/no-v-html': 'off',
    'vue/require-default-prop': 'off',
    'vue/html-self-closing': ['warn', { html: { void: 'any' } }],
    // generators compile to large (regenerator-runtime) and slow code with
    // babel for IE 11, so disallow them
    'tui/no-generators': 'error',
    'tui/no-export-vue-extend': 'error',
    'tui/no-tui-internal': 'error',
    'tui/no-for-of': 'error',
    'tui/no-weka-standalone-usage-identifier': 'error',
  },
  overrides: [
    {
      files: [
        'tooling/**/*',
        'component/*/src/tests/**/*',
        'component/*/src/**/__tests__/**/*',
      ],
      rules: {
        'tui/no-for-of': 'off',
      },
    },
  ],
};
