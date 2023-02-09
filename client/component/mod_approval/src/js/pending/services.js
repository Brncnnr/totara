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
import { APPLICATIONS_FROM_OTHERS } from 'mod_approval/constants';
import { getApplications } from 'mod_approval/graphql_selectors/others_applications';
import othersApplicationsQuery from 'mod_approval/graphql/others_applications';

/**
 * @typedef {import('./context).PendingContext} PendingContext
 */

/**
 * @param {PendingContext} context
 * @return {import('apollo').QueryOptions}
 */
export function applicationsFromOthers(context) {
  return {
    query: othersApplicationsQuery,
    variables: context.variables,
    fetchPolicy: 'network-only',
  };
}

/**
 * @param {PendingContext} context
 * @param {object} othersApplicationsResponse
 * @return {Partial<PendingContext>}
 */
applicationsFromOthers.updateContext = (
  context,
  { data: { mod_approval_others_applications } }
) => {
  // always append to existing applications
  // empty on initial load
  const items = getApplications(context).concat(
    mod_approval_others_applications.items
  );

  return {
    [APPLICATIONS_FROM_OTHERS]: {
      mod_approval_others_applications: {
        items,
        total: mod_approval_others_applications.total,
        next_cursor: mod_approval_others_applications.next_cursor,
      },
    },
  };
};
