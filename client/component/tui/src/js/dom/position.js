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

import { Point, Rect, Size } from 'tui/geometry';
import { closestEl } from './traversal';

/**
 * @typedef {{ top: number, right: number, bottom: number, left: number }} Spacing
 */

/**
 * Get the position of an element relative to the document.
 *
 * @param {HTMLElement} element
 * @returns {Point}
 */
export function getDocumentPosition(element) {
  let x = 0;
  let y = 0;
  let el = element;

  while (el) {
    x += el.offsetLeft || 0;
    y += el.offsetTop || 0;
    el = el.offsetParent;
  }

  el = element;

  // account for scrolling of elements
  // do not account for document scrolling
  // - in most browsers, it is documentElement which scrolls
  // - in pre-Chromium Edge, it is document.body which scrolls
  while (el && el !== document.documentElement && el !== document.body) {
    x -= el.scrollLeft || 0;
    y -= el.scrollTop || 0;
    el = el.parentNode;
  }

  return new Point(x, y);
}

/**
 * Get a Rect representing the element's size and position relative to its offset parent.
 *
 * @param {HTMLElement} el
 * @returns {Rect}
 */
export function getOffsetRect(el) {
  return new Rect(el.offsetLeft, el.offsetTop, el.offsetWidth, el.offsetHeight);
}

/**
 * Get a Rect representing the currently visbile portion of the document.
 *
 * @returns {Rect}
 */
export function getViewportRect() {
  const html = document.documentElement;
  return new Rect(
    window.pageXOffset,
    window.pageYOffset,
    html.clientWidth,
    html.clientHeight
  );
}

/**
 * Get box model information.
 *
 * @param {Element} el
 * @param {Object} [options]
 * @param {boolean} [options.transformed=false] Get the position post-transform? (default false)
 * @param {boolean} [options.viewport=false] Get the position relative to the viewport? (default false)
 * @returns {{ marginBox: Rect, borderBox: Rect, paddingBox: Rect, margin: Spacing, padding: Spacing }}
 */
export function getBox(el, { transformed = false, viewport = false } = {}) {
  let position;
  let size;
  if (transformed) {
    // DOMRect x,y unsupported in IE11, but left,top are the same values in most circumstances
    const clientRect = el.getBoundingClientRect();
    position = new Point(clientRect.left, clientRect.top);
    if (!viewport) {
      position = position.add(getViewportRect().getPosition());
    }
    size = new Size(clientRect.width, clientRect.height);
  } else {
    position = getDocumentPosition(el);
    if (viewport) {
      position = position.sub(getViewportRect().getPosition());
    }
    size = new Size(el.offsetWidth, el.offsetHeight);
  }
  const borderBox = new Rect(position.x, position.y, size.width, size.height);
  const style = getComputedStyle(el);

  const margin = {
    top: parseSize(style.marginTop),
    right: parseSize(style.marginRight),
    bottom: parseSize(style.marginBottom),
    left: parseSize(style.marginLeft),
  };

  const padding = {
    top: parseSize(style.paddingTop),
    right: parseSize(style.paddingRight),
    bottom: parseSize(style.paddingBottom),
    left: parseSize(style.paddingLeft),
  };

  return {
    marginBox: alterRect(borderBox, margin, 1),
    borderBox,
    paddingBox: alterRect(borderBox, padding, -1),
    margin,
    padding,
  };
}

/**
 * Get bounding client rect.
 *
 * @param {Element} el
 * @returns {Rect}
 */
export function getBoundingClientRect(el) {
  const clientRect = el.getBoundingClientRect();
  return new Rect(
    clientRect.left,
    clientRect.top,
    clientRect.width,
    clientRect.height
  );
}

/**
 * Alter Rect by adding or removing spacing on each side.
 *
 * @param {Rect} rect
 * @param {{ top: number, right: number, bottom: number, left: number }} spacing
 * @param {number} multiplier 1 - expand, -1 - shrink
 */
function alterRect(rect, spacing, multiplier) {
  return Rect.fromPositions({
    top: rect.top - spacing.top * multiplier,
    right: rect.right + spacing.right * multiplier,
    bottom: rect.bottom + spacing.bottom * multiplier,
    left: rect.left - spacing.left * multiplier,
  });
}

/**
 * Parse CSS size value, e.g. from marginLeft.
 *
 * @param {string} str
 * @returns {number}
 */
function parseSize(str) {
  // computed styles are in px when visible, original values otherwise.
  // if it's not visible, treat everything as 0
  return str.slice(-2) == 'px' ? Number(str.slice(0, -2)) : 0;
}

/**
 * Get containing block for element.
 *
 * https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_Block
 *
 * @param {Element} el
 * @param {{ position: 'fixed' }} options
 * @returns {{ el: ?Element, rect: Rect }}
 */
export function getContainingBlockInfo(el, { position }) {
  if (position != 'fixed') {
    throw new Error('Unsupported position: ' + position);
  }

  const blockEl = closestEl(
    el.parentNode,
    x => {
      const style = getComputedStyle(x);
      // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_Block#Identifying_the_containing_block
      if (
        !isNoneTransform(style.transform) ||
        style.perspective != 'none' ||
        style.contain == 'paint'
      ) {
        return true;
      }
      if (style.willChange) {
        const willChange = style.willChange.split(', ');
        if (
          willChange.includes('transform') ||
          willChange.includes('perspective')
        ) {
          return true;
        }
      }
      return false;
    },
    x => x == document.body
  );

  if (blockEl) {
    return {
      el: blockEl,
      rect: getBox(blockEl).paddingBox,
    };
  } else {
    return {
      el: null,
      rect: getViewportRect(),
    };
  }
}

function isNoneTransform(transform) {
  return (
    transform == 'none' ||
    // the computed value for "none" in IE, apparently
    transform == 'matrix(1, 0, 0, 1, 0, 0)'
  );
}

/**
 * Get the coords of the selected text. If no text is selected returns the caret position.
 *
 * @returns {Rect}
 */
export function getSelectionClientRect() {
  const selection = window.getSelection();
  const range = selection.getRangeAt(0).cloneRange();
  range.collapse(true);
  const rects = range.getClientRects();
  let rect = rects[0];

  if (rect && rect.x !== 0 && rect.y !== 0) {
    return Rect(rect.x, rect.y, rect.width, rect.height);
  }

  // insert a temporary element
  const span = document.createElement('span');
  span.appendChild(document.createTextNode('\u200b'));
  range.insertNode(span);

  rect = span.getClientRects()[0];
  const spanParent = span.parentNode;
  spanParent.removeChild(span);
  spanParent.normalize();

  return Rect(rect.x, rect.y, rect.width, rect.height);
}
