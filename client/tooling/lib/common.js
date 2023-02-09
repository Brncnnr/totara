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

const path = require('path');
const findCacheDir = require('find-cache-dir');
const fs = require('fs');
const os = require('os');
const minimatch = require('minimatch');
const crypto = require('crypto');
const execFileSync = require('child_process').execFileSync;

/**
 * @var {string} rootDir Root directory of Totara repository.
 */
const rootDir = path.join(__dirname, '../../../');

/**
 * @var {string} clientDir "client" directory.
 */
const clientDir = path.join(__dirname, '../../');

/**
 * Execute a command.
 *
 * @private
 * @param {string} command
 * @param {string[]} args
 * @returns {string} Command output.
 */
function exec(command, args) {
  const options = {
    cwd: process.cwd(),
    env: process.env,
    stdio: 'pipe',
    encoding: 'utf-8',
  };
  return execFileSync(command, args, options);
}

/**
 * Run a git command
 *
 * @param {string[]} args
 * @return {string[]} Returned lines.
 */
function execGit(args) {
  return exec('git', args)
    .trim()
    .toString()
    .split('\n');
}

let changedFiles = null;

/**
 * Get a list of files with uncommitted changes.
 *
 * @return {string[]}
 */
function listChangedFiles() {
  if (changedFiles !== null) {
    return changedFiles;
  }
  const gitCmds = [
    // get modified and staged files (except deleted)
    ['diff', '--name-only', '--diff-filter=ACMRTUB', 'HEAD'],
    // get untracked files
    ['ls-files', '--others', '--exclude-standard'],
  ];
  changedFiles = arrayUnique(...gitCmds.map(execGit));
  return changedFiles;
}

/**
 * Filter files to those matching any of the provided glob patterns.
 *
 * @param {string[]} files Real files.
 * @param {string[]} patterns Glob patterns.
 * @returns {string[]} Matching files.
 */
function filterByGlobs(files, patterns) {
  const intersection = [];
  patterns.forEach(pattern =>
    Array.prototype.push.apply(
      intersection,
      minimatch.match(files, pattern, { matchBase: true })
    )
  );
  return arrayUnique(intersection);
}

/**
 * Deduplicate array.
 *
 * Pass multiple arrays as separate arguments and they will be merged together.
 *
 * @param {...array} array
 * @returns {array}
 */
function arrayUnique(array) {
  if (arguments.length != 1) {
    array = [].concat(...arguments);
  }
  return [...new Set(array)];
}

/**
 * Escape a string to make it suitable for including in a regular expression
 *
 * @param {string} s
 * @returns {string}
 */
function escapeRegExp(s) {
  return s.replace(/[\\^$.*+?()[\]{}|-]/g, '\\$&');
}

/**
 * Get a SHA-256 hex digest of the provided string.
 *
 * @param {string} algorithm e.g. sha256
 * @param {string} str
 * @param {string} [encoding=hex] hex by default
 */
function hash(algorithm, str, encoding = 'hex') {
  const hash = crypto.createHash(algorithm);
  hash.update(str);
  return hash.digest(encoding);
}

let installHash = null;

/**
 * Get hash for totara directory.
 *
 * @returns {string}
 */
function getInstallHash() {
  if (installHash) {
    return installHash;
  }
  installHash = hash('sha256', rootDir);
  return installHash;
}

let installTemp = null;

/**
 * Get the path to a directory that can be used to store cached data.
 *
 * @returns {string}
 */
function getCacheDir() {
  if (installTemp) {
    return installTemp;
  }

  const cacheDir = findCacheDir({ name: 'tui', cwd: rootDir, create: true });
  if (cacheDir) {
    installTemp = cacheDir;
    return installTemp;
  }

  const tuiTemp = path.join(os.tmpdir(), 'tui_cache');
  if (!fs.existsSync(tuiTemp)) {
    fs.mkdirSync(tuiTemp);
  }
  const installTempPath = path.join(tuiTemp, getInstallHash());
  if (!fs.existsSync(installTempPath)) {
    fs.mkdirSync(installTempPath);
  }
  installTemp = installTempPath;
  return installTemp;
}

module.exports = {
  rootDir,
  clientDir,
  listChangedFiles,
  filterByGlobs,
  arrayUnique,
  escapeRegExp,
  hash,
  getCacheDir,
};
