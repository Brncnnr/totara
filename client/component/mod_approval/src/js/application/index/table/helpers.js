/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
import { getMyApplicationsVars, getOthersApplicationsVars } from './selectors';
import { variables } from './context';
import {
  MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  MY,
  OTHERS,
  ApplicationTableColumn,
  OverallProgressState,
  YourProgressState,
} from 'mod_approval/constants';

/*
 * @param {Object} queryOptions
 * @param {'my'|'others'} query
 * @returns {Object}
 */
export function setTableParams(queryOptions, query) {
  const { pagination, filters, sort_by } = queryOptions;
  const params = {};

  if (pagination) {
    params[`${query}.pagination.page`] = pagination.page;
    params[`${query}.pagination.limit`] = pagination.limit;
  }

  if (filters) {
    Object.keys(filters).forEach(filter => {
      if (filters[filter]) {
        params[`${query}.filters.${filter}`] = filters[filter];
      }
    });
  }

  // set inactive filters as explicitly undefined so that they are removed from the url
  ['applicant_name', 'overall_progress', 'your_progress'].forEach(filter => {
    if (!params[`${query}.filters.${filter}`]) {
      params[`${query}.filters.${filter}`] = undefined;
    }
  });

  if (sort_by) {
    params[`${query}.sort_by`] = sort_by;
  }

  return params;
}

/*
 * @param {Object} context
 * @param {Object} context
 * @returns {Object}
 */
export function mapContextToQueryParams(context, prevContext) {
  let params = {};
  const myAppsVars = getMyApplicationsVars(context);
  const prevMyAppsVars = getMyApplicationsVars(prevContext);
  const othersVars = getOthersApplicationsVars(context);
  const prevOthersVars = getOthersApplicationsVars(prevContext);
  const notifyAndNotifyType = context.notify && context.notifyType;

  if (notifyAndNotifyType) {
    params.notify = prevContext.notify;
    params.notifyType = prevContext.notifyType;
  } else {
    params.notify = undefined;
    params.notifyType = undefined;
  }

  if (othersVars !== prevOthersVars) {
    params = Object.assign(
      params,
      setTableParams(othersVars.query_options, OTHERS)
    );
  }

  if (myAppsVars !== prevMyAppsVars) {
    params = Object.assign(
      params,
      setTableParams(myAppsVars.query_options, MY)
    );
  }

  return params;
}

/*
 * @param {Object} context
 * @returns {Object}
 */
export function mapQueryParamsToContext(params) {
  const context = {
    notify: params.notify,
    notifyType: params.notify_type,
    variables: variables(),
    parsedParams: {},
  };

  Object.keys(params).forEach(key => {
    if (key.includes(`${MY}.`) || key.includes(`${OTHERS}.`)) {
      context.parsedParams[key] = params[key];
      if (!context.parsedParams.from_dashboard) {
        context.parsedParams.from_dashboard = true;
      }

      const parts = key.split('.');
      const query =
        parts[0] === MY ? MY_APPLICATIONS : APPLICATIONS_FROM_OTHERS;
      const value = isNaN(params[key])
        ? params[key]
        : parseInt(params[key], 10);

      if (!['sort_by', 'filters', 'pagination'].includes(parts[1])) {
        return;
      }

      if (parts[1] === 'sort_by' && ApplicationTableColumn[value]) {
        context.variables[query].query_options.sort_by = value;
        return;
      }

      if (
        parts[1] === 'filters' &&
        !['overall_progress', 'your_progress', 'applicant_name'].includes(
          parts[2]
        )
      ) {
        return;
      }

      if (parts[1] === 'filters') {
        context.variables[query].query_options.filters = {};
      }

      if (parts[2] === 'overall_progress' && !OverallProgressState[value]) {
        return;
      }

      if (parts[2] === 'your_progress' && !YourProgressState[value]) {
        return;
      }

      if (parts[1] === 'pagination' && !['limit', 'page'].includes(parts[2])) {
        return;
      }

      if (parts[2] === 'limit' && ![10, 20, 50, 100].includes(value)) {
        return;
      }

      if (parts[2] === 'page' && isNaN(value)) {
        return;
      }

      context.variables[query].query_options[parts[1]][parts[2]] = value;
    }
  });

  return context;
}
