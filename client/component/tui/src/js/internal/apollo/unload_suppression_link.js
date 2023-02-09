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

import { ApolloLink, Observable } from 'apollo-link';

/**
 * Suppress response when page is unloaded to avoid error showing briefly.
 */
export function createUnloadSuppressionLink() {
  // Firefox aborts requests on page unload. Somewhere between the
  // "beforeunload" event and the "pagehide"/"unload" event.
  // Unfortunately, adding a handler for the beforeunload event makes the page
  // ineligible for the back/forward cache, so we only attach it in Firefox
  // as other browsers do not have this bug.
  if (
    typeof navigator === 'undefined' ||
    !/firefox/i.test(navigator.userAgent)
  ) {
    return new ApolloLink((operation, forward) => forward(operation));
  }

  let unloading = false;
  let resumeQueue = [];

  window.addEventListener('beforeunload', e => {
    unloading = true;
    setTimeout(() => {
      if (e.defaultPrevented || e.returnValue) {
        // Not leaving after all. Keep running tasks!
        unloading = false;
        resumeQueue.forEach(x => Promise.resolve().then(x));
        resumeQueue = [];
      }
    }, 0);
  });

  const exec = fn => {
    if (!unloading) {
      fn();
    } else {
      // Page is unloading, don't pass through the failure (or success).
      // Stick it in a queue though, so if the unload is cancelled we can
      // continue where we left off.
      resumeQueue.push(fn);
    }
  };

  return new ApolloLink((operation, forward) => {
    return new Observable(observer => {
      forward(operation).subscribe({
        next(value) {
          exec(() => observer.next(value));
        },
        error(e) {
          exec(() => observer.error(e));
        },
        complete() {
          exec(() => observer.complete());
        },
      });
    });
  });
}
