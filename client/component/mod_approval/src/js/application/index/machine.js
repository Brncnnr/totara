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
import { createMachine } from 'tui_xstate/xstate';
import * as actions from 'mod_approval/application/index/actions';
import * as guards from 'mod_approval/application/index/guards';
import * as machineServices from 'mod_approval/application/index/services';
import * as selectors from 'mod_approval/application/index/selectors';
import makeState from 'mod_approval/application/index/state';
import createNewApplicationModalMachine from 'mod_approval/application/index/create_new/machine';
import {
  LOAD_WORKFLOW_TYPES,
  MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  CREATE_NEW_APPLICATION_MENU,
} from 'mod_approval/constants';

function applicationDashboardMachine({
  showApplicationsFromOthers,
  currentUserId,
}) {
  const services = Object.assign({}, machineServices, {
    createNewApplicationModalMachine: createNewApplicationModalMachine({
      currentUserId,
    }),
  });

  const queries = {
    [LOAD_WORKFLOW_TYPES]: true,
    [MY_APPLICATIONS]: true,
    [APPLICATIONS_FROM_OTHERS]: true,
    [CREATE_NEW_APPLICATION_MENU]: true,
  };

  const state = makeState({
    showApplicationsFromOthers,
    currentUserId,
  });

  const options = {
    actions,
    guards,
    selectors,
    services,
    queries,
  };

  return createMachine(state, options);
}

export default applicationDashboardMachine;
