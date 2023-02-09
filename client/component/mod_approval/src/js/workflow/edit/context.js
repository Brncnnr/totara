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
  LOAD_WORKFLOW,
  WorkflowState,
  SELECTABLE_USERS,
  WORKFLOW_STAGE_MOVE_TO,
} from 'mod_approval/constants';

import { userSelectors } from 'mod_approval/graphql_selectors';

export default function makeContext({
  categoryContextId,
  stagesExtendedContexts = {},
  workflow,
  params = {},
  approverTypes = {},
}) {
  const firstStage = workflow && workflow.latest_version.stages[0];
  return {
    activeWorkflowStageId: firstStage ? firstStage.id : null,
    activeApprovalLevelId: null,
    activeApproverTagList: null,
    appendUsers: false,
    approvalLevelTagListData: {},
    approvalLevelLoading: false,
    approvalLevelDeleting: false,
    approvalModalSubtitle: null,
    approvalModalTitle: 'non-empty-placeholder',
    approverTypes,
    approvers: [],
    approversInherited: null,
    assignRolesTargetAssignment: null,
    categoryContextId,
    loadOverides: Boolean(params[WorkflowState.OVERRIDES]),
    mutationQueue: [],
    pendingSwitchToWorkflowStageId: null,
    savedFormviewVisibility: {},
    sectionVisibilityUpdates: {},
    selectedApproverTypes: {},
    stagesSectionVisibility: {},
    stagesExtendedContexts,
    toEditApprovalLevelId: null,
    toEditStageId: null,
    toEditInteraction: null,
    userSearchVariables: {},
    users: {},
    // Holds query variables for each stage held in an overridesStageKey
    variables: {},
    toArchiveOverrides: null,
    notify: null,
    notifyType: null,
    [SELECTABLE_USERS]: userSelectors.create(),
    [LOAD_WORKFLOW]: {
      mod_approval_load_workflow: { workflow },
    },

    [WORKFLOW_STAGE_MOVE_TO]: {
      mod_approval_workflow_stage_move_to: {
        options: [],
      },
    },
  };
}
