module.exports = {
  extends: [
    './tooling/configs/.stylelintrc_tui.js',
    'stylelint-config-prettier',
  ],
  overrides: [
    {
      files: ['*.scss', '**/*.scss'],
      customSyntax: 'postcss-scss',
    },
    {
      files: ['*.vue', '**/*.vue'],
      customSyntax: 'postcss-html',
    },
  ],
  plugins: ['stylelint-order', './tooling/stylelint/plugins'],
  rules: {
    'order/properties-order': require('./tooling/configs/stylelint_order'),
    'tui/ascii-only': true,
    'tui/at-extend-only-placeholders': true,
  },
};
