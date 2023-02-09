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

const lint = require('../lib/lint');

const args = require('yargs')
  .usage('Usage: $0 [options] [globs]')
  .help()
  .version(false)
  .boolean('fix')
  .describe('fix', 'Automatically fix problems and format files')
  .boolean('formatting')
  .describe('formatting', 'Additionally check if source files need formatting')
  .boolean('changed')
  .describe('changed', 'Only process uncommitted files').argv;

const { fix, formatting, changed } = args;
const paths = args._.length ? args._ : undefined;

lint
  .lintFiles({ paths, fix, formatting, changed })
  .then(success => {
    if (success) {
      console.log('Lint passed.');
    } else {
      console.log('Lint failed.');
      process.exit(1);
    }
  })
  .catch(err => {
    console.error(err.stack);
    process.exit(1);
  });
