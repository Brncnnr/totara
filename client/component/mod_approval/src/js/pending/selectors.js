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
import {
  getApplications,
  getApplicationsCount,
  getApplicationsTotal,
} from 'mod_approval/graphql_selectors/others_applications';
export * from 'mod_approval/graphql_selectors/others_applications';

/**
 * @typedef {import('./context).PendingContext} PendingContext
 */

/**
 * @param {PendingContext} context
 * @return {number}
 */
export const getGridWidth = context => context.gridWidth;

/**
 * @param {PendingContext} context
 * @return {import('../graphql_selectors/others_applications').ModApprovalOthersApplicationsVariables}
 */
export const getVariables = context => context.variables;

export const getQueryOptions = createSelector(
  getVariables,
  /** @return {import('../graphql_selectors/others_applications').ModApprovalOthersApplicationsInput} */
  variables => variables.query_options
);

export const getPagination = createSelector(
  getQueryOptions,
  /** @return {import('../graphql_selectors/others_applications).CorePaginationInput} */
  queryOptions => queryOptions.pagination
);

export const getPage = createSelector(
  getPagination,
  /** @return {number} */
  pagination => pagination.page
);

/**
 *  @typedef {Object} Row
 *  @property {number} index
 *  @property {object[]} items
 */
export const getRows = createSelector(
  getGridWidth,
  getApplications,
  /** @return {Row[]} */
  (gridWidth, applications) => {
    const rows = [];
    for (let index = 0; index < applications.length; index += gridWidth) {
      const row = applications.slice(index, index + gridWidth);
      rows.push({ index: rows.length, items: row });
    }

    return rows;
  }
);

export const getHasMore = createSelector(
  getApplicationsCount,
  getApplicationsTotal,
  /** @return {boolean} */
  (count, total) => total > count
);
