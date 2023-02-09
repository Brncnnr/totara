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
  LOAD_WORKFLOW,
  MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES,
  MOD_APPROVAL__WORKFLOW_CLONE,
  OVERRIDE_ASSIGNMENTS,
  SELECTABLE_USERS,
  ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS,
  MOD_APPROVAL__EDIT_OVERRIDES,
  OVERRIDE_FOR_ASSIGNMENT,
  WORKFLOW_STAGE_MOVE_TO,
} from 'mod_approval/constants';
import { parseQueryString } from 'tui/util';
import { createMachine } from 'tui_xstate/xstate';
import * as selectors from 'mod_approval/workflow/edit/selectors';
import * as actions from 'mod_approval/workflow/edit/actions';
import * as guards from 'mod_approval/workflow/edit/guards';
import * as machineServices from 'mod_approval/workflow/edit/services';
import addOrEditOverrideMachine from 'mod_approval/workflow/add_or_edit_override/machine';
import makeState from 'mod_approval/workflow/edit/state';
import cloneMachine from '../clone/machine';
import assignRolesMachine from '../assign_roles/machine';

function workflowEditMachine({
  id,
  categoryContextId,
  workflow,
  stagesExtendedContexts,
  approverTypes,
}) {
  const services = Object.assign({}, machineServices, {
    [MOD_APPROVAL__EDIT_OVERRIDES]: addOrEditOverrideMachine({
      workflowId: workflow.id,
    }),
    [MOD_APPROVAL__WORKFLOW_CLONE]: cloneMachine({
      workflow,
      contextId: categoryContextId,
    }),
    [MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES]: assignRolesMachine({
      workflow,
    }),
  });

  const queries = {
    [SELECTABLE_USERS]: true,
    [LOAD_WORKFLOW]: true,
    [OVERRIDE_ASSIGNMENTS]: true,
    [ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS]: true,
    [OVERRIDE_FOR_ASSIGNMENT]: true,
    [WORKFLOW_STAGE_MOVE_TO]: true,
  };

  const params = parseQueryString(window.location.search);
  const state = makeState({
    id,
    categoryContextId,
    params,
    stagesExtendedContexts,
    workflow,
    approverTypes,
  });

  const options = {
    selectors,
    actions,
    services,
    guards,
    queries,
  };

  return createMachine(state, options);
}

export default workflowEditMachine;
