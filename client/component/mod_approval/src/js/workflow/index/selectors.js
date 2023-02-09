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
import { getWorkflows } from 'mod_approval/graphql_selectors/manageable_workflows';
export * from 'mod_approval/graphql_selectors/manageable_workflows';

/**
 * @typedef {import('./context).WorkflowIndexContext} WorkflowIndexContext
 */

/**
 * @typedef {Object} ModApprovalManageableWorkflowsVariables
 */

/**
 * @typedef {import('../../constants').WorkflowsSortOption} WorkflowsSortOption
 */

/**
 * @typedef {import('../../constants').StatusEnum} StatusEnum
 */

/**
 * @param {WorkflowIndexContext} context
 * @return {?string}
 */
export const getToMutateId = context => context.toMutateId;

/**
 * @param {WorkflowIndexContext} context
 * @return {ModApprovalManageableWorkflowsVariables}
 */
export const getVariables = context => context.variables;

export const getQueryOptions = createSelector(
  getVariables,
  /** @return {Object} */
  variables => variables.query_options
);
export const getPagination = createSelector(
  getQueryOptions,
  /** @return {Object} */
  queryOptions => queryOptions.pagination
);
export const getSortBy = createSelector(
  getQueryOptions,
  /** @return {WorkflowsSortOption} */
  queryOptions => queryOptions.sort_by
);
export const getFilters = createSelector(
  getQueryOptions,
  /** @return {Object} */
  queryOptions => queryOptions.filters || {}
);
export const getStatusFilter = createSelector(
  getFilters,
  /** @return {StatusEnum} */
  filters => filters.status || null
);

export const getWorkflowTypeIdFilter = createSelector(
  getFilters,
  /** @return {?string} */
  filters => filters.workflow_type_id || null
);

export const getPage = createSelector(
  getPagination,
  /** @return {number} */
  pagination => pagination.page
);
export const getLimit = createSelector(
  getPagination,
  /** @return {number} */
  pagination => pagination.limit
);

export const getAssignmentTypeFilter = createSelector(
  getFilters,
  /** @return {?string} */
  filters => filters.assignment_type || null
);

export const getWorkflowNameFilter = createSelector(
  getFilters,
  /** @return {?string} */
  filters => filters.name
);

export const getToMutateWorkflow = createSelector(
  getWorkflows,
  getToMutateId,
  /** @return {Object} */
  (workflows, toMutateId) =>
    workflows.find(workflow => workflow.id === toMutateId)
);

export const getToMutateWorkflowName = createSelector(
  getToMutateWorkflow,
  /** @return {string} */
  toMutateWorkflow =>
    toMutateWorkflow && toMutateWorkflow.name ? toMutateWorkflow.name : ''
);
