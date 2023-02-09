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
 * Implements has, get, set, and delete methods from Map.
 *
 * Keys must be arrays.
 */
export default class ArrayKeyedMap {
  constructor() {
    this._keys = [];
    this._values = [];
  }

  /**
   * Check if the key has a value in the map.
   *
   * @param {Array} key
   * @returns {boolean}
   */
  has(key) {
    return this._findIndex(key) !== -1;
  }

  /**
   * Get the value associated with the key, or undefined if none.
   *
   * @param {Array} key
   * @returns {*}
   */
  get(key) {
    const index = this._findIndex(key);
    return index !== -1 ? this._values[index] : undefined;
  }

  /**
   * Associate a value with a key.
   *
   * @param {Array} key
   * @param {*} value
   * @returns {ArrayKeyedMap} The current map.
   */
  set(key, value) {
    const index = this._findIndex(key);
    if (index === -1) {
      this._keys.push(Array.prototype.slice.apply(key));
      this._values.push(value);
    } else {
      this._values[index] = value;
    }
    return this;
  }

  /**
   * Remove the value associated with the key from the map.
   *
   * @param {Array} key
   * @returns {boolean} True if the key had a value, false if it did not.
   */
  delete(key) {
    const index = this._findIndex(key);
    if (index !== -1) {
      this._keys.splice(index, 1);
      this._values.splice(index, 1);
      return true;
    }
    return false;
  }

  /**
   * Find the index of the key in the data array, or -1 if not found.
   *
   * @private
   * @param {Array} key
   * @returns {number} Index, or -1 if not found.
   */
  _findIndex(key) {
    if (!Array.isArray(key)) {
      throw new Error('key must be an array');
    }
    return this._keys.findIndex(x => this._keyEqual(x, key));
  }

  /**
   * Check if two keys are equal.
   *
   * Compares key array elements using SameValueZero.
   *
   * @private
   * @param {Array} a
   * @param {Array} b
   * @returns {boolean}
   */
  _keyEqual(a, b) {
    return a.length === b.length && a.every((x, i) => sameValueZero(x, b[i]));
  }
}

/**
 * Compare two values using the SameValueZero algorithm.
 *
 * {@see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Equality_comparisons_and_sameness}
 *
 * @param {*} x
 * @param {*} y
 * @returns {boolean}
 */
function sameValueZero(x, y) {
  return (
    x === y ||
    (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y))
  );
}
