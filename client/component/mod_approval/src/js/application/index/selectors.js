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
export * from 'mod_approval/graphql_selectors/create_new_application_menu';
export * from 'mod_approval/graphql_selectors/selectable_applicants';
export * from 'mod_approval/graphql_selectors/others_applications';
export * from 'mod_approval/graphql_selectors/load_workflow_types';
import { getWorkflowTypes } from 'mod_approval/graphql_selectors/load_workflow_types';
import { getMyApplications } from 'mod_approval/graphql_selectors/my_applications';
import { CREATE_NEW_APPLICATION_MENU } from 'mod_approval/constants';

export const getJobAssignments = context =>
  context[CREATE_NEW_APPLICATION_MENU]
    .mod_approval_create_new_application_menu || [];

export const getJobAssignmentsWorkflowTypes = createSelector(
  getJobAssignments,
  jobAssignments => jobAssignments.map(({ workflow_type }) => workflow_type)
);

export const hasWorkflowTypes = createSelector(
  getWorkflowTypes,
  workflowTypes => workflowTypes.length > 0
);

export const getOwnAvailableWorkflowTypes = createSelector(
  getWorkflowTypes,
  getJobAssignmentsWorkflowTypes,
  (workflowTypes, jobAssignmentsWorkflowTypes) =>
    workflowTypes.filter(workflowType =>
      jobAssignmentsWorkflowTypes.includes(workflowType.label)
    )
);

export const getOwnDefaultWorkflowType = createSelector(
  getOwnAvailableWorkflowTypes,
  ownAvailableWorkflowTypes => ownAvailableWorkflowTypes[0]
);

export const getIsLearnerEmpty = context =>
  !context.showApplicationsFromOthers &&
  getMyApplications(context).length === 0;
