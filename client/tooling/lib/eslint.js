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

const CLIEngine = require('eslint').CLIEngine;
const common = require('./common');
const patterns = require('./patterns');

const ignoreMessage =
  'File ignored because of a matching ignore pattern. Use "--no-ignore" to override.';

async function run(opts) {
  const filePatterns = opts.paths
    ? common.filterByGlobs(opts.paths, patterns.eslint)
    : patterns.eslint;

  const cli = new CLIEngine({
    baseConfig: require('../../.eslintrc.js'),
    fix: opts.fix,
    cwd: require('../lib/common').rootDir,
  });
  const formatter = cli.getFormatter();

  // filter to only changed files if requested
  const finalFilePatterns = opts.onlyChanged
    ? common.filterByGlobs(common.listChangedFiles(), filePatterns)
    : filePatterns;
  const report = cli.executeOnFiles(finalFilePatterns);

  // suppress "File ignored" messages, which happen when we pass in files
  // that are ignored by an ignore pattern
  const results = report.results.filter(
    item => !(item.messages[0] && item.messages[0].message === ignoreMessage)
  );

  if (opts && opts.fix) {
    CLIEngine.outputFixes(report);
  }

  const ignoredCount = report.results.length - results.length;
  const output = formatter(results);
  const errorCount = report.errorCount;
  const warningCount = report.warningCount - ignoredCount;

  if (output != '') {
    console.log(output);
  }

  return errorCount === 0 && warningCount === 0;
}

module.exports = {
  run,
};
