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
import apollo from 'tui/apollo_client';
import deleteWorkflowMutation from 'mod_approval/graphql/workflow_delete';
import archiveWorkflowMutation from 'mod_approval/graphql/workflow_archive';
import unarchiveWorkflowMutation from 'mod_approval/graphql/workflow_unarchive';
import manageableWorkflowsQuery from 'mod_approval/graphql/manageable_workflows';
import cloneWorkflowMutation from 'mod_approval/graphql/workflow_clone';
import createWorkflowMutation from 'mod_approval/graphql/workflow_create';

// Queries
export function manageableWorkflows(context) {
  return {
    query: manageableWorkflowsQuery,
    variables: context.variables,
    fetchPolicy: 'network-only',
  };
}

// Mutations
export function createWorkflow(context, { data }) {
  return apollo.mutate({
    mutation: createWorkflowMutation,
    variables: {
      input: data.createData,
    },
  });
}

export function deleteWorkflow({ toMutateId }) {
  return apollo.mutate({
    mutation: deleteWorkflowMutation,
    variables: {
      input: {
        workflow_id: parseInt(toMutateId, 10),
      },
    },
  });
}

export function archiveWorkflow({ toMutateId }) {
  return apollo.mutate({
    mutation: archiveWorkflowMutation,
    variables: {
      input: {
        workflow_id: parseInt(toMutateId, 10),
      },
    },
  });
}

export function unarchiveWorkflow({ toMutateId }) {
  return apollo.mutate({
    mutation: unarchiveWorkflowMutation,
    variables: {
      input: {
        workflow_id: parseInt(toMutateId, 10),
      },
    },
  });
}

export function cloneWorkflow({ toMutateId }, { data }) {
  const details = data.cloneData;
  return apollo.mutate({
    mutation: cloneWorkflowMutation,
    variables: {
      input: {
        workflow_id: parseInt(toMutateId, 10),
        name: details.name,
        default_assignment: details.defaultAssignment,
      },
    },
  });
}
