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

const path = require('path');
const fs = require('fs');
const { getCacheDir, hash } = require('../../lib/common');

module.exports = class Estimator {
  constructor({ key }) {
    this._estModules = require('./progress_defaults');
    this._estimatesWritten = false;
    const dir = path.join(getCacheDir(), 'webpack_build_progress');
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir);
    }
    this._estimatePath = path.join(
      dir,
      hash('sha256', key).slice(0, 8) + '.json'
    );

    if (fs.existsSync(this._estimatePath)) {
      try {
        const json = JSON.parse(fs.readFileSync(this._estimatePath, 'utf-8'));
        Object.assign(this._estModules, json.modules);
      } catch (e) {
        try {
          fs.unlinkSync(this._estimatePath);
        } catch (e) {
          // nothing we can do, just ignore it
        }
      }
    }
  }

  /**
   * Get estimated number of components for module.
   *
   * @param {string} component
   * @returns {number}
   */
  getModules(component) {
    return component in this._estModules ? this._estModules[component] : null;
  }

  /**
   * Update estimate.
   *
   * @param {string} component
   * @param {number} estimate
   */
  updateModules(component, estimate) {
    this._estModules[component] = estimate;
  }

  /**
   * Write updated estimate if it has not been written yet.
   */
  writeOnce() {
    // avoid writing incorrect values for incremental builds
    if (this._estimatesWritten) return;
    this._estimatesWritten = true;

    fs.writeFileSync(
      this._estimatePath,
      JSON.stringify({ modules: this._estModules }, null, 2) + '\n'
    );
  }
};
