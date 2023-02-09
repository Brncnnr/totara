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

const eslint = require('./eslint');
const stylelint = require('./stylelint');
const prettier = require('./prettier');

async function lintFiles({ changed, fix, paths, formatting }) {
  const eslintSuccess = await eslint.run({
    onlyChanged: changed,
    fix,
    paths,
  });

  const stylelintSuccess = await stylelint.run({
    onlyChanged: changed,
    fix,
    paths,
  });

  const prettierSuccess =
    !formatting ||
    (await prettier.run({
      onlyChanged: changed,
      write: fix,
      paths,
    }));

  return eslintSuccess && stylelintSuccess && prettierSuccess;
}

module.exports = {
  lintFiles,
};
