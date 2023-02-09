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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module totara_engage
 */

const slice = Array.prototype.slice;

/**
 * Returning an array of array.
 *
 * @param {Object[]} items
 * @param {number} perRow
 * @param {boolean} [padding=false]
 *
 * @return {Object[]}
 */
export function calculateRow(items, perRow, padding = false) {
  if (!Array.isArray(items) || !items.length || perRow === 0) {
    return [];
  }

  let value = items.slice();
  let rows = [];

  for (let index = 0; index < value.length; index += perRow) {
    let row = slice.call(value, index, index + perRow);
    rows.push({ index: rows.length, items: row });
  }

  if (!padding) return rows;

  // Fill the empty slots
  const remainder = items.length % perRow;
  if (remainder <= 0) {
    return rows;
  }

  const slotNum = perRow - remainder;
  for (let index = 0; index < slotNum; index += 1) {
    rows[rows.length - 1].items.push({ component: 'FillSlot' });
  }
  return rows;
}

export const engageGrid = {
  xsmall: {
    name: 'xsmall',
    boundaries: [0, 480],
    direction: 'horizontal',
    maxItemsPerRow: 1,
    cardUnits: 3,
  },

  small: {
    name: 'small',
    boundaries: [481, 649],
    direction: 'horizontal',
    maxItemsPerRow: 2,
    cardUnits: 3,
  },

  medium: {
    name: 'medium',
    boundaries: [650, 822],
    direction: 'horizontal',
    maxItemsPerRow: 3,
    cardUnits: 3,
  },

  large: {
    name: 'large',
    boundaries: [823, 1072],
    direction: 'horizontal',
    maxItemsPerRow: 4,
    cardUnits: 2,
  },

  xlarge: {
    name: 'xlarge',
    boundaries: [1073, 1372],
    direction: 'horizontal',
    maxItemsPerRow: 5,
    cardUnits: 2,
  },
};
