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

let counter = 1;

/**
 * Generates an incrementing unique ID.
 *
 * This number is only unique within a page, and is not unique across page
 * loads.
 *
 * @return {number}
 */
export function uniqueId() {
  return counter++;
}

/**
 * Get a result from a value.
 *
 * If value is a function it will be called to obtain the result, otherwise
 * value will be used as-is.
 *
 * @param {*} value
 * @return {*}
 */
export function result(value) {
  if (value instanceof Function) {
    return value();
  }
  return value;
}

export function createNewEvent(eventName) {
  let event;
  if (typeof Event === 'function') {
    event = new Event(eventName);
  } else {
    event = document.createEvent('Event');
    event.initEvent(eventName, true, true);
  }
  return event;
}
