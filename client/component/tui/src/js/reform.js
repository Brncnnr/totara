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
import { TOUCHED } from './internal/reform/constants';

/**
 * Get whether the specified field has been touched by the user.
 *
 * @param {*} state Reform state
 * @param {string|number|array} path
 * @returns {boolean}
 */
export function isTouched(state, path) {
  return !!get(state[TOUCHED], path);
}
