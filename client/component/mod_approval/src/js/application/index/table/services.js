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
import apollo from 'tui/apollo_client';
import cloneApplicationMutation from 'mod_approval/graphql/application_clone';
import deleteApplicationMutation from 'mod_approval/graphql/application_delete';
import othersApplicationsQuery from 'mod_approval/graphql/others_applications';
import myApplicationsQuery from 'mod_approval/graphql/my_applications';
import {
  APPLICATIONS_FROM_OTHERS,
  MY_APPLICATIONS,
} from 'mod_approval/constants';

export function myApplications(context) {
  return {
    query: myApplicationsQuery,
    variables: context.variables[MY_APPLICATIONS],
    fetchPolicy: 'network-only',
  };
}

export function applicationsFromOthers(context) {
  return {
    query: othersApplicationsQuery,
    variables: context.variables[APPLICATIONS_FROM_OTHERS],
    fetchPolicy: 'network-only',
  };
}

export const deleteApplication = context => {
  return apollo.mutate({
    mutation: deleteApplicationMutation,
    variables: {
      input: {
        application_id: context.applicationToDeleteId,
      },
    },
  });
};

export const cloningApplication = (context, event) => {
  return apollo.mutate({
    mutation: cloneApplicationMutation,
    variables: {
      input: {
        application_id: event.applicationToCloneId,
      },
    },
  });
};
