/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */
let stack = {};
let listenerLoaded = false;

// For ease of unit testing
export let testUtils = {};

/**
 * A global keydown handler
 *
 * @param {KeyboardEvent} event The keyboard event as sent by the browser
 */
function listener(event) {
  let fixedKey = event.key;
  let propagationStopped = false;
  function stopPropagation() {
    event.stopPropagation();
    propagationStopped = true;
  }

  fixedKey = fixKey(fixedKey);

  if (stack[fixedKey] && stack[fixedKey].length > 0) {
    for (let i = stack[fixedKey].length - 1; i >= 0; i--) {
      const e = {
        srcEvent: event,
        key: fixedKey,
        stopPropagation,
        preventDefault: event.preventDefault.bind(event),
      };

      stack[fixedKey][i](e);

      if (propagationStopped) {
        break;
      }
    }
  }
}

/**
 * Adds a callback to the document that stacks nicely with other callbacks
 *
 * @param {String} key The key to be listened to
 * @param {Function} callback The callback that handles the event
 */
export function addListener(key, callback) {
  if (typeof callback != 'function') {
    console.error(
      'Supplied callback to keyboard_stack.addListener is not a function'
    );
  }

  key = fixKey(key);

  if (!stack[key]) {
    stack[key] = [];
  }

  if (!listenerLoaded) {
    document.addEventListener('keydown', listener);
    listenerLoaded = true;
  }

  stack[key].push(callback);
}

/**
 * Removes a callback from the keydown listener
 *
 * @param {String} key The key to remove
 * @param {Function} callback The function to remove from the stack
 */
export function removeListener(key, callback) {
  key = fixKey(key);

  if (!stack[key]) {
    return;
  }

  let callbackID = stack[key].indexOf(callback);
  if (callbackID == -1) {
    return;
  }

  stack[key].splice(callbackID, 1);

  if (stack[key].length === 0) {
    delete stack[key];
  }
}

/**
 * Standardises the key string (yes I'm looking at you IE11)
 *
 * @param {String} key The key to fix up
 * @returns {String} A standardised key string
 */
function fixKey(key) {
  switch (key) {
    case 'Esc':
      return 'Escape';
    default:
      return key;
  }
}

// Unit testing functionality
if (process.env.NODE_ENV == 'test') {
  testUtils.getStack = () => stack;

  testUtils.reset = () => {
    stack = {};
  };
}
