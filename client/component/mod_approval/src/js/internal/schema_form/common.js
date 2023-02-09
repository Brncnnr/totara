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
 * @module mod_approval
 */

/**
 * Compare two variables.
 *
 * @param {*} a
 * @param {'='|'>'|'>='|'<'|'<='} op Comparison operation
 * @param {*} b
 */
export function comparisonResult(a, op, b) {
  switch (op) {
    case '=':
      return a == b;
    case '>':
      return a > b;
    case '>=':
      return a >= b;
    case '<':
      return a < b;
    case '<=':
      return a <= b;
  }
  console.warn('invalid op: ' + op);
  return false;
}

/**
 * Format Date object to match format string.
 *
 * @param {Date} date
 * @param {?string} format Format string, e.g. "Y-m-d". Only "Y" "m" and "d" format tokens are supported.
 * @returns {string}
 */
export function formatDate(date, format) {
  if (!format) {
    format = 'Y-m-d';
  }
  return format
    .replace('Y', padStart(date.getFullYear().toString(), 4, '0'))
    .replace('m', padStart((date.getMonth() + 1).toString(), 2, '0'))
    .replace('d', padStart(date.getDate().toString(), 2, '0'));
}

/**
 * Pad start of string.
 *
 * @param {string} str input string
 * @param {number} len target length
 * @param {string} pad character to pad with
 * @returns {string}
 */
const padStart = (str, len, pad) => {
  while (str.length < len) {
    str = pad + str;
  }
  return str;
};
