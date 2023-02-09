/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @module mod_approval
 */

import { langString } from 'tui/i18n';
import { comparisonResult } from '../common';

/** @type {Object<string, import('./defs').ValidatorDef>} */

/**
 * Strip time component from an ISO 8601 string
 *
 * @param {string} date
 * @returns {string}
 */
function stripTime(date) {
  if (typeof date !== 'string') return date;
  const index = date.indexOf('T');
  if (index === -1) {
    return date;
  } else {
    return date.substr(0, index);
  }
}

/**
 * Convert ISO 8601 date (not including time!) to a comparable number
 *
 * @param {string} date
 * @returns {number}
 */
function isoDateToComparable(date) {
  const result = /^(\d{4})-(\d{2})-(\d{2})/.exec(date);
  if (!result) return null;
  const [, y, m, d] = result;
  // pack into a number like 20210914
  return (y | 0) * 10000 + (m | 0) * 100 + (d | 0);
}

export default {
  date_compare: {
    validate: (val, opts) => {
      const a = val && isoDateToComparable(stripTime(val));
      let b = null;
      if (opts.value === 'today') {
        b = new Date();
        b.setHours(0);
        b.setMinutes(0);
        b.setSeconds(0);
      } else if (opts.value) {
        b = isoDateToComparable(stripTime(opts.value));
      }
      return !a || !b || comparisonResult(a, opts.operation, b);
    },
    message: (val, opts) => {
      if (!opts.value) return '?';
      switch (opts.operation) {
        case '>':
        case '>=':
          return (
            opts.message ||
            langString(
              'date_before_limit',
              'totara_form',
              stripTime(opts.value)
            )
          );
        case '<':
        case '<=':
          return (
            opts.message ||
            langString('date_after_limit', 'totara_form', stripTime(opts.value))
          );
      }
      return '?';
    },
  },

  max: {
    validate: (val, opts) => Number(val) <= opts.value,
    message: (val, opts) =>
      langString('validation_invalid_max', 'totara_core', {
        max: opts.value,
      }),
  },

  min: {
    validate: (val, opts) => Number(val) >= opts.value,
    message: (val, opts) =>
      langString('validation_invalid_min', 'totara_core', {
        min: opts.value,
      }),
  },

  maxlength: {
    validate: (val, opts) =>
      typeof val === 'string' && val.trim().length <= opts.value,
    message: (val, opts) =>
      langString('validation_invalid_max_length', 'totara_core', {
        len: opts.value,
      }),
  },
};
