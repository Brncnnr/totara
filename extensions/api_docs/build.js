/**
 * This file is part of the Totara API docs.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @module api_docs
 */

const fs = require('fs');
const path = require('path');
const chalk = require('chalk').stderr;
const crypto = require('crypto');
const { execFile } = require('child_process');

const args = require('yargs')(process.argv.slice(2))
  .usage('Usage: $0 [options] [schema-directory]')
  .help()
  .version(false)
  .boolean('debug')
  .describe('debug', 'Verbose output from spectaql command')
  .default('debug', false)
  .check(argv => {
    const filePaths = argv._;
    if (filePaths.length < 1) {
      throw new Error(
        'You must provide the path to the directory with the schema definitions.'
      );
    } else {
      return true;
    }
  }).argv;

const docsFileRoot = path.resolve(args._[0]);

const schemaFile = path.join(docsFileRoot, 'schema.external.graphqls');
const metadataFile = path.join(docsFileRoot, 'metadata.external.json');
const navFile = path.join(docsFileRoot, 'nav.js');

let schema;
// Check schema file is readable.
try {
  schema = fs.readFileSync(schemaFile, 'utf8');
} catch (err) {
  console.error(
    chalk.redBright(
      "Error: cannot read schema file '" +
        schemaFile +
        "'. Check directory is correct and readable"
    )
  );
  process.exit(1);
}

try {
  fs.readFileSync(metadataFile, 'utf8');
} catch (err) {
  console.error(
    chalk.redBright(
      "Error: cannot read metadata file '" +
        metadataFile +
        "'. Check directory is correct and readable"
    )
  );
  process.exit(1);
}

const { nav: totaraNav } = require(navFile);

const hash = crypto
  .createHash('sha1')
  .update(schema)
  .digest('hex');

const spectaql = path.join(__dirname, 'node_modules/.bin/spectaql');

if (!fs.existsSync(spectaql)) {
  console.error(
    chalk.redBright(
      `Error: SpectaQL not installed. Run 'npm ci' in ${
        process.cwd() === __dirname ? 'the current directory' : __dirname
      } to install dependency.`
    )
  );
  process.exit(1);
}

try {
  process.chdir(path.join(__dirname, 'spectaql'));
} catch (err) {
  console.error(chalk.redBright('Error: cannot change to spectaql directory.'));
  process.exit(1);
}

// Create the build directory if it doesn't exist.
let buildDir = path.join(__dirname, 'build/spectaql');
if (!fs.existsSync(buildDir)) {
  fs.mkdirSync(buildDir);
}

execFile(
  spectaql,
  [
    '--embeddable',
    '--one-file',
    '--target-file=out.html',
    `--target-dir=${buildDir}`,
    `--schema-file=${schemaFile}`,
    `--introspection-metadata-file=${metadataFile}`,
    'config.yml',
  ],
  { env: { ...process.env, NAV: JSON.stringify(totaraNav) } },
  (error, stdout, stderr) => {
    if (error) {
      console.log(`error: ${error.message}`);
      process.exit(1);
    }
    if (stderr) {
      console.log(`error: ${stderr}`);
      process.exit(1);
    }

    // If --debug flag set, pass spectaql output to console.
    if (args.debug) {
      console.log(stdout);
    }

    // On success also save build details
    let json = {
      timestamp: new Date().toISOString(),
      schemahash: hash,
    };
    let content = JSON.stringify(json, null, 2);
    fs.writeFileSync(path.join(buildDir, '/out.json'), content, 'utf8');
  }
);
