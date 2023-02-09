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
 * @module mod_approval
 */
import { APPLICATIONS_FROM_OTHERS } from 'mod_approval/constants';
import { createMachine } from 'tui_xstate/xstate';
import * as actions from './actions';
import * as guards from './guards';
import * as services from './services';
import * as selectors from './selectors';
import makeState from './state';

const queries = {
  [APPLICATIONS_FROM_OTHERS]: true,
};

/**
 * @return {import('xstate').StateMachine}
 */
function pendingMachine() {
  const state = makeState();
  const options = {
    actions,
    guards,
    selectors,
    services,
    queries,
  };

  return createMachine(state, options);
}

export default pendingMachine;
