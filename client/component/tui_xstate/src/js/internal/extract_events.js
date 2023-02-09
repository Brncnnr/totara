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
 * @author Simon Tegg <simon.tegg@totaralearning.com
 * @module tui_xstate
 */

import traverse from 'traverse';

/**
 * extractEvents collects all events used in a machine.
 *
 * The plugin registers all of the machine's events on this.x.e,
 * and available under the computed property "$e".
 *
 * This avoids string typos 'EVNT_NAME' for 'EVENT_NAME'.
 * $e.EVNT_NAME will throw an undefined error while 'EVNT_NAME' will mysteriously not work.
 *
 * @param {StateNode} state
 * @return {Object} events - an object with { EVENT_NAME: 'EVENT_NAME', ... }
 */

// TODO state.events ? https://xstate.js.org/api/classes/statenode.html#events
export default function extractEvents(state) {
  const events = {};
  traverse(state).forEach(function() {
    if (this.parent && this.parent.key === 'on') {
      events[this.key] = this.key;
    }
  });

  return events;
}
