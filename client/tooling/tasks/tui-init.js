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

const fs = require('fs');
const path = require('path');
const { rootDir } = require('../lib/common');
const { formatCodeWithPath } = require('../lib/prettier');

const args = require('yargs')
  .help()
  .version(false)
  .command('$0 <component> <vendor>', false, yargs => {
    yargs
      .positional('component', {
        describe: 'Name of client component to create',
      })
      .positional('vendor', {
        describe: 'Unique string identifying the authoring organisation',
      });
  }).argv;

const componentDir = 'client/component/' + args.component;
const srcDir = componentDir + '/src';
const fullComponentDir = path.join(rootDir, componentDir);
const fullSrcDir = path.join(rootDir, srcDir);

if (fs.existsSync(fullComponentDir)) {
  console.error(`Error: directory ${componentDir} already exists.`);
  process.exit(1);
}

fs.mkdirSync(fullComponentDir);
fs.mkdirSync(fullSrcDir);

/**
 * Write a file if it does not exist, formatting content with prettier.
 *
 * @param {string} file
 * @param {string} content
 */
function write(file, content) {
  const filePath = path.join(fullSrcDir, file);
  if (fs.existsSync(filePath)) {
    console.log(`${file} already exists, skipping...`);
    return;
  }
  if (file.endsWith('.json') && typeof content !== 'string') {
    content = JSON.stringify(content, null, 2);
  }
  content = formatCodeWithPath(filePath, content);
  fs.writeFileSync(filePath, content, 'utf8');
}

console.log(`Initializing TUI in ${componentDir}/...`);

write('tui.json', { component: args.component, vendor: args.vendor });

['js', 'pages', 'components', 'tests', 'tests/unit'].forEach(subdir => {
  const fullSubdir = path.join(fullSrcDir, subdir);
  if (!fs.existsSync(fullSubdir)) {
    fs.mkdirSync(fullSubdir);
  }
});

console.log('Done!');
