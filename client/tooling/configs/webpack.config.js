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

require('../lib/environment_patch');
const path = require('path');
const fs = require('fs');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { VueLoaderPlugin } = require('vue-loader');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const scanTuiJson = require('../webpack/scan_tui_json');
const { rootDir, arrayUnique } = require('../lib/common');
const tuiExternals = require('../webpack/tui_externals');
const TuiAliasPlugin = require('../webpack/TuiAliasPlugin');
const babelConfigs = require('./babel');
const fg = require('fast-glob');
const cssVarExtract = require('./webpack/css_var_extract');

/**
 * Create a webpack config with the specified options.
 *
 * @param {object} opts
 * @param {string} opts.name
 *     Name used to identify this config, e.g. 'modern' or 'legacy'.
 * @param {string} opts.mode 'development'/'production'
 * @param {boolean} opts.watch Watch for changes and rebuild?
 * @param {object} opts.define Definitions to pass to DefinePlugin.
 * @param {string} opts.analyze Load analyze plugin if name matches this string.
 * @param {string} opts.primary
 *     Is this the primary config? Some functionality will be disabled in
 *     non-primary configs to avoid performing the same work twice.
 * @param {boolean} opts.bundleTag
 *     If present, appended to the name of output files with a dot.
 */
function createConfig({
  targetVariant,
  mode,
  watch,
  define,
  analyze,
  primary,
  bundleTag,
  tuiComponent,
  tuiJson,
}) {
  const name = tuiComponent + '_' + targetVariant;

  // update output filenames based on config options
  const tagSuffix = bundleTag ? '.' + bundleTag : '';
  const modeSuffix = mode == 'production' ? '' : '.' + mode;
  const suffix = tagSuffix + modeSuffix;
  const entry = {
    tui_bundle: tuiJson,
  };

  const plugins = [
    new VueLoaderPlugin(),
    primary &&
      new MiniCssExtractPlugin({
        filename: '[name]' + suffix + '.scss',
        experimentalUseImportModule: true,
      }),
    new webpack.DefinePlugin({
      'process.env.LEGACY_BUNDLE': 'false',
      ...define,
    }),
    // provide source mapping for JS files, equivalent to `devtool: 'eval'`
    mode != 'production' &&
      new webpack.EvalDevToolModulePlugin({
        sourceUrlComment: '\n//# sourceURL=[url]',
        moduleFilenameTemplate:
          'webpack://[namespace]/[resource-path]?[loaders]',
      }),
    // provide source mapping for (S)CSS files
    mode != 'production' &&
      new webpack.SourceMapDevToolPlugin({
        test: /\.(s?css)($|\?)/i,
      }),
    mode == 'production' && new webpack.ids.HashedModuleIdsPlugin(),
    analyze == name &&
      new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin)(),
  ].filter(Boolean);

  const rules = [];
  const ruleIds = [];

  const addRule = (id, rule) => {
    // normalize use
    rule.use = rule.use.map(x => {
      const useEntry = typeof x == 'string' ? { loader: x } : x;
      if (!useEntry.options) useEntry.options = {};
      return useEntry;
    });

    rules.push(rule);
    ruleIds.push(id);
  };

  const addRuleBefore = (before, id, rule) => {
    const index = ruleIds.indexOf(before);
    if (index == -1) {
      return addRule(id, rule);
    }
    rules.splice(index, 0, rule);
    ruleIds.splice(index, 0, id);
  };

  const removeRule = id => {
    const index = ruleIds.indexOf(id);
    if (index == -1) {
      return;
    }
    rules.splice(index, 1);
    ruleIds.splice(index, 1);
  };

  const getRule = id => {
    return rules[ruleIds.indexOf(id)];
  };

  const getRuleIds = () => [...ruleIds];

  addRule('tui.json', {
    test: /[/\\]tui\.json$/,
    type: 'javascript/auto',
    use: [
      {
        loader: require.resolve('../webpack/tui_json_loader'),
        options: { silent: false },
      },
    ],
  });

  addRule('js', {
    test: /\.js$/,
    exclude: {
      and: [/node_modules/],
      not: [
        // these libraries use modern JS features and must go through babel:
        /prosemirror/,
      ]
    },
    use: [
      babelConfigs[targetVariant] && {
        loader: 'babel-loader',
        options: babelConfigs[targetVariant],
      },
    ].filter(Boolean),
  });

  addRule('js-all-pre', {
    test: /\.js$/,
    enforce: 'pre',
    use: [
      {
        loader: 'source-map-loader',
        options: {
          filterSourceMappingUrl: (url, resourcePath) => {
            if (/[/\\]node_modules[/\\]|[/\\]vendor[/\\]/.test(resourcePath)) {
              return 'remove';
            }
            return 'consume';
          },
        },
      },
    ],
  });

  addRule('vue', {
    test: /\.vue$/,
    use: [
      require.resolve('../webpack/tui_vue_loader'),
      {
        loader: 'vue-loader',
        options: {
          productionMode: mode === 'production',
          // having prettify enabled increases build time around 40%
          prettify: false,
        },
      },
    ],
  });

  addRule('scss', {
    test: /\.s?css$/,
    use: primary
      ? [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              // otherwise vue-loader won't be able to import
              esModule: false,
            },
          },
          // css-loader cannot parse SCSS
          {
            loader: require.resolve('../webpack/css_raw_loader'),
            options: { sourceMap: true },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                parser: 'postcss-scss',
                plugins: [require('autoprefixer')],
              },
              sourceMap: true,
            },
          },
        ]
      : ['null-loader'],
  });

  addRule('graphql', {
    test: /\.(graphql|gql)$/,
    exclude: /node_modules/,
    use: [require.resolve('../webpack/graphql_loader')],
  });

  addRule('svg-icon-obj', {
    test: /icons[/\\]internal[/\\]obj[/\\].*\.svg/,
    type: 'javascript/auto',
    use: [require.resolve('../webpack/icons_svg_loader')],
  });

  addRule('block-lang-strings', {
    resourceQuery: /blockType=lang-strings/,
    use: [require.resolve('../webpack/tui_lang_strings_loader')],
  });

  const config = {
    name,
    entry,
    mode,
    watch,

    // regular output is excessive
    stats: 'minimal',

    // disable automatic sourcemap support as we're adding the plugins manually above
    devtool: false,

    // stop webpack warning for file size
    performance: { hints: false },

    output: {
      path: path.resolve(
        rootDir,
        'client/component/' + tuiComponent + '/build'
      ),
      filename: '[name]' + suffix + '.js',
      chunkFilename: '[name]' + suffix + '.js',
    },

    target:
      'browserslist:' +
      path.resolve(rootDir, 'client/.browserslistrc') +
      ':' +
      targetVariant,

    resolve: {
      extensions: ['.mjs', '.js', '.json', '.vue', '.graphql'],
      plugins: [new TuiAliasPlugin()],
    },

    // used to implement importing frankenstyle paths
    externals: [tuiExternals()],

    module: {
      rules,
    },

    plugins,
  };

  const overrideContext = {
    webpack,
    mode,
    targetVariant: targetVariant,
    targetVariantIsPrimary: primary,
    configs: {
      babel: babelConfigs[targetVariant],
    },
    component: tuiComponent,
    suffix,
    addRule,
    addRuleBefore,
    removeRule,
    getRule,
    getRuleIds,
  };

  return overrideConfig('webpackTui', tuiJson, overrideContext, config);
}

function overrideConfig(key, tuiJson, overrideContext, config) {
  const configFile = path.join(
    rootDir,
    path.dirname(tuiJson),
    'build.config.js'
  );
  if (!fs.existsSync(configFile)) {
    return config;
  }
  const buildConfig = require(configFile);
  if (buildConfig && buildConfig[key]) {
    config = buildConfig[key](config, overrideContext);
    if (config && !config.name) {
      throw new Error(
        `Custom config from ${overrideContext.compoennt} does not have a name`
      );
    }
  }
  return config;
}

/**
 * Modern webpack config
 *
 * @param {object} opts
 * @return {object}
 */
function modernConfig(opts) {
  return createConfig({ ...opts, targetVariant: 'modern', primary: true });
}

/**
 * Legacy webpack config
 *
 * @param {object} opts
 * @return {object}
 */
function legacyConfig(opts) {
  return createConfig({
    ...opts,
    targetVariant: 'legacy',
    bundleTag: 'legacy',
    define: { ...opts.define, 'process.env.LEGACY_BUNDLE': 'true' },
  });
}

/**
 * Transform SCSS to improved SCSS (by running autoprefixer)
 *
 * @param {object} opts
 * @return {object}
 */
function scssToScssConfig({ mode, watch }, { getTuiDirs }) {
  const tuiDirs = getTuiDirs();

  const scssEntry = tuiDirs.reduce((acc, dir) => {
    if (fs.existsSync(path.join(rootDir, dir, 'global_styles'))) {
      fg.sync('global_styles/**/*.scss', { cwd: dir }).forEach(x => {
        if (x == 'global_styles/static.scss') {
          // already included in bundle (see tui_json_loader)
          return;
        }
        const out = path.join(
          dir.replace(
            /^\.[/\\]client[/\\]component[/\\]([^/\\]+)[/\\]src/,
            './client/component/$1/build'
          ),
          'global_styles',
          x.slice('global_styles/'.length).replace(/\.scss$/, '')
        );
        const modeSuffix = mode == 'production' ? '' : '.' + mode;
        acc[out + modeSuffix] = './' + path.join(dir, x);
      });
    }
    return acc;
  }, {});

  if (Object.keys(scssEntry).length == 0) {
    return;
  }

  const plugins = [
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].scss',
      experimentalUseImportModule: true,
    }),
    mode != 'production' &&
      new webpack.SourceMapDevToolPlugin({
        test: /\.(s?css)($|\?)/i,
      }),
  ].filter(Boolean);

  const rules = [
    {
      test: /\.scss$/,
      use: [
        MiniCssExtractPlugin.loader,
        // css-loader cannot parse SCSS
        {
          loader: require.resolve('../webpack/css_raw_loader'),
          options: { sourceMap: true },
        },
        {
          loader: 'postcss-loader',
          options: {
            postcssOptions: {
              parser: 'postcss-scss',
              plugins: [require('autoprefixer')],
            },
            sourceMap: true,
          },
        },
      ],
    },
  ];

  return {
    name: 'scss-to-scss',
    entry: scssEntry,
    mode,
    watch,
    stats: 'minimal',
    devtool: false,
    output: { path: rootDir },
    module: { rules },
    plugins,
  };
}

/**
 * Generate webpack config.
 *
 * @param {object} opts
 * @param {?bool} opts.legacy false if legacy disabled, undefined or true if legacy enabled
 * @returns {object[]}
 */
module.exports = function(opts) {
  if (!opts.mode) {
    opts = { ...opts, mode: 'production' };
  }

  let configOpts = {
    mode: opts.mode,
    analyze: opts.analyze,
  };

  const tuiJsonFiles = scanTuiJson({
    rootDir,
    components: opts.tuiComponents,
    vendor: opts.vendor,
  });

  const getTuiDirs = () => {
    return arrayUnique(Object.values(tuiJsonFiles)).map(x => path.dirname(x));
  };

  const configs = [
    scssToScssConfig(configOpts, { getTuiDirs }),
    cssVarExtract(configOpts, { getTuiDirs }),
  ];

  Object.entries(tuiJsonFiles).forEach(([tuiComponent, path]) => {
    configs.push(modernConfig({ ...configOpts, tuiComponent, tuiJson: path }));
    if (opts.legacy !== false) {
      configs.push(
        legacyConfig({ ...configOpts, tuiComponent, tuiJson: path })
      );
    }
  });

  const customConfigOpts = {
    webpack,
    mode: opts.mode,
    legacyEnabled: opts.legacy !== false,
  };

  // add custom webpack builds
  Object.entries(tuiJsonFiles).forEach(([tuiComponent, tuiJson]) => {
    const tuiDir = path.dirname(tuiJson);
    const configPath = path.join(tuiDir, 'build.config.js');
    const fullConfigPath = path.join(rootDir, configPath);
    if (!fs.existsSync(fullConfigPath)) {
      return;
    }
    const buildConfig = require(fullConfigPath);
    if (buildConfig.webpack) {
      const result = buildConfig.webpack({
        ...customConfigOpts,
        component: tuiComponent,
      });
      if (result) {
        if (Array.isArray(result)) {
          result.forEach(config => {
            if (!config.name) {
              throw new Error(
                `Custom config from ${configPath} does not have a name`
              );
            }
            configs.push(config);
          });
        } else {
          if (!result.name) {
            throw new Error(
              `Custom config from ${configPath} does not have a name`
            );
          }
          configs.push(result);
        }
      }
    }
  });

  return configs.filter(Boolean);
};
