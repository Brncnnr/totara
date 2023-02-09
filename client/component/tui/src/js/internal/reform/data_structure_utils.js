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

import Vue from 'vue';
import { isLangString } from 'tui/i18n';
import { orderBy, isPlainObject, structuralShallowClone } from 'tui/util';

/**
 * Check if two arrays are shallowly == (all of their items are ==)
 *
 * @param {array} a
 * @param {array} b
 * @returns {boolean}
 */
export const arrayEqual = (a, b) =>
  a.length == b.length && arrayStartsWith(a, b);

/**
 * Check if an array starts with a prefix (using ==)
 *
 * @param {array} arr
 * @param {array} prefix
 * @returns {boolean}
 */
export const arrayStartsWith = (arr, prefix) =>
  prefix.every((x, i) => arr[i] == x);

/**
 * Ensure path-like value is a path.
 *
 * @param {(array|string)} path
 * @returns {array}
 */
export const toPath = path => (Array.isArray(path) ? path : [path]);

/**
 * Helper to check if value is a plain data structure (object or array).
 *
 * @param {*} value
 * @returns {boolean}
 */
export const isDataStructure = value =>
  isPlainObject(value) || Array.isArray(value);

/**
 * Check if two path-function entries are equal.
 *
 * @param {[(string|number|array), function]} a
 * @param {[(string|number|array), function]} b
 * @returns {bool}
 */
export const pathFunctionEqual = (a, b) =>
  arrayEqual(toPath(a[0]), toPath(b[0])) && a[1] == b[1];

/**
 * Sort [path, value] entries by the length of the path (shortest to longest).
 *
 * @param {array} arr
 * @returns {array}
 */
export const sortEntriesByPath = arr =>
  orderBy(arr, ([path]) => (path ? path.length : 0));

/**
 * Merge error objects.
 *
 * @param {object} result Error result. Will be mutated.
 * @param {object} newErrors Errors to merge in.
 */
export function mergeErrors(result, newErrors) {
  if (!newErrors) {
    return result;
  }
  Object.keys(newErrors).forEach(k => {
    const val = newErrors[k];
    if (k in result && result[k] != null) {
      if (!val) return;
      if (isDataStructure(val)) {
        result[k] = structuralShallowClone(result[k]);
        mergeErrors(result[k], val);
      } else {
        // Vue.set not needed as already in result
        result[k] = val;
      }
    } else {
      // doesn't exist in result - just assign
      // shallow clone so later updates to the same property don't modify
      // the value from newErrors
      Vue.set(result, k, structuralShallowClone(val));
    }
  });
  return result;
}

/**
 * Filter nested errors object to keys that have a truthy value in touched.
 *
 * @param {object} errors
 * @param {object} touched
 * @returns {object}
 */
export function onlyTouched(errors, touched, touchedTest) {
  return onlyTouchedImpl(errors, touched, touchedTest);
}

/**
 * Filter nested errors object to keys that have a truthy value in touched.
 *
 * @private
 * @param {object} errors
 * @param {object} touched
 * @returns {object}
 */
function onlyTouchedImpl(errors, touched, touchedTest, path = []) {
  return Object.entries(errors)
    .filter(
      ([key]) => (touched && touched[key]) || touchedTest(path.concat(key))
    )
    .reduce(
      (acc, [key, value]) => {
        acc[key] = isDataStructure(value)
          ? onlyTouchedImpl(
              value,
              touched && touched[key],
              touchedTest,
              path.concat(key)
            )
          : value;
        return acc;
      },
      Array.isArray(errors) ? [] : {}
    );
}

/**
 * Make a touch object that covers all entries in errors.
 *
 * @param {(object|array)} errors
 * @returns {(object|array)}
 */
export function makeAllTouch(errors) {
  return Object.entries(errors).reduce(
    (acc, [key, value]) => {
      if (value) {
        acc[key] = isDataStructure(value) ? makeAllTouch(value) : true;
      }
      return acc;
    },
    Array.isArray(errors) ? [] : {}
  );
}

/**
 * Collect all values from an error object.
 *
 * @param {(object|array)} errors
 */
export function collectErrorValues(errors) {
  const arr = [];
  collectErrorValuesInternal(arr, errors);
  return arr;
}

/**
 * Internal implementation of $_collectErrorValues
 *
 * @param {array} arr Output
 * @param {(object|array)} errors
 */
export function collectErrorValuesInternal(arr, errors) {
  return Object.values(errors).forEach(value => {
    if (isDataStructure(value)) {
      collectErrorValuesInternal(arr, value);
    } else if (value) {
      arr.push(value);
    }
  });
}

/**
 * Collect all language strings from an error object.
 *
 * @param {array} arr Output array
 * @param {(object|array)} errors
 */
export function collectLangStrings(errors) {
  return collectErrorValues(errors).filter(x => isLangString(x));
}
