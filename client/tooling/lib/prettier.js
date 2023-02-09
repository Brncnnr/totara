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

const prettier = require('prettier');
const fs = require('fs');
const path = require('path');
const fg = require('fast-glob');
const { rootDir, filterByGlobs, listChangedFiles } = require('./common');
const patterns = require('./patterns');

async function run(opts) {
  let hasError = false;
  let hasIssues = false;
  const issueFiles = [];

  const filePatterns = opts.paths
    ? filterByGlobs(opts.paths, patterns.prettier)
    : patterns.prettier;

  // filter to only changed files if requested
  const finalFilePatterns = opts.onlyChanged
    ? filterByGlobs(listChangedFiles(), filePatterns)
    : filePatterns;

  // resolve globs to get an array of files to check
  const files = [].concat(
    ...finalFilePatterns.map(glob => fg.sync(glob, { cwd: rootDir }))
  );

  // format/check each file
  files.forEach(file => {
    // get info we need to pass to prettier
    const fullFilePath = path.isAbsolute(file)
      ? file
      : path.join(rootDir, file);

    const config = getConfig(fullFilePath);

    if (!config) {
      return;
    }

    // read file and format
    const input = fs.readFileSync(fullFilePath, 'utf8');
    try {
      if (opts.write) {
        const output = prettier.format(input, config);
        if (output !== input) {
          fs.writeFileSync(fullFilePath, output, 'utf8');
        }
      } else {
        if (!prettier.check(input, config)) {
          issueFiles.push(file);
          hasIssues = true;
        }
      }
    } catch (e) {
      hasError = true;
      console.log(`\n\n${e.message}\n\nError in file: ${file}\n\n`);
    }
  });

  if (hasIssues) {
    console.log('Files failed prettier check:');
    issueFiles.forEach(function(file) {
      console.log('* ' + file);
    });
    console.log('');
  }

  return !hasError && !hasIssues;
}

function getConfig(filePath) {
  const info = prettier.getFileInfo.sync(filePath, {
    ignorePath: path.join(rootDir, '.eslintignore'),
  });

  let config = prettier.resolveConfig.sync(filePath);
  if (!config) {
    return;
  }

  // The config could override the default parser
  const parser = config.parser || info.inferredParser;

  if (info.ignored || parser == null) {
    return;
  }

  return { ...config, parser: parser };
}

function formatCodeWithPath(filePath, code) {
  const config = getConfig(filePath);
  return prettier.format(code, config);
}

module.exports = {
  run,
  formatCodeWithPath,
};
