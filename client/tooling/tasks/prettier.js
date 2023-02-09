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

const prettier = require('../lib/prettier');

const args = require('yargs')
  .usage('Usage: $0 [options] [globs]')
  .help()
  .version(false)
  .boolean('write')
  .describe('write', 'Write formatted file back')
  .boolean('check')
  .describe('check', 'Report files that need formatting')
  .conflicts('write', 'check')
  .describe('changed', 'Only process uncommitted files').argv;

if (!args.write && !args.check) {
  console.log(
    'Error: need one of --write or --check. Pass --help for details.'
  );
  process.exit(1);
}

const paths = args._.length ? args._ : undefined;

prettier
  .run({
    paths,
    write: args.write,
    onlyChanged: args.changed,
  })
  .then(success => {
    if (success) {
      console.log(
        args.write
          ? 'Formatted successfully.'
          : 'Files are correctly formatted.'
      );
    } else {
      console.log(
        args.write ? 'Formatting failed.' : 'Files are not correctly formatted.'
      );
      process.exit(1);
    }
  })
  .catch(err => {
    console.error(err.stack);
    process.exit(1);
  });
