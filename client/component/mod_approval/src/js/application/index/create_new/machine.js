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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import {
  CREATE_NEW_APPLICATION_MENU,
  SELECTABLE_APPLICANTS,
} from 'mod_approval/constants';
import { createMachine } from 'tui_xstate/xstate';
import * as actions from 'mod_approval/application/index/create_new/actions';
import * as guards from 'mod_approval/application/index/create_new/guards';
import * as selectors from 'mod_approval/application/index/create_new/selectors';
import * as services from 'mod_approval/application/index/create_new/services';
import makeState from 'mod_approval/application/index/create_new/state';

function createNewApplicationMachine({ currentUserId }) {
  const state = makeState({ currentUserId });
  const queries = {
    [CREATE_NEW_APPLICATION_MENU]: true,
    [SELECTABLE_APPLICANTS]: true,
  };

  const options = {
    selectors,
    services,
    actions,
    guards,
    queries,
  };

  return createMachine(state, options);
}

export default createNewApplicationMachine;
