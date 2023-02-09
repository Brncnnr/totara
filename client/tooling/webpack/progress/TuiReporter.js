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

const chalk = require('chalk').stderr;

const logUpdate = require('log-update').create(process.stderr, {
  showCursor: true,
});

const spinnerFrames = '⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏';

const timeFormatter = new Intl.DateTimeFormat(undefined, {
  hour: 'numeric',
  minute: 'numeric',
  second: 'numeric',
});

let lastRender = Date.now();
module.exports = class TuiReporter {
  constructor() {
    this.frame = 0;
    this.builtModules = 0;
    this.buildStart = null;
  }

  start() {
    if (this.buildStart === null) {
      this.buildStart = Date.now();
    }
  }

  allDone(ctx) {
    if (process.stderr.isTTY) {
      logUpdate('');
      logUpdate.done();
    }

    const builtModules = ctx.statesArray.reduce(
      (acc, x) => acc + x.totalModules,
      0
    );

    let statusMessage = `built ${builtModules} modules`;

    if (ctx.hasErrors) {
      statusMessage = chalk.redBright(statusMessage + ', with errors');
    } else {
      statusMessage = chalk.green(statusMessage);
    }

    let seconds = (Date.now() - this.buildStart) / 1000;
    if (seconds < 10) {
      seconds = Math.round(seconds * 10) / 10;
    } else {
      seconds = Math.round(seconds);
    }

    statusMessage +=
      chalk.grey(` in ${seconds}s`) +
      chalk.grey(' at ' + timeFormatter.format(new Date()));

    process.stderr.write(statusMessage + '\n');

    this.builtModules = 0;
    this.buildStart = null;
  }

  done(context, { stats }) {
    this.builtModules += stats.compilation.modules.size;
    this._render(context.statesArray);
  }

  progress(context) {
    if (Date.now() - lastRender > 50) {
      this._render(context.statesArray);
    }
  }

  _render(statesArray) {
    lastRender = Date.now();

    if (!process.stderr.isTTY) {
      return;
    }

    // calculate overall progress
    let doneModules = 0;
    let totalModules = 0;
    let badEstimate = false;
    statesArray.forEach(x => {
      doneModules += Math.min(x.modules, x.totalModules);
      totalModules += x.totalModules;
      badEstimate = badEstimate || x.totalModulesBadEstimate;
    });
    let overall = Math.min(doneModules / totalModules, 1);
    if (isNaN(overall)) {
      overall = 0;
    }

    const allDone = statesArray.every(x => x.done);

    let spinner = spinnerFrames[this.frame];
    this.frame = (this.frame + 1) % spinnerFrames.length;

    let lines = [
      `${spinner}  ${renderBar(overall)} ${(overall * 100).toFixed(2)}%`,
      `   compiling... ` +
        chalk.grey(
          `${doneModules}/${totalModules} modules` +
            (badEstimate ? ' (estimated)' : '')
        ),
    ].join('\n');

    if (allDone) {
      logUpdate('');
      return;
    }

    logUpdate('\n' + lines + '\n');
  }
};

function renderBar(progress) {
  const size = 40;
  const progressChars = Math.round(progress * size);
  return (
    chalk.bgGreen(' '.repeat(progressChars)) +
    chalk.bgWhite(' '.repeat(size - progressChars))
  );
}
