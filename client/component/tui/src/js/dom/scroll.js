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

import { closestEl } from './traversal';

/**
 * Measure the width of the browser scrollbar.
 *
 * Note that this will change with zoom level.
 *
 * @returns {number}
 */
export function getScrollbarWidth() {
  const scroller = document.createElement('div');
  Object.assign(scroller.style, {
    position: 'absolute',
    top: '0',
    width: '50px',
    height: '50px',
    overflow: 'scroll',
    visibility: 'hidden',
  });
  document.body.appendChild(scroller);
  const width = scroller.getBoundingClientRect().width - scroller.clientWidth;
  document.body.removeChild(scroller);
  return width;
}

/**
 * Check if the document currently has a scrollbar. Returns false for overlay scrollbars.
 *
 * @returns {boolean}
 */
export function documentHasScrollbar() {
  // checking documentElement.scrollHeight against window.innerHeight does not
  // work in all browsers as some browsers (e.g. IE 11) use overlay scrollbar for
  // the document but regular scrollbars for other elements, instead we check
  // whether the body takes up the full width of the document
  const rect = document.body.getBoundingClientRect();
  // we check rect.left + rect.right instead of rect.width, because the former
  // handles body margin better. rect.width won't handle body margin, but
  // rect.left + rect.right will as long as the margin is the same on each side
  return rect.left + rect.right < window.innerWidth;
}

/**
 * Check if the provided element is scrollable in the provided direction.
 *
 * @param {Element} el
 * @param {"x"|"y"|"xy"} direction
 * @returns {boolean}
 */
function isScrollable(el, direction) {
  if (process.env.NODE_ENV !== 'production') {
    if (direction != 'xy' && direction != 'x' && direction != 'y')
      throw new Error('Unexpected direction');
  }
  const style = getComputedStyle(el);
  let match = false;
  const checkX = direction == 'x' || direction == 'xy';
  const checkY = direction == 'y' || direction == 'xy';
  match =
    (checkX && isScrollableOverflow(style.overflowX)) ||
    (checkY && isScrollableOverflow(style.overflowY));
  return match;
}

const isScrollableOverflow = value => value === 'auto' || value === 'scroll';

/**
 * Get the closest scrollable element.
 *
 * If there are none (other than html/body) it will return null.
 *
 * @param {Element} el
 * @param {"x"|"y"|"xy"} [direction="xy"]
 * @returns {?Element}
 */
export function getClosestScrollable(el, direction = 'xy') {
  return closestEl(
    el,
    x => isScrollable(x, direction),
    x => x == document.body
  );
}
