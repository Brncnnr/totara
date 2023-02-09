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
import { get, totaraUrl } from 'tui/util';
import { assign, shimmerAssign } from 'tui_xstate/xstate';
import { notify } from 'tui/notifications';
import apollo from 'tui/apollo_client';
import manageableWorkflows from 'mod_approval/graphql/manageable_workflows';
import { produce } from 'tui/immutable';
import { getSuccessMessageAsync } from 'mod_approval/messages';

const WORKFLOWS_KEY = 'mod_approval_manageable_workflows';

export const updatePagination = shimmerAssign((context, { pagination }) => {
  context.variables.query_options.pagination = pagination;
});

export const updateSort = shimmerAssign((context, { sortBy }) => {
  const options = context.variables.query_options;
  options.sort_by = sortBy;
  options.pagination.page = 1;
});

export const updateFilters = shimmerAssign((context, { filters }) => {
  const options = context.variables.query_options;
  options.filters = filters;
  options.pagination.page = 1;
});

export const setToMutateId = assign({
  toMutateId: (context, event) => event.workflowId,
});
export const unsetToMutateId = assign({ toMuatateId: null });

export const notifySuccess = async (context, event) => {
  return notify({
    duration: 3000,
    message: await getSuccessMessageAsync(event),
    type: 'success',
  });
};

// This action uses assign and writeQuery in the same action
// because the V4 Xstate runs the assign actions prior to other actions
// making it difficult to unset the toMutateId in the same event flow.
export const removeCachedWorkflow = assign({
  toMutateId: ({ variables, toMutateId }) => {
    const data = apollo.readQuery({ query: manageableWorkflows, variables });

    apollo.writeQuery({
      query: manageableWorkflows,
      variables,
      data: produce(data, draft => {
        const workflows = draft[WORKFLOWS_KEY];
        workflows.items = workflows.items.filter(
          workflow => workflow.id !== toMutateId
        );
      }),
    });

    return null;
  },
});

export const navigateToClone = (context, event) => {
  const id = get(event, [
    'data',
    'data',
    'mod_approval_workflow_clone',
    'workflow',
    'id',
  ]);
  window.location.href = totaraUrl('/mod/approval/workflow/edit.php', {
    workflow_id: id,
    notify_type: 'success',
    notify: 'clone_workflow',
  });
};

export const navigateToNewWorkflow = (context, event) => {
  const id = get(event, [
    'data',
    'data',
    'mod_approval_workflow_create',
    'workflow',
    'id',
  ]);
  window.location.href = totaraUrl('/mod/approval/workflow/edit.php', {
    workflow_id: id,
    notify_type: 'success',
    notify: 'create_workflow',
  });
};
