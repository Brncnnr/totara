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
// MACHINES
export const MOD_APPROVAL__APPLICATION = 'MOD_APPROVAL__APPLICATION';
export const MOD_APPROVAL__APPLICATION_VIEW = 'MOD_APPROVAL__APPLICATION_VIEW';
export const MOD_APPROVAL__DASHBOARD = 'MOD_APPROVAL__DASHBOARD';
export const MOD_APPROVAL__DASHBOARD_TABLE = 'MOD_APPROVAL__DASHBOARD_TABLE';
export const MOD_APPROVAL__WORKFLOW_DASHBOARD =
  'MOD_APPROVAL__WORKFLOW_DASHBOARD';
export const MOD_APPROVAL__WORKFLOW_EDIT = 'MOD_APPROVAL__WORKFLOW_EDIT';
export const MOD_APPROVAL__WORKFLOW_CLONE = 'MOD_APPROVAL__WORKFLOW_CLONE';
export const MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES =
  'MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES';
export const MOD_APPROVAL__WORKFLOW_CREATE = 'MOD_APPROVAL__WORKFLOW_CREATE';
export const MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL =
  'MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL';
export const MOD_APPROVAL__PENDING = 'MOD_APPROVAL__PENDING';
export const MOD_APPROVAL__EDIT_OVERRIDES = 'MOD_APPROVAL__EDIT_OVERRIDES';
export const MOD_APPROVAL__SCROLL_MACHINE = 'MOD_APPROVAL__SCROLL_MACHINE';

// PARAMS
export const TAB = 'tab';
export const OTHERS = 'others';
export const MY = 'my';

export const SUB_SECTION = 'sub_section';

/** @enum {string} */
export const WorkflowState = {
  APPROVALS: 'approvals',
  INTERACTIONS: 'interactions',
  FORM: 'form',
  NOTIFICATIONS: 'notifications',
  OVERRIDES: 'overrides',
};

/** @enum {string} */
export const WorkflowStageFeatureType = {
  FORMVIEWS: 'FORMVIEWS',
  APPROVAL_LEVELS: 'APPROVAL_LEVELS',
  INTERACTIONS: 'INTERACTIONS',
};

// QUERY IDS
export const ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS =
  'ancestorAssignmentApprovalLevels';
export const APPLICATION_APPROVERS = 'applicationApprovers';
export const APPLICATION_FORM_SCHEMA = 'applicationFormSchema';
export const APPLICATIONS_FROM_OTHERS = 'applicationsFromOthers';
export const ASSIGNMENT_IDENTIFIERS = 'assignmentIdentifiers';
export const CREATE_NEW_APPLICATION_MENU = 'createNewApplicationMenu';
export const GET_ACTIVE_FORMS = 'getActiveForms';
export const LOAD_APPLICATION = 'loadApplication';
export const LOAD_APPLICATION_ACTIVITIES = 'loadApplicationActivities';
export const LOAD_WORKFLOW = 'loadWorkflow';
export const LOAD_WORKFLOW_TYPES = 'loadWorkflowTypes';
export const OVERRIDE_FOR_ASSIGNMENT = 'overrideForAssignment';
export const MANAGEABLE_WORKFLOWS = 'manageableWorkflows';
export const MY_APPLICATIONS = 'myApplications';
export const OVERRIDE_ASSIGNMENTS = 'overrideAssignments';
export const SELECTABLE_APPLICANTS = 'selectableApplicants';
export const SELECTABLE_USERS = 'selectableUsers';
export const USER_OWN_PROFILE = 'userOwnProfile';
export const WORKFLOW_ID_NUMBER_IS_UNIQUE = 'workflowIdNumberIsUnique';
export const WORKFLOW_STAGE_MOVE_TO = 'workflowStageMoveTo';

/** @enum {string} */
export const ApplicationAction = {
  APPROVE: 'APPROVE',
  REJECT: 'REJECT',
  WITHDRAW_BEFORE_SUBMISSION: 'WITHDRAW_BEFORE_SUBMISSION',
  WITHDRAW_IN_APPROVALS: 'WITHDRAW_IN_APPROVALS',
  RESET_APPROVALS: 'RESET_APPROVALS',
  SUBMIT: 'SUBMIT',
};

/** @enum {string} */
export const Transition = {
  NEXT: 'NEXT',
  PREVIOUS: 'PREVIOUS',
  RESET: 'RESET',
};

/** @enum {string} */
export const ApplicationTableColumn = {
  APPLICANT_NAME: 'APPLICANT_NAME',
  SUBMITTED: 'SUBMITTED',
  WORKFLOW_TYPE_NAME: 'WORKFLOW_TYPE_NAME',
  ID_NUMBER: 'ID_NUMBER',
  TITLE: 'TITLE',
};

/** @enum {string} */
export const OverallProgressState = {
  DRAFT: 'DRAFT',
  IN_PROGRESS: 'IN_PROGRESS',
  FINISHED: 'FINISHED',
  REJECTED: 'REJECTED',
  WITHDRAWN: 'WITHDRAWN',
};

/** @enum {string} */
export const YourProgressState = {
  ALL: 'ALL',
  PENDING: 'PENDING',
  APPROVED: 'APPROVED',
  REJECTED: 'REJECTED',
  NA: 'NA',
};

/**
 * @typedef {"DRAFT"|"ACTIVE"|"ARCHIVED"} StatusEnum
 */

/** @enum {number} */
export const WorkflowStatus = {
  DRAFT: 1,
  ACTIVE: 2,
  ARCHIVED: 3,
};

/** @enum {string} */
export const WorkflowsSortOption = {
  NAME: 'NAME',
  UPDATED: 'UPDATED',

  // workflow.graphqls also lists the following
  // but these are not specified in the design
  // ID_NUMBER: 'ID_NUMBER',
  // STATUS: 'STATUS'
};

/** @enum {string} */
export const ApproverType = {
  RELATIONSHIP: 'RELATIONSHIP',
  USER: 'USER',
};

/** @enum {string} */
export const OverridesSortBy = {
  NAME_ASC: 'NAME_ASC',
  NAME_DESC: 'NAME_DESC',
};

/** @enum {string} */
export const AssignmentType = {
  ORGANISATION: 'ORGANISATION',
  POSITION: 'POSITION',
  COHORT: 'COHORT',
};

/** @enum {string} */
export const RoleAssignTarget = {
  WORKFLOW: 'WORKFLOW',
  APPROVAL_OVERRIDE: 'APPROVAL_OVERRIDE',
};

/** @enum {string} */
export const StageType = {
  FORM_SUBMISSION: 'FORM_SUBMISSION',
  APPROVALS: 'APPROVALS',
  WAITING: 'WAITING',
  FINISHED: 'FINISHED',
};

/** @enum {string} */
export const FormviewVisibility = {
  EDITABLE: 'EDITABLE',
  EDITABLE_AND_REQUIRED: 'EDITABLE_AND_REQUIRED',
  READ_ONLY: 'READ_ONLY',
  HIDDEN: 'HIDDEN',
};
