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
import {
  SELECTABLE_USERS,
  ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS,
  ASSIGNMENT_IDENTIFIERS,
} from 'mod_approval/constants';
import { createMachine } from 'tui_xstate/xstate';
import * as selectors from './selectors';
import * as actions from './actions';
import * as guards from './guards';
import * as services from './services';
import makeState from './state';

function addOrEditOverrideMachine({ workflowId }) {
  const queries = {
    [SELECTABLE_USERS]: true,
    [ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS]: true,
    [ASSIGNMENT_IDENTIFIERS]: true,
  };

  const state = makeState({ workflowId });
  const options = {
    selectors,
    actions,
    services,
    guards,
    queries,
  };

  return createMachine(state, options);
}

export default addOrEditOverrideMachine;
