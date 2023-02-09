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

import { get } from 'tui/util';
import { collectErrorValues } from './data_structure_utils';

export default class ValidationResults {
  /** @private */
  constructor(errors) {
    /** @private */
    this._errors = errors;
    /** @private */
    this._isValid = null;
  }

  /**
   * Check if there are any errors in this form.
   *
   * @returns {boolean}
   */
  get isValid() {
    if (this._isValid === null) {
      this._isValid = collectErrorValues(this._errors).every(x => !x);
    }
    return this._isValid;
  }

  /**
   * Get error at path.
   *
   * @param {(string|number|array)} path
   * @returns {?any}
   */
  getError(path = null) {
    return path == null ? this._errors : get(this._errors, path);
  }
}
