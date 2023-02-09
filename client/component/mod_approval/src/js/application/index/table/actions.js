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
import { assign, shimmerAssign } from 'tui_xstate/xstate';
import { get, set, totaraUrl } from 'tui/util';
import { notify } from 'tui/notifications';
import { getString } from 'tui/i18n';
import apollo from 'tui/apollo_client';
import myApplications from 'mod_approval/graphql/my_applications';
import otherApplications from 'mod_approval/graphql/others_applications';
import {
  getMyApplicationsVars,
  getOthersApplicationsVars,
  getMyApplicationsQueryOptions,
  getOthersApplicationsQueryOptions,
} from 'mod_approval/application/index/table/selectors';
import { getErrorMessageAsync } from 'mod_approval/messages';
import { MY, OTHERS } from 'mod_approval/constants';
import { setTableParams } from './helpers';

export const setQueryOptions = shimmerAssign((context, { path, value }) => {
  set(context.variables, path, value);
});

export const setPageTo1 = shimmerAssign((context, { path }) => {
  context.variables[path[0]].query_options.pagination.page = 1;
});

export const setApplicationToDelete = assign({
  applicationToDeleteId: (context, event) => event.applicationToDeleteId,
});

export const navigateToCloned = (context, event) => {
  const application_id = get(event, [
    'data',
    'data',
    'mod_approval_application_clone',
    'application',
    'id',
  ]);

  window.location.href = totaraUrl(
    '/mod/approval/application/edit.php',
    Object.assign(
      {
        application_id,
        notify_type: 'success',
        notify: 'clone_application',
      },
      context.parsedParams || {}
    )
  );
};

export const errorNotify = async (context, event) => {
  await notify({
    message: await getErrorMessageAsync(event),
    type: 'error',
  });
};

export const deletedNotify = () => {
  notify({
    duration: 3000,
    message: getString(`success:delete_application`, 'mod_approval'),
    type: 'success',
  });
};

export const showNotify = context => {
  notify({
    duration: 3000,
    message: getString(
      `${context.notifyType}:${context.notify}`,
      'mod_approval'
    ),
    type: context.notifyType,
  });
};

export const unsetNotify = assign({
  notify: null,
  notifyType: null,
});

export const removeFromMyApplications = assign({
  applicationToDeleteId: context => {
    const variables = getMyApplicationsVars(context);
    removeApplicationFromQuery(
      context.applicationToDeleteId,
      myApplications,
      variables
    );

    return null;
  },
});

export const removeFromOthersApplication = assign({
  applicationToDeleteId: context => {
    const variables = getOthersApplicationsVars(context);
    removeApplicationFromQuery(
      context.applicationToDeleteId,
      otherApplications,
      variables
    );

    return null;
  },
});

/**
 * These two actions rely on preserveActionOrder configued as true
 * and setQueryOptions updating the queryOptions in a previous action.
 */
export const setBaseParamsOthers = shimmerAssign(context => {
  const queryOptions = getOthersApplicationsQueryOptions(context);
  const params = setTableParams(queryOptions, OTHERS);
  params.from_dashboard = true;
  params.tab = OTHERS;
  context.parsedParams = params;
});

export const setBaseParamsMy = shimmerAssign(context => {
  const queryOptions = getMyApplicationsQueryOptions(context);
  const params = setTableParams(queryOptions, MY);
  params.from_dashboard = true;
  params.tab = MY;
  context.parsedParams = params;
});

/**
 * Removes the applicationId from the apollo cache.
 * Used in actions after an application is deleted from the dashboard table.
 *
 * @param {Number} deletedApplicationId application id deleted.
 * @param query GraphQl query to remove from.
 * @param {Object} variables Query variables.
 */
const removeApplicationFromQuery = (deletedApplicationId, query, variables) => {
  const data = apollo.readQuery({
    query,
    variables,
  });
  const queryName = query.definitions[0].name.value;
  const queryData = data[queryName];

  const updatedData = {
    [queryName]: {
      items: queryData.items.filter(
        application => application.id !== deletedApplicationId
      ),
      total: queryData.total - 1,
      next_cursor: queryData.next_cursor,
    },
  };

  apollo.writeQuery({
    query,
    variables,
    data: updatedData,
  });
};
