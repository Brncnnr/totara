/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module samples
 */

module.exports = {
  webpackTui(config, { addRule, addRuleBefore, getRule }) {
    // `addRule` adds an entry to module.rules in the webpack configuration
    // the first argument (id) is so that it can be looked up via `getRule` if
    // need be.
    // All of the core rules are registered using addRule, so you can get them
    // with `getRule` and modify if need be.

    // add rules for sample-template and sample-script Vue SFC blocks
    addRule('block-sample-template', {
      resourceQuery: /blockType=sample-template/,
      use: [require.resolve('./tooling/sample_template_loader')],
    });
    addRule('block-sample-script', {
      resourceQuery: /blockType=sample-script/,
      use: [require.resolve('./tooling/sample_script_loader')],
    });

    // add a rule for files that should not be passed through babel
    // it needs to be added before the js rule so we match before it
    // (unused, just an example for addRuleBefore)
    addRuleBefore('js', 'js-no-babel', {
      test: /-nobabel\.js$/,
      use: [],
    });

    // alter a vue-loader option
    const vueRule = getRule('vue');
    const vueLoader = vueRule.use.find(x => x.loader == 'vue-loader');
    // this is already false, just an example
    vueLoader.options.prettify = false;

    return config;
  },

  // Example of a completely custom webpack config:
  // Return null in `webpackTui()` to prevent the default tui build for the
  // folder from running.
  // Return an array here to provide multiple configs.

  // webpack({ mode, component }) {
  //   const suffix = mode == 'production' ? '' : '.' + mode;
  //   return {
  //     // name must be unique and should begin with component
  //     name: component + '_custom',
  //     mode,
  //     entry: {
  //       standalone: path.join(__dirname, 'standalone-entry.js'),
  //     },
  //     output: {
  //       path: path.join(__dirname, '../build'),
  //       filename: '[name]' + suffix + '.js',
  //     },
  //   };
  // },
};
