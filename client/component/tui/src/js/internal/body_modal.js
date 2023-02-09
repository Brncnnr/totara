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

import { getScrollbarWidth, documentHasScrollbar } from '../dom/scroll';

let bodyModalIsOpen;
let bodyModalHandles = [];
let bodyModalFlags = {};

/**
 * Set a class on the body to put it into "modal is open" mode.
 *
 * @returns {object}
 *   Object containing a "scroll" flag indicating whether the body had a
 *   scrollbar, and a close() method to call when the modal has closed to remove
 *   the class again.
 */
export function bodySetModalOpen() {
  if (!bodyModalIsOpen) {
    bodyModalFlags = _bodySetModalOpen(true);
    bodyModalIsOpen = true;
  }

  const handle = Object.assign({}, bodyModalFlags, {
    close() {
      closeBodyModal(handle);
    },
  });

  bodyModalHandles.push(handle);

  return handle;
}

/**
 * Remove the specified handle from the list of open modals on the body.
 *
 * @internal
 * @param {object} handle
 */
function closeBodyModal(handle) {
  const index = bodyModalHandles.indexOf(handle);
  if (index === -1) {
    return;
  }
  bodyModalHandles.splice(index, 1);
  if (bodyModalHandles.length == 0 && bodyModalIsOpen) {
    _bodySetModalOpen(false);
    bodyModalIsOpen = false;
  }
}

let oldBodyMarginRight = null;
let bodyMarginRightComputed = null;

/**
 * Set whether the body is in "modal open" mode or not.
 *
 * @internal
 * @param {boolean} open
 * @returns {object}
 */
function _bodySetModalOpen(open) {
  if (open) {
    let scroll = false;
    oldBodyMarginRight = document.body.style.marginRight;
    if (documentHasScrollbar()) {
      bodyMarginRightComputed = parseFloat(
        window.getComputedStyle(document.body)['margin-right']
      );

      if (isNaN(bodyMarginRightComputed)) {
        bodyMarginRightComputed = 0;
      }

      updateScrollbarOffset();

      // there's no event listener for zoom level change only, but resize does
      // fire on zoom level change, so listen to that instead
      window.addEventListener('resize', updateScrollbarOffset);

      // If there was a scrollbar on the document, make sure we always show one
      // for the modal too even if it doesn't need to scroll - otherwise you'll
      // see a white bar to the right where the document's scrollbar used to be.
      scroll = true;
    }
    document.body.classList.add('has-tui-modal');
    return { scroll };
  } else {
    window.removeEventListener('resize', updateScrollbarOffset);
    document.body.style.marginRight = oldBodyMarginRight;
    document.body.classList.remove('has-tui-modal');
  }
}

/**
 * Adjust body margin so that when we make the body not scrollable, elements
 * don't jump around the page due to it being wider due to no scrollbar.
 */
function updateScrollbarOffset() {
  const newMarginRight = bodyMarginRightComputed + getScrollbarWidth();
  document.body.style.marginRight = newMarginRight + 'px';
}
