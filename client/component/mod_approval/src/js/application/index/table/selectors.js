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
  MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  ApplicationTableColumn,
} from 'mod_approval/constants';
import {
  getMyApplicationsTotal,
  getMyApplications,
} from 'mod_approval/graphql_selectors/my_applications';
import {
  getApplicationsTotal,
  getApplications,
} from 'mod_approval/graphql_selectors/others_applications';
export * from 'mod_approval/graphql_selectors/my_applications';
export * from 'mod_approval/graphql_selectors/others_applications';

const ALL = 'ALL';

const getVariables = context => context.variables;

export const getParsedParams = context => {
  return Object.entries(context.parsedParams).reduce((acc, [key, value]) => {
    if (value !== undefined) {
      acc[key] = value;
    }

    return acc;
  }, {});
};

export const getMyApplicationsVars = createSelector(
  getVariables,
  variables => variables[MY_APPLICATIONS]
);
export const getOthersApplicationsVars = createSelector(
  getVariables,
  variables => variables[APPLICATIONS_FROM_OTHERS]
);
export const getMyApplicationsQueryOptions = createSelector(
  getMyApplicationsVars,
  variables => variables.query_options
);
export const getMyApplicationsFilters = createSelector(
  getMyApplicationsQueryOptions,
  queryOptions => queryOptions.filters || {}
);
export const getMyApplicationsOverallProgress = createSelector(
  getMyApplicationsFilters,
  filters => filters.overall_progress || ALL
);
export const getMyApplicationsSortBy = createSelector(
  getMyApplicationsQueryOptions,
  queryOptions => queryOptions.sort_by || ApplicationTableColumn.SUBMITTED
);
export const getMyApplicationsPagination = createSelector(
  getMyApplicationsQueryOptions,
  queryOptions => queryOptions.pagination || {}
);
export const getMyApplicationsPage = createSelector(
  getMyApplicationsPagination,
  pagination => pagination.page || 1
);
export const getMyApplicationsLimit = createSelector(
  getMyApplicationsPagination,
  pagination => pagination.limit || 20
);

export const getZeroMyApplications = createSelector(
  getMyApplicationsTotal,
  getMyApplicationsOverallProgress,
  (total, overallProgress) => total === 0 && overallProgress === ALL
);

// APPLICATIONS_FROM_OTHERS
export const getOthersApplicationsQueryOptions = createSelector(
  getOthersApplicationsVars,
  variables => variables.query_options
);
export const getOthersApplicationsFilters = createSelector(
  getOthersApplicationsQueryOptions,
  queryOptions => queryOptions.filters || {}
);
export const getOthersApplicationsOverallProgress = createSelector(
  getOthersApplicationsFilters,
  filters => filters.overall_progress || ALL
);
export const getOthersApplicationsYourProgress = createSelector(
  getOthersApplicationsFilters,
  filters => filters.your_progress || ALL
);
export const getOthersApplicationsSearch = createSelector(
  getOthersApplicationsFilters,
  filters => filters.applicant_name
);
export const getOthersApplicationsSortBy = createSelector(
  getOthersApplicationsQueryOptions,
  queryOptions => queryOptions.sort_by || ApplicationTableColumn.SUBMITTED
);
export const getOthersApplicationsPagination = createSelector(
  getOthersApplicationsQueryOptions,
  queryOptions => queryOptions.pagination || {}
);
export const getOthersApplicationsPage = createSelector(
  getOthersApplicationsPagination,
  pagination => pagination.page || 1
);
export const getOthersApplicationsLimit = createSelector(
  getOthersApplicationsPagination,
  pagination => pagination.limit || 20
);

// filter is in a reset state
export const getZeroApplicationsFromOthers = createSelector(
  getApplicationsTotal,
  getOthersApplicationsOverallProgress,
  getOthersApplicationsYourProgress,
  getOthersApplicationsSearch,
  (total, overallProgress, yourProgress, search) =>
    total === 0 && overallProgress === ALL && yourProgress === ALL && !search
);

export const getToDeleteId = context => context.applicationToDeleteId;

export const getToDeleteApplication = createSelector(
  getApplications,
  getMyApplications,
  getToDeleteId,
  (myApplications, applications, toDeleteId) =>
    [...myApplications, ...applications].find(
      application => application.id === toDeleteId
    )
);

export const getToDeleteApplicationTitle = createSelector(
  getToDeleteApplication,
  toDeleteApplication =>
    toDeleteApplication && toDeleteApplication.title
      ? toDeleteApplication.title
      : ''
);
