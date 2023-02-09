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

import ArrayKeyedMap from './ArrayKeyedMap';
import pending from '../../pending';

/**
 * Creates a throttled function that invokes `fn` at most every `wait` ms.
 *
 * The last used arguments and `this` value will be passed to `fn` when it is
 * invoked.
 *
 * If you would like each combination of `this` and arguments to be throttled
 * separately, pass `{ perArgs: true }` to `options`.
 *
 * Executing on the leading edge means that `fn` will be called the first time
 * the throttled function is called, and no more calls will be made until `wait`
 * has passed and the throttled function is called again.
 *
 * Executing on the trailing edge means that `fn` will be called after `wait` ms
 * has elapsed (from now, if this is the first call, or since the last call if
 * we're within the wait period).
 * Additional calls to the throttled function during the wait period will have
 * no effect if it is already flagged to be run.
 *
 * If combining leading and trailing edges, `fn` is not called on the trailing
 * edge as result of the initial call, only if another call is made during the
 * wait period.
 *
 * See https://css-tricks.com/debouncing-throttling-explained-examples/ for a
 * more visual explanation of leading and trailing calls and the difference
 * between debouncing and throttling.
 *
 * @param {function} fn Function to call.
 * @param {number} wait Miliseconds to wait.
 * @param {object} [options]
 * @param {boolean} [options.leading=true] Execute on leading edge?
 * @param {boolean} [options.trailing=true] Execute on trailing edge?
 * @param {boolean} [options.perArgs=false] Throttle separately per argument set?
 * @returns {function}
 */
export function throttle(fn, wait, options) {
  const { leading = true, trailing = true, perArgs = false } = options || {};
  const impl = perArgs ? throttleCommonMemo : throttleCommon;
  return impl(fn, wait, { leading, trailing });
}

/**
 * Creates a debounced function that won't invoke `fn` until at least `wait`
 * ms have passed since the last call to the debounced function.
 *
 * The last used arguments and `this` value will be passed to `fn` when it is
 * invoked.
 *
 * If you would like each combination of `this` and arguments to be throttled
 * separately, pass `{ perArgs: true }` to `options`.
 *
 * Executing on the leading edge means that `fn` will be called the first time
 * the debounced function is called, and no more calls will be made until it has
 * been at least `wait` ms since the last call and the debounced function is
 * called again.
 *
 * Executing on the trailing edge means that `fn` will be called after `wait` ms
 * has elapsed (from now, if this is the first call, or since the last call if
 * we're within the wait period).
 * Additional calls to the throttled function during the wait period will have
 * no effect if it is already flagged to be run.
 *
 * If combining leading and trailing edges, `fn` is not called on the trailing
 * edge as result of the initial call, only if another call is made during the
 * wait period.
 *
 * By default, `fn` is only called on the trailing edge.
 *
 * See https://css-tricks.com/debouncing-throttling-explained-examples/ for a
 * more visual explanation of leading and trailing calls and the difference
 * between debouncing and throttling.
 *
 * @param {function} fn Function to call.
 * @param {number} wait Miliseconds to wait.
 * @param {object} [options]
 * @param {boolean} [options.leading=false] Execute on leading edge?
 * @param {boolean} [options.trailing=true] Execute on trailing edge?
 * @param {boolean} [options.perArgs=false] Throttle separately per argument set?
 * @returns {function}
 */
export function debounce(fn, wait, options) {
  const { leading = false, trailing = true, perArgs = false } = options || {};
  const impl = perArgs ? throttleCommonMemo : throttleCommon;
  return impl(fn, wait, { leading, trailing, extendWait: true });
}

/**
 * Version of throttleCommon that is memoized based on arguments.
 *
 * @private
 * @param {function} fn
 * @param {number} wait
 * @param {object} [options]
 * @returns {function}
 */
function throttleCommonMemo(fn, wait, options) {
  let map, nullary;

  function make(key) {
    return throttleCommon(fn, wait, options, () => {
      if (map && key) {
        map.delete(key);
      }
    });
  }

  return function() {
    const key = (this !== undefined || arguments.length !== 0) && [
      this,
      ...arguments,
    ];

    // fast path for when args/this don't matter
    if (!key) {
      const fn = nullary || (nullary = make(key));
      return fn.apply(this, arguments);
    }

    // create map lazily
    if (!map) {
      map = new ArrayKeyedMap();
    }

    // get from map if existing
    let val = map.get(key);
    if (!val) {
      val = make(key);
      map.set(key, val);
    }
    return val.apply(this, arguments);
  };
}

/**
 * Common implementation for throttle/debounce
 *
 * Creates a throttled function that invokes `fn` at most every `wait` ms.
 *
 * The last used arguments and `this` value will be passed to `fn` when it is
 * invoked.
 *
 * Executing on the leading edge means that `fn` will be called the first time
 * the throttled function is called, and no more calls will be made until `wait`
 * has passed and the throttled function is called again.
 *
 * Executing on the trailing edge means that `fn` will be called after `wait` ms
 * has elapsed (from now, if this is the first call, or since the last call if
 * we're within the wait period).
 * Additional calls to the throttled function during the wait period will have
 * no effect if it is already flagged to be run.
 *
 * If combining leading and trailing edges, `fn` is not called on the trailing
 * edge as result of the initial call, only if another call is made during the
 * wait period.
 *
 * If extendWait is passed, additional calls during the wait period will
 * extend it so that it ends `wait` ms from now.
 *
 * See https://css-tricks.com/debouncing-throttling-explained-examples/ for a
 * more visual explanation of leading and trailing calls.
 *
 * @private
 * @param {function} fn Function to call.
 * @param {number} wait Miliseconds to wait.
 * @param {object} [options]
 * @param {boolean} [options.leading=true] Execute on leading edge?
 * @param {boolean} [options.trailing=true] Execute on trailing edge?
 * @param {boolean} [options.extendWait=true] Reset time until the next call on every call?
 * @param {function} [done] Function that is called when wait period is over
 * @returns {function}
 */
function throttleCommon(fn, wait, options = {}, done) {
  const { leading = true, trailing = true, extendWait = false } = options;

  // handle to timeout, set when in wait period
  let timeout = null;

  // store pending
  let pendingDone = null;

  // call fn when timeout expires?
  let expireCall = false;

  // store invocation details
  let args;
  let context;

  // handle end of wait period
  function expire() {
    timeout = null;
    if (expireCall) {
      expireCall = false;
      fn.apply(context, args);
      // set another timeout to maintain proper spacing between calls
      // otherwise (if leading was on) a call right after the trailing call
      // would execute immediately
      // or (if leading was off) a call nearly `wait` ms after the trailing call
      // would execute double `wait` ms after the last call instead of `wait` ms
      timeout = setTimeout(expire, wait);
    } else {
      // Resolve pending
      if (pendingDone) {
        pendingDone();
        pendingDone = null;
      }

      // it has been `wait` ms since the last call and there hasn't been any
      // more, we can dispose of this function now if needed and recreate it
      // later without throwing off timing
      if (done) {
        done();
      }
    }
  }

  /**
   * Make sure our behat tests wait while the throttling is ongoing
   *
   * @param {String} pendingName
   */
  function setPending(pendingName) {
    if (!pendingDone) {
      pendingDone = pending(pendingName);
    }
  }

  return function() {
    // save last args/context so we can use it when we call fn
    args = arguments;
    context = this;

    if (timeout) {
      // within wait period

      // for leading calls we don't need to do anything, the leading call has
      // happened already

      if (trailing) {
        // for trailing calls we just set the flag so that it happens when the
        // timeout expires
        expireCall = true;
      }

      if (extendWait) {
        // reset wait so that expire() happens `wait` ms from now (used to implement debouncing)
        clearTimeout(timeout);
        setPending('throttling_extend_wait');
        timeout = setTimeout(expire, wait);
      }
    } else {
      // not within wait period
      if (leading) {
        // leading edge calls happen immediately
        fn.apply(context, args);
      } else if (trailing) {
        // only do a trailing call on the first invocation if we're not doing a
        // leading call too, otherwise we're doing unneccesary work
        expireCall = true;
      }
      setPending('throttling');
      timeout = setTimeout(expire, wait);
    }
  };
}

// expose for unit test
/* istanbul ignore next */
export const _throttleCommon =
  process.env.NODE_ENV == 'test' ? throttleCommon : null;
