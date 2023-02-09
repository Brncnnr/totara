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
const util = require('util');
const webpack = require('webpack');
const chalk = require('chalk').stderr;
const createConfig = require('../configs/webpack.config');
const { rootDir } = require('../lib/common');
const ProgressReportPlugin = require('../webpack/progress/ProgressReportPlugin');
const ProgressReportPluginState = require('../webpack/progress/ProgressReportPluginState');

const args = require('yargs')
  .usage('Usage: $0 [options] [components]')
  .help()
  .version(false)
  .boolean('watch')
  .describe('watch', 'Watch files for changes')
  .describe('mode', 'Enable production mode when passed "production"')
  .describe('vendor', 'Only build code by the provided vendor')
  .boolean('legacy')
  .default('legacy', true)
  .hide('legacy')
  .describe('no-legacy', 'Skip building code for legacy browsers')
  .default('mode', 'development').argv;

if (args.watch && Array.isArray(args.mode)) {
  console.error(
    chalk.redBright('Error: watch cannot be used with multiple modes')
  );
  process.exit(1);
}

const tuiComponents = args._.length == 0 ? null : args._;

const runWebpack = util.promisify(function runWebpack(args, cb) {
  const configs = createConfig({
    mode: args.mode,
    analyze: args.analyze,
    legacy: args.legacy,
    vendor: args.vendor,
    tuiComponents,
  });

  if (configs.length == 0) {
    console.error(chalk.red('no matching builds'));
    cb(null);
    return;
  }

  const pluginState = new ProgressReportPluginState({ key: args.mode });
  configs.forEach(config => {
    if (!config.plugins) config.plugins = [];
    // inject progress reporter for each build
    config.plugins.push(new ProgressReportPlugin({ pluginState }));

    if (config.target == null) {
      config.target =
        'browserslist:' + path.resolve(rootDir, 'client/.browserslistrc');
    }
  });

  let compiler = webpack(configs);

  const statsOptions = {
    all: false,
    errors: true,
    warnings: true,
    logging: 'warn',
    colors: chalk.supportsColor,
  };

  let prevHash;
  function webpackCallback(err, stats) {
    if (!args.watch || err) {
      // clear cache
      compiler.purgeInputFileSystem();
    }
    if (err) {
      prevHash = null;
      console.error(err.stack || err);
      if (err.details) {
        console.error(err.details);
      }
      process.exitCode = 1;
      cb(err);
      return;
    }
    if (stats.hash !== prevHash) {
      prevHash = stats.hash;
      const statsString = stats.toString(statsOptions);
      if (statsString) {
        process.stdout.write(`${statsString}\n`);
      }
    }
    if (!args.watch && stats.hasErrors()) {
      process.exitCode = 2;
    }
    cb(null);
  }

  if (args.watch) {
    console.error('watching for changes in client directory...');
    compiler.watch({}, webpackCallback);
  } else {
    compiler.run((err, stats) => {
      if (compiler.close) {
        compiler.close(closeErr => {
          webpackCallback(err || closeErr, stats);
        });
      } else {
        webpackCallback(err, stats);
      }
    });
  }
});

console.error(chalk.bold('tui build'));

async function runBuilds() {
  const modes = [].concat(args.mode);
  for (const mode of modes) {
    if (modes.length > 1) {
      console.error('\n' + chalk.bold(mode) + ' build:');
    }
    await runWebpack({
      ...args,
      mode,
    });
  }
  console.error('');
}

runBuilds();
