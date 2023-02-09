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
 * @module mod_approval
 */
import { createSelector } from 'tui_xstate/util';
import { LOAD_WORKFLOW, WorkflowStatus } from '../constants';

export const getWorkflow = context =>
  context[LOAD_WORKFLOW].mod_approval_load_workflow.workflow;
export const getInteractor = createSelector(
  getWorkflow,
  workflow => workflow && workflow.interactor
);

// WorkflowId
export const getWorkflowId = createSelector(
  getWorkflow,
  workflow => workflow && workflow.id
);

// WorkflowContextId
export const getWorkflowContextId = createSelector(
  getWorkflow,
  workflow => workflow && workflow.context_id
);
// WorkflowName
export const getWorkflowName = createSelector(
  getWorkflow,
  workflow => workflow.name
);
// WorkflowDescription
export const getWorkflowDescription = createSelector(
  getWorkflow,
  workflow => workflow.description
);
// WorkflowIdNumber
export const getWorkflowIdNumber = createSelector(
  getWorkflow,
  workflow => workflow.id_number
);
// WorkflowType
export const getWorkflowType = createSelector(
  getWorkflow,
  workflow => workflow.workflow_type
);
export const getWorkflowTypeName = createSelector(
  getWorkflowType,
  workflowType => workflowType.name
);
export const getWorkflowTypeId = createSelector(
  getWorkflowType,
  workflowType => workflowType.id
);
// Default Assignment
export const getDefaultAssignment = createSelector(
  getWorkflow,
  workflow => workflow.default_assignment
);

export const getDefaultAssignmentId = createSelector(
  getDefaultAssignment,
  defaultAssignment => defaultAssignment.id
);
export const getAssignmentType = createSelector(
  getDefaultAssignment,
  assignment => assignment.assignment_type_label
);

export const getAssignedTo = createSelector(
  getDefaultAssignment,
  assignment => assignment.assigned_to
);

export const getAssignedToName = createSelector(
  getAssignedTo,
  assignedTo => assignedTo.fullname
);

// Version Status
export const getLatestVersion = createSelector(
  getWorkflow,
  workflow => workflow && workflow.latest_version
);

export const getLatestVersionId = createSelector(
  getLatestVersion,
  latestVersion => latestVersion.id
);

export const getWorkflowStatus = createSelector(
  getLatestVersion,
  latestVersion => latestVersion.status_label
);
export const getWorkflowStatusInt = createSelector(
  getLatestVersion,
  latestVersion => latestVersion.status
);
export const getWorkflowIsActive = createSelector(
  getWorkflowStatusInt,
  status => status === WorkflowStatus.ACTIVE
);
export const getWorkflowIsDraft = createSelector(
  getWorkflowStatusInt,
  status => status === WorkflowStatus.DRAFT
);
export const getWorkflowStages = createSelector(
  getLatestVersion,
  latestVersion => latestVersion.stages
);
export const getFirstWorkflowStage = createSelector(
  getWorkflowStages,
  stages => stages[0]
);

export const getFormVersion = createSelector(
  getLatestVersion,
  latestVersion => latestVersion.form_version
);
export const getFormSchema = createSelector(getFormVersion, formVersion =>
  JSON.parse(formVersion.json_schema)
);
