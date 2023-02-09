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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui_xstate
 */

import { assign, interpret as xstateInterpret } from 'xstate';
import { produce } from 'tui/immutable';
import pending from 'tui/pending';

export * from 'xstate';

/** @type {import('xstate').interpret} */
export function interpret(machine, options) {
  return xstateInterpret(
    machine,
    Object.assign({}, options, {
      clock:
        // wrap any custom clock with pending calls, otherwise use the
        // pre-wrapped defaultClock
        options && options.clock
          ? pendingWrappedClock(options.clock)
          : defaultClock,
    })
  );
}

function pendingWrappedClock(clock) {
  const clockPending = new Map();
  return {
    setTimeout(fn, ms) {
      const id = clock.setTimeout(() => {
        const done = clockPending.get(id);
        if (done) {
          done();
          clockPending.delete(id);
        }
        fn();
      }, ms);
      clockPending.set(id, pending());
      return id;
    },
    clearTimeout(id) {
      const done = clockPending.get(id);
      if (done) {
        done();
        clockPending.delete(id);
      }
      clock.clearTimeout(id);
    },
  };
}

const defaultClock = pendingWrappedClock({
  setTimeout: (fn, ms) => setTimeout(fn, ms),
  clearTimeout: id => clearTimeout(id),
});

/**
 * Update the current context of the machine using the shimmer produce() helper.
 *
 * @param {(contextDraft: object, event: object, meta: object) => void} recipe
 */
export function shimmerAssign(recipe) {
  return assign((context, event, meta) => {
    return produce(context, draft => recipe(draft, event, meta));
  });
}
