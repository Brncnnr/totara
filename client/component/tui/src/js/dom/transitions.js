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

import pending from '../pending';

/**
 * Get the smallest non-zero transition duration in ms specified for the provided element, or 0 if none
 *
 * @param {Element} element
 * @returns {number} Transition duration in milliseconds. Will be 0 if there is none specified.
 */
function getSmallestTransitionDuration(element) {
  const styles = window.getComputedStyle(element);
  const durations = styles.transitionDuration
    .split(',')
    .map(getMilliseconds)
    .filter(x => x > 0);
  if (durations.length == 0) {
    return 0;
  }
  return Math.min(...durations);
}

/**
 * Get value in milliseconds from transition duration value (1.2s, 200ms), or 0 if unknown.
 *
 * @param {string} value
 * @returns {number}
 */
function getMilliseconds(value) {
  value = value.trim();
  let num = parseFloat(value);
  if (isNaN(num)) {
    return 0;
  }
  if (value.endsWith('ms')) {
    return num;
  } else if (value.endsWith('s')) {
    return num * 1000;
  } else {
    return 0;
  }
}

/**
 * Wait for transition(s) to end, with a backup timeout in case the event is not fired
 *
 * @param {Element|Element[]} element DOM element, or array of DOM elements
 * @returns {Promise}
 */
export function waitForTransitionEnd(element) {
  if (Array.isArray(element)) {
    return Promise.all(element.map(x => waitForTransitionEnd(x)));
  }
  const done = pending('transition-end');
  return new Promise(resolve => {
    function handler() {
      element.removeEventListener('transitionend', handler);
      done();
      resolve();
    }
    element.addEventListener('transitionend', handler);
    const duration = getSmallestTransitionDuration(element);
    setTimeout(handler, duration > 0 ? duration + 800 : 0);
  });
}

/**
 * Check if animations should be enabled
 *
 * This currently checks the browsers prefers-reduced-motion but can be updated to include more checks in the future
 *
 * @returns {Boolean}
 */
export function shouldAnimate() {
  return window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
}
