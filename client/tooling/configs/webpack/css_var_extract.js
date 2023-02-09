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
 * @module tui
 */

const path = require('path');
const fs = require('fs');
const { rootDir, clientDir } = require('../../lib/common');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

/**
 * Generate webpack config for extracting CSS var data.
 *
 * @param {object} opts
 * @param {object} context
 * @returns {object|null}
 */
function cssVarExtract({ mode, watch }, { getTuiDirs }) {
  const tuiDirs = getTuiDirs();

  const scssVarsEntry = tuiDirs.reduce((acc, dir) => {
    const varsSrc = path.join(dir, 'global_styles/_variables.scss');
    if (fs.existsSync(path.join(rootDir, varsSrc))) {
      const out = path.join(
        dir.replace(/[/\\]src$/, '/build'),
        'css_variables'
      );
      acc[out] = './' + varsSrc;
    }
    return acc;
  }, {});

  if (Object.keys(scssVarsEntry).length == 0) {
    return null;
  }

  const plugins = [
    new RemoveEmptyScriptsPlugin(),
  ];

  const cssLoaders = [
    require.resolve('../../webpack/css_var_extract_loader'),
    require.resolve('../../webpack/css_raw_loader'),
    {
      loader: 'sass-loader',
      options: {
        webpackImporter: false,
        sassOptions: {
          // force outputStyle to expanded even in production builds, otherwise
          // comments get stripped.
          outputStyle: 'expanded',
          importer: url => {
            const result = /^(\w+)\/(.*)/.exec(url);
            if (result) {
              const filePath = path.join(
                clientDir,
                'component',
                result[1],
                'src/global_styles',
                result[2]
              );
              return { file: filePath };
            }
            return null;
          },
        },
      },
    },
  ];

  const rules = [
    {
      test: /\.scss$/,
      use: [...cssLoaders],
    },
  ];

  return {
    name: 'css-var-extract',
    entry: scssVarsEntry,
    mode,
    watch,
    stats: 'minimal',
    devtool: false,
    output: { path: rootDir },
    module: { rules },
    plugins,
  };
}

module.exports = cssVarExtract;
