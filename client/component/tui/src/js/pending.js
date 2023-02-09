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

import { pull } from './util';

let pendingList = [];

/**
 * Register an asynchronous task as pending.
 *
 * The primary purpose of this is enabling behat tests.
 *
 * Code running in microtasks (e.g. Promises) does not need to be registered as
 * that does not involve an event loop break.
 *
 * The preferred approach should be to push this down to the lowest level
 * possible so most code never needs to call this - for example, an AJAX wrapper
 * should call this so users don't have to.
 *
 * Needed for:
 *   * waiting for the result of a direct fetch() call
 *   * waiting for an event before continuing (load, transitionend, etc)
 *   * setTimeout
 *
 * Not needed for:
 *   * GraphQL requests
 *   * Promise callbacks
 *   * async/await
 *
 * Returns a function that should be called when the code is complete.
 *
 * @param {string=} key Optional identifier for this task (used for debugging, does not have to be unique)
 * @return {function}
 *   Function to call when the code is complete.
 *   Calling this function multiple times has no effect, only the first call
 *   will be registered.
 */
export default function pending(key = 'pending') {
  pendingList.push(key);
  let called = false;
  return () => {
    if (called) return;
    called = true;
    pull(pendingList, key);
  };
}

/* istanbul ignore else */
if (typeof window !== 'undefined') {
  if (!window.testbridge) window.testbridge = {};
  if (!window.testbridge.pending) window.testbridge.pending = [];
  pendingList = window.testbridge.pending;
}
