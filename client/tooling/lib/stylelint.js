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

const stylelint = require('stylelint');
const common = require('./common');
const patterns = require('./patterns');

async function run(opts) {
  const filePatterns = opts.paths
    ? common.filterByGlobs(opts.paths, patterns.stylelint)
    : patterns.stylelint;

  // filter to only changed files if requested
  const finalFilePatterns = opts.onlyChanged
    ? common.filterByGlobs(common.listChangedFiles(), filePatterns)
    : filePatterns;

  const data = await stylelint.lint({
    files: finalFilePatterns,
    formatter: 'string',
    fix: opts.fix,
    allowEmptyInput: true,
  });

  if (data.output != '') {
    console.log(data.output);
  }
  return data.output == '';
}

module.exports = {
  run,
};
