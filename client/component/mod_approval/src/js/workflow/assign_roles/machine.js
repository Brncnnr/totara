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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */

import { createMachine } from 'tui_xstate/xstate';
import * as guards from './guards';
import * as selectors from './selectors';
import makeState from './state';

export default function assignRolesMachine({ workflow } = {}) {
  const options = {
    guards,
    selectors,
  };

  const state = makeState({ workflow });

  return createMachine(state, options);
}
