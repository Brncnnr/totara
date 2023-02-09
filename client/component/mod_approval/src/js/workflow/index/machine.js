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
import { createMachine } from 'tui_xstate/xstate';
import {
  MANAGEABLE_WORKFLOWS,
  MOD_APPROVAL__WORKFLOW_CLONE,
  MOD_APPROVAL__WORKFLOW_CREATE,
} from 'mod_approval/constants';
import * as actions from 'mod_approval/workflow/index/actions';
import * as guards from 'mod_approval/workflow/index/guards';
import * as selectors from 'mod_approval/workflow/index/selectors';
import * as machineServices from 'mod_approval/workflow/index/services';
import makeState from 'mod_approval/workflow/index/state';
import cloneMachine from '../clone/machine';
import workflowCreateMachine from '../create/machine';

function workflowDashboardMachine({ categoryContextId, workflowTypeOptions }) {
  const services = Object.assign({}, machineServices, {
    [MOD_APPROVAL__WORKFLOW_CLONE]: cloneMachine({
      contextId: categoryContextId,
    }),
    [MOD_APPROVAL__WORKFLOW_CREATE]: workflowCreateMachine({
      workflowTypeOptions,
    }),
  });

  const queries = {
    [MANAGEABLE_WORKFLOWS]: true,
  };

  const options = {
    actions,
    guards,
    selectors,
    services,
    queries,
  };

  const state = makeState({ categoryContextId });

  return createMachine(state, options);
}

export default workflowDashboardMachine;
