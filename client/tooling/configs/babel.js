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
 * @module totara_core
 */

require('../lib/environment_patch');

module.exports = {
  legacy: {
    presets: ['@babel/preset-env'],
    plugins: [
      // require('../babel/disallow-syntax'),
      [
        '@babel/plugin-transform-runtime',
        {
          regenerator: true,
        },
      ],
      [
        '@babel/plugin-transform-regenerator',
        {
          asyncGenerators: false,
          generators: false,
          async: true,
        },
      ],
    ],
  },

  test: {
    plugins: ['@babel/plugin-transform-modules-commonjs'],
  },
};
