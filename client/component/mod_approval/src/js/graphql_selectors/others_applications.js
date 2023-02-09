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
import { APPLICATIONS_FROM_OTHERS } from 'mod_approval/constants';

const KEY = 'mod_approval_others_applications';

/**
 * @typedef {Object} ModApprovalApplicationsPage
 * @property {object[]} items
 * @property {number} total
 * @property {string} next_cursor
 */

/**
 * @typedef {Object} ModApprovalOthersApplicationsResponse
 * @property {ModApprovalApplicationsPage} mod_approval_others_applications
 */

/**
 * @param {object} context
 * @return {ModApprovalApplicationsResponse}
 */
const getOthersApplicationsResponse = context =>
  context[APPLICATIONS_FROM_OTHERS];

/**
 * @param {object} context
 * @return {ModApprovalApplicationsPage}
 */
const getApplicationsData = createSelector(
  getOthersApplicationsResponse,
  response => response[KEY]
);

/**
 * @param {object} context
 * @return {object[]}
 */
export const getApplications = createSelector(
  getApplicationsData,
  data => data.items
);

/**
 * @param {object} context
 * @return {number}
 */
export const getApplicationsTotal = createSelector(
  getApplicationsData,
  data => data.total || 0
);

/**
 * @param {object} context
 * @return {number}
 */
export const getApplicationsCount = createSelector(
  getApplications,
  applications => applications.length
);

/** @typedef {"DRAFT"|"IN_PROGRESS"|"FINISHED"|"REJECTED"|"WITHDRAWN"} ModApprovalApplicationOverallProgressStates */
/** @typedef {"PENDING"|"APPROVED"|"REJECTED"|"NA"} ModApprovalApplicationYourProgressStates */

/**
 * @typedef {Object} ModApprovalOthersApplicationsFilters
 * @property {string} application_id
 * @property {?string} workflow_type_name
 * @property {?ModApprovalApplicationOverallProgressStates[]} overall_progress
 * @property {?ModApprovalApplicationYourProgressStates} your_progress
 * @property {?string} applicant_name
 */

/**
 * @typedef {"SUBMITTED"|"WORKFLOW_TYPE_NAME"|"APPLICANT_NAME"|"ID_NUMBER"|"TITLE"} ModApprovalOthersApplicationsSortyByOptions
 */

/**
 * @typedef {Object} CorePaginationInput
 * @property {?string} cursor
 * @property {?number} limit
 * @property {?number} page
 */

/**
 * @typedef {Object} ModApprovalOthersApplicationsInput
 * @property {CorePaginationInput} pagination
 * @property {ModApprovalOthersApplicationsFilters} filters
 * @property {ModApprovalOthersApplicationsSortyByOptions} sort_by
 */

/**
 * @typedef {Object} ModApprovalOthersApplicationsVariables
 * @property {ModApprovalOthersApplicationsInput} query_options
 */

/**
 * @param {{page: number, limit: number, filters: ModApprovalOthersApplicationsFilters}} options
 * @return {ModApprovalOthersApplicationsVariables}
 */
export const variables = ({ page = 1, limit = 20, filters = {} }) => ({
  query_options: {
    pagination: {
      page,
      limit,
    },
    filters,
  },
});
