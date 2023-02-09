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

/**
 * Calculate total value for field.
 *
 * @param {string[]} sources
 * @param {(string) => any} getValue
 * @returns {?number}
 */
export function calculateTotal(sources, getValue) {
  if (!sources) return null;
  const values = [];
  sources.forEach(source => {
    const value = Number(getValue(source));
    if (!isNaN(value)) {
      values.push(value);
    }
  });
  return values.length > 0 ? values.reduce((acc, cur) => acc + cur, 0) : null;
}
