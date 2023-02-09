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

const Estimator = require('./Estimator');
const TuiReporter = require('./TuiReporter');

const DEFAULT_STATE = {
  done: false,
  hasErrors: false,
  modules: 0,
  totalModules: 0,
  totalModulesBadEstimate: false,
  otherTasks: 0,
};

module.exports = class ProgressReportPluginState {
  constructor(opts) {
    this.reporter = opts.reporter || new TuiReporter();
    this.estimator = opts.estimator || new Estimator({ key: opts.key });

    this.states = {};
  }

  sendReporter(fn, instance, payload = {}) {
    if (this.reporter[fn]) {
      try {
        this.reporter[fn](this, payload);
      } catch (e) {
        process.stdout.write(e.stack + '\n');
      }
    }
  }

  get hasRunning() {
    return Object.values(this.states).some(state => !state.done);
  }

  get hasErrors() {
    return Object.values(this.states).some(state => state.hasErrors);
  }

  get statesArray() {
    return Object.values(this.states);
  }

  ensureState(name) {
    if (!this.states[name]) {
      this.states[name] = { ...DEFAULT_STATE };
    }
  }

  resetState(name) {
    this.ensureState(name);
    Object.assign(this.states[name], DEFAULT_STATE);
  }

  /**
   * @param {import('./ProgressReportPlugin')} plugin
   */
  // eslint-disable-next-line no-unused-vars
  pluginFinished(plugin) {
    if (!this.hasRunning) {
      this.sendReporter('allDone');

      Object.values(this.states).forEach(x => {
        Object.assign(x, {
          ...DEFAULT_STATE,
          done: x.done,
          hasErrors: x.hasErrors,
        });
      });

      this.estimator.writeOnce();
    }
  }
};
