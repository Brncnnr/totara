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
import { createSelector } from 'tui_xstate/util';
export * from 'mod_approval/graphql_selectors/load_workflow';
export * from 'mod_approval/graphql_selectors/selectable_users';
export * from 'mod_approval/graphql_selectors/ancestor_assignment_approval_levels';

export const getUserSearchVariables = context => context.userSearchVariables;
export const getUsers = context => context.users;
export const getOverrideAssignment = context =>
  context.overrideAssignment || {};

export const getApprovalLevels = createSelector(
  getOverrideAssignment,
  overrideAssignment => overrideAssignment.assignment_approval_levels
);

export const getApproversByLevelId = createSelector(
  getApprovalLevels,
  approvalLevels =>
    approvalLevels.reduce((acc, level) => {
      acc[level.approval_level.id] = level.approvers;
      return acc;
    }, {})
);

export const getAssignment = createSelector(
  getOverrideAssignment,
  overrideAssignment => overrideAssignment.assignment
);

export const getAssignmentId = createSelector(
  getAssignment,
  assignment => assignment.id
);

export const getWorkflowStageId = context => context.workflowStageId;

export const getFormValues = context => context.formValues;
export const getActiveLevelId = context => context.activeLevelId;
export const getActiveApproverType = createSelector(
  getFormValues,
  getActiveLevelId,
  (formValues, activeLevelId) => formValues.approverTypes[activeLevelId]
);

export const getActiveApprovers = createSelector(
  getFormValues,
  getActiveApproverType,
  getActiveLevelId,
  (formValues, approverType, activeLevelId) => {
    return formValues[approverType]
      ? formValues[approverType][activeLevelId] || []
      : [];
  }
);

export const getApproversById = createSelector(
  getActiveApprovers,
  approvers => {
    return approvers.reduce((acc, approver) => {
      acc[approver.id] = true;
      return acc;
    }, {});
  }
);

export const getFormApproverTypes = createSelector(
  getFormValues,
  formValues => formValues.approverTypes
);

export const getDisabledIds = context => context.disabledIds;
export const getSelectedAssignmentType = context =>
  context.selectedAssignmentType;

export const getActiveDisabledIds = createSelector(
  getSelectedAssignmentType,
  getDisabledIds,
  (assignmentType, disabledIds) => disabledIds[assignmentType] || []
);
