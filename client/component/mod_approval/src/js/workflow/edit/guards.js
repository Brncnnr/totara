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

import { RoleAssignTarget, WorkflowState } from 'mod_approval/constants';
import {
  getActiveVariables,
  getActiveStageId,
  getToEditStageId,
  getWorkflowStages,
} from 'mod_approval/workflow/edit/selectors';

import {
  hasApprovalLevels,
  hasFormViews,
} from 'mod_approval/item_selectors/workflow_stage';
import initialActiveSubsection from './state/initial_active_subsection';
import { get } from 'tui/util';
export { hasNotify } from 'mod_approval/common/guards';

export const navigateToOverrides = context => context.loadOverides;
export const navigateToApprovals = context =>
  initialActiveSubsection({ context }) === WorkflowState.APPROVALS;
export const navigateToForm = context =>
  initialActiveSubsection({ context }) === WorkflowState.FORM;
export const navigateToNotifications = context =>
  initialActiveSubsection({ context }) === WorkflowState.NOTIFICATIONS;

export const noActiveVariables = context => !getActiveVariables(context);

export const hasAddedApprovalLevel = (context, event) =>
  Boolean(
    get(event, [
      'data',
      'data',
      'mod_approval_workflow_stage_add_approval_level',
    ])
  );

export const mutationQueued = context => context.mutationQueue.length > 0;

export const deleteApprovalLevelSuccess = (context, event) =>
  Boolean(
    get(event, [
      'data',
      'data',
      'mod_approval_workflow_stage_delete_approval_level',
    ])
  );

export const shouldAppend = context => context.appendUsers;

export const hasUpdatedStageName = (context, event) =>
  get(event, ['data', 'data', 'mod_approval_workflow_stage_edit']);

export const hasCloneData = (context, event) =>
  event.data && Boolean(event.data.cloneData);
export const cancelled = (context, event) =>
  !event.data || !event.data.approversVariables;
export const hasAddedOverride = (context, event) => event.data.hasAddedOverride;
export const assignRolesInApprovalOverride = (context, event) =>
  event.data && event.data.target === RoleAssignTarget.APPROVAL_OVERRIDE;
export const assignRolesInWorkflow = (context, event) =>
  event.data && event.data.target === RoleAssignTarget.WORKFLOW;
export const existingOverrideForAssignment = (context, event) =>
  get(event, ['data', 'data', 'mod_approval_override_for_assignment_type']);

export const hasPendingStageSwitch = context =>
  context.pendingSwitchToWorkflowStageId != null;

export const editingActiveStage = context =>
  getToEditStageId(context) === getActiveStageId(context);

export const hasApprovalsStage = (context, { stageId }) => {
  const stages = getWorkflowStages(context);
  const toNavStage = stages.find(stage => stage.id === stageId);
  if (toNavStage) {
    return hasApprovalLevels(toNavStage);
  }

  return false;
};

export const hasFormViewsStage = (context, { stageId }) => {
  const stages = getWorkflowStages(context);
  const toNavStage = stages.find(stage => stage.id === stageId);
  if (toNavStage) {
    return hasFormViews(toNavStage);
  }

  return false;
};

export const showingSection = (context, { visible }) => visible === true;
export const hidingSection = (context, { visible }) => visible === false;
