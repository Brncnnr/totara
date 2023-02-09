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

/**
 * Remove the first instance of the specified item from the array.
 *
 * @param {array} array
 * @param {*} item
 */
export function pull(array, item) {
  const index = array.indexOf(item);
  if (index != -1) {
    array.splice(index, 1);
  }
}

/**
 * Return a copy of array filtered to only contain unique values.
 *
 * @param {array} arr
 * @returns {array}
 */
export function unique(arr) {
  return arr.filter((item, pos) => arr.indexOf(item) === pos);
}

/**
 * Create a new object composed of the selected keys of the provided object.
 *
 * @param {object} object
 * @param {array} keys
 * @return {object}
 */
export function pick(object, keys) {
  if (!Array.isArray(keys)) {
    throw new Error('keys must be an array');
  }
  const newObj = {};
  for (let i = 0; i < keys.length; i++) {
    const key = keys[i];
    if (key in object) {
      newObj[key] = object[key];
    }
  }
  return newObj;
}

/**
 * Splits a collection into sets, group by the result of running each value
 * through `fn`.
 *
 * @param {*} array
 * @param {*} fn
 */
export function groupBy(array, fn) {
  const result = {};
  array.forEach(x => {
    const key = fn(x);
    if (!result[key]) {
      result[key] = [];
    }
    result[key].push(x);
  });
  return result;
}

/**
 * Sort the supplied array using the supplied function to generate a sort key.
 *
 * @param {array} array
 * @param {function} fn
 * @returns {array}
 */
export function orderBy(array, fn) {
  array = array.slice();
  array.sort((a, b) => {
    const aScore = fn(a);
    const bScore = fn(b);
    if (aScore < bScore) {
      return -1;
    } else if (aScore > bScore) {
      return 1;
    } else {
      return 0;
    }
  });
  return array;
}
