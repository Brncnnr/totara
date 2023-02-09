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
import { SELECTABLE_USERS } from 'mod_approval/constants';
import apollo from 'tui/apollo_client';
import { get } from 'tui/util';
import { getAssignmentId, getWorkflowStageId } from './selectors';
import ancestorAssignmentApprovalLevelsQuery from 'mod_approval/graphql/ancestor_assignment_approval_levels';
import selectableUsersQuery from 'mod_approval/graphql/selectable_users';
import assignmentIdentifiersQuery from 'mod_approval/graphql/assignment_identifiers';
import assignmentManageMutation from 'mod_approval/graphql/assignment_manage';
import { variables as makeVariables } from 'mod_approval/graphql_selectors/selectable_users';

export function ancestorAssignmentApprovalLevels(context) {
  return {
    query: ancestorAssignmentApprovalLevelsQuery,
    variables: {
      input: {
        assignment_id: getAssignmentId(context),
        workflow_stage_id: getWorkflowStageId(context),
      },
    },
  };
}

export function selectableUsers(context) {
  const variables = context.activeLevelId
    ? context.userSearchVariables[context.activeLevelId]
    : makeVariables({ workflowId: context.workflowId });

  return {
    query: selectableUsersQuery,
    variables,
  };
}

selectableUsers.updateContext = (context, event) => {
  const { activeLevelId, shouldAppend } = context;

  if (activeLevelId) {
    const existingUsers = get(context, ['users', activeLevelId, 'items']) || [];

    const users = shouldAppend
      ? existingUsers.concat(event.data.mod_approval_selectable_users.items)
      : event.data.mod_approval_selectable_users.items;

    return {
      users: Object.assign({}, context.users, {
        [context.activeLevelId]: {
          users,
          next_cursor: event.data.mod_approval_selectable_users.next_cursor,
          total: event.data.mod_approval_selectable_users.total,
        },
      }),
    };
  }

  // TODO: TL-32777
  // also handle blank search append case
  return {
    [SELECTABLE_USERS]: event.data,
  };
};

export function assignmentIdentifiers(context) {
  return {
    query: assignmentIdentifiersQuery,
    variables: {
      input: {
        workflow_id: context.workflowId,
        assignment_type: context.selectedAssignmentType,
      },
    },
    fetchPolicy: 'network-only',
  };
}

assignmentIdentifiers.updateContext = (context, event) => {
  const { selectedAssignmentType, disabledIds } = context;
  const { mod_approval_assignment_identifiers } = event.data;

  return {
    disabledIds: Object.assign({}, disabledIds, {
      [selectedAssignmentType]: mod_approval_assignment_identifiers,
    }),
  };
};

export function createOverrideAssignment(context) {
  return apollo.mutate({
    mutation: assignmentManageMutation,
    variables: {
      input: {
        workflow_stage_id: context.workflowStageId,
        type: context.selectedAssignmentType,
        identifier: parseInt(context.selectedIdentifier, 10),
      },
    },
  });
}
