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
 * @author Simon Tegg <simon.teggfe@totaralearning.com>
 * @module mod_approval
 */

import { createSelector } from 'tui_xstate/util';
import { getSelectableApplicants } from 'mod_approval/graphql_selectors/selectable_applicants';
export * from 'mod_approval/graphql_selectors/create_new_application_menu';
export * from 'mod_approval/graphql_selectors/selectable_applicants';

export const getSelectedUser = context => context.selectedUser;
export const getCurrentUserId = context => context.currentUserId;
export const getAvailableUsers = createSelector(
  getSelectableApplicants,
  getCurrentUserId,
  (users, currentUserId) => users.filter(user => user.id !== currentUserId)
);
export const getSelectedJobAssignment = context =>
  context.selectedJobAssignment;
export const getSelectedJobAssignmentId = createSelector(
  getSelectedJobAssignment,
  jobAssignment => (jobAssignment ? jobAssignment.job_assignment_id : '')
);

export const getWorkflowTypeOptions = context => context.workflowTypeOptions;

export const getSelectedWorkflowType = context => context.selectedWorkflowType;
export const getSelectedWorkflowTypeId = createSelector(
  getSelectedWorkflowType,
  workflowType => (workflowType ? workflowType.id : '')
);

export const getVariables = context => context.variables;
export const getInput = createSelector(
  getVariables,
  variables => variables.input
);
export const getFilters = createSelector(getInput, input => input.filters);
export const getNameSearch = createSelector(
  getFilters,
  filters => filters.fullname
);

export const getForYourself = context => context.forYourself;
