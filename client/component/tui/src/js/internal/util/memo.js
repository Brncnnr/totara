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
 * Create a memoized version of the provided function.
 *
 * By default, the first argument to the function is used as the cache key.
 * If you pass a function as `keyFn`, it will be called with the invocation's
 * arguments to generate a cache key.
 *
 * The cache key can be anything, it is not restricted to being a string.
 *
 * @param {function} fn
 * @param {function=} keyFn
 * @returns {function}
 */
export function memoize(fn, keyFn) {
  return memoizeWithMap(fn, keyFn, new Map());
}

/**
 * Create a memoized version of the provided function.
 *
 * Like `memoize()` but using a WeakMap.
 *
 * @param {function} fn
 * @param {function=} keyFn
 * @returns {function}
 */
export function memoizeWeak(fn, keyFn) {
  return memoizeWithMap(fn, keyFn, new WeakMap());
}

/**
 * Create a memoized version of the provided function.
 *
 * Like `memoize()` but using a the map you pass for key storage.
 *
 * @private
 * @param {function} fn
 * @param {function=} keyFn
 * @param {object} map Object implementing Map API (has/get/set)
 * @returns {function}
 */
function memoizeWithMap(fn, keyFn, map) {
  return function(arg) {
    const key = keyFn ? keyFn.apply(this, arguments) : arg;
    if (map.has(key)) {
      return map.get(key);
    }
    const result = fn.apply(this, arguments);
    map.set(key, result);
    return result;
  };
}

/**
 * Wrap the provided async or promise-returning function so that subsequent
 * calls get the same result, and calls while loading get the same promise
 * to avoid making multiple requests.
 *
 * @param {function} fn
 * @return {Promise}
 */
export function memoizeLoad(fn) {
  let promise;
  const memoized = () => {
    if (promise) {
      return promise;
    }

    promise = fn();
    promise.catch(() => (promise = null));
    return promise;
  };

  /* istanbul ignore next */
  if (process.env.NODE_ENV == 'test') {
    memoized._fn = fn;
    memoized._change = newFn => {
      memoize._fn = fn = newFn;
      promise = null;
    };
  }
  return memoized;
}
