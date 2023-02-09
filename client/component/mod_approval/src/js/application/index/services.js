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
import createApplicationMutation from 'mod_approval/graphql/create_application';
import { YourProgressState } from 'mod_approval/constants';
import othersApplicationsQuery from 'mod_approval/graphql/others_applications';
import myApplicationsQuery from 'mod_approval/graphql/my_applications';
import applicationMenuQuery from 'mod_approval/graphql/create_new_application_menu';
import workflowTypesQuery from 'mod_approval/graphql/load_workflow_types';

export function myApplications() {
  return {
    query: myApplicationsQuery,
    variables: {
      query_options: {
        filters: {},
      },
    },
  };
}

export function loadWorkflowTypes() {
  return {
    query: workflowTypesQuery,
    variables: {
      input: {
        require_active_workflow: true,
      },
    },
  };
}

export function applicationsFromOthers() {
  return {
    query: othersApplicationsQuery,
    variables: {
      query_options: {
        filters: {
          your_progress: YourProgressState.PENDING,
        },
      },
    },
  };
}

export function createNewApplicationMenu() {
  return {
    query: applicationMenuQuery,
  };
}

export function createApplication(context, { data }) {
  const { selectedJobAssignment, selectedUser } = data;

  return apollo.mutate({
    mutation: createApplicationMutation,
    variables: {
      input: {
        applicant_id: selectedUser.id,
        assignment_id: selectedJobAssignment.assignment_id,
        job_assignment_id: selectedJobAssignment.job_assignment_id,
      },
    },
  });
}
