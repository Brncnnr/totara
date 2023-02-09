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

import theme from 'tui/theme';

const scaleString = theme.getVar('char-length-scale');

/**
 * Entries in char length scale.
 */
export const charLengthScale = scaleString
  ? scaleString
      .split(',')
      .map(x => Number(x.trim()))
      .filter(x => !isNaN(x))
  : [];

const validCharLengths = ['full', ...charLengthScale.map(x => x.toString())];

/**
 * Check if input size is valid.
 */
export const isValidCharLength = x =>
  x != null && validCharLengths.includes(x.toString());

/**
 * Common prop definition for input size.
 */
export const charLengthProp = {
  type: [String, Number],
  validator: isValidCharLength,
};
