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
import { get } from 'tui/util';
import apollo from 'tui/apollo_client';

// Queries
import overrideAssignmentsQuery from 'mod_approval/graphql/override_assignments';
import selectableUsersQuery from 'mod_approval/graphql/selectable_users';
import overrideForAssignmentTypeQuery from 'mod_approval/graphql/override_for_assignment_type';
import workflowStageMoveToQuery from 'mod_approval/graphql/workflow_stage_move_to';

// Mutations
import setAssignmentApprovalLevelApproversMutation from 'mod_approval/graphql/assignment_set_level_approvers';
import archiveOverrideAssignmentMutation from 'mod_approval/graphql/archive_override_assignment';
import assignmentManageMutation from 'mod_approval/graphql/assignment_manage';
import publishWorkflowVersionMutation from 'mod_approval/graphql/workflow_version_publish';
import addApprovalLevelMutation from 'mod_approval/graphql/workflow_stage_add_approval_level';
import deleteApprovalLevelMutation from 'mod_approval/graphql/workflow_stage_delete_approval_level';
import reorderApprovalLevelMutation from 'mod_approval/graphql/workflow_reorder_levels';
import deleteWorkflowMutation from 'mod_approval/graphql/workflow_delete';
import archiveWorkflowMutation from 'mod_approval/graphql/workflow_archive';
import unarchiveWorkflowMutation from 'mod_approval/graphql/workflow_unarchive';
import cloneWorkflowMutation from 'mod_approval/graphql/workflow_clone';
import editWorkflowMutation from 'mod_approval/graphql/workflow_edit';
import editStageMutation from 'mod_approval/graphql/workflow_stage_edit';
import editApprovalLevelMutation from 'mod_approval/graphql/workflow_stage_edit_approval_level';
import { getSelectableUsers } from 'mod_approval/graphql_selectors/selectable_users';
import addStageMutation from 'mod_approval/graphql/workflow_version_add_stage';
import deleteStageMutation from 'mod_approval/graphql/workflow_stage_delete';
import configureFormviewsMutation from 'mod_approval/graphql/workflow_stage_configure_formviews';
import confgureTransitionMutation from 'mod_approval/graphql/workflow_stage_interaction_configure_transition';
import {
  getWorkflowId,
  getLatestVersionId,
  getFirstWorkflowStage,
} from 'mod_approval/graphql_selectors/load_workflow';

import {
  UPDATE_APPROVAL_LEVEL_APPROVERS,
  ADD_APPROVAL_LEVEL,
  SELECT_APPROVER_TYPE,
  DELETE_APPROVAL_LEVEL,
  REORDER_APPROVAL_LEVEL,
  RENAME_APPROVAL_LEVEL,
  RENAME_WORKFLOW_STAGE,
  UPDATE_FORMVIEW,
  UPDATE_SECTION_VISIBILITY,
} from './state/persistence';
import {
  getActiveVariables,
  getOverridesStageKey,
  getDefaultTransitionId,
  getDefaultVariables,
  getActiveUserSearchVariables,
  getActiveApprovalLevelId,
  getActiveStageId,
  getInteractionId,
  getToEditStageId,
} from './selectors';
import { loadWorkflowOptions } from './query_options';

export function loadWorkflow(context) {
  return loadWorkflowOptions(getWorkflowId(context));
}

export function selectableUsers(context) {
  return {
    query: selectableUsersQuery,
    variables: getActiveUserSearchVariables(context),
  };
}
selectableUsers.updateContext = (
  context,
  { data: { mod_approval_selectable_users } }
) => {
  const activeApprovalLevelId = getActiveApprovalLevelId(context);

  if (activeApprovalLevelId) {
    const existingUsers =
      get(context, ['users', activeApprovalLevelId, 'items']) || [];
    const users = context.appendUsers
      ? existingUsers.concat(mod_approval_selectable_users.items)
      : mod_approval_selectable_users.items;

    return {
      users: Object.assign({}, context.users, {
        [activeApprovalLevelId]: Object.assign(
          {},
          mod_approval_selectable_users,
          {
            items: users,
          }
        ),
      }),
    };
  }

  return {
    [SELECTABLE_USERS]: {
      mod_approval_selectable_users: context.appendUsers
        ? {
            items: getSelectableUsers(context).concat(
              mod_approval_selectable_users.items
            ),
            next_cursor: mod_approval_selectable_users.next_cursor,
            total: mod_approval_selectable_users.total,
          }
        : mod_approval_selectable_users,
    },
  };
};

export function overrideAssignments(context) {
  return {
    query: overrideAssignmentsQuery,
    variables: getActiveVariables(context) || getDefaultVariables(context),
    fetchPolicy: 'network-only',
  };
}
overrideAssignments.updateContext = (
  context,
  { data: { mod_approval_override_assignments } }
) => {
  const overridesStageKey = getOverridesStageKey(context);
  return {
    [overridesStageKey]: mod_approval_override_assignments,
  };
};

export function overrideForAssignment(context) {
  return {
    query: overrideForAssignmentTypeQuery,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
        assignment_type: context.assignRolesTargetAssignment.type,
        assignment_identifier: context.assignRolesTargetAssignment.id,
      },
    },
    fetchPolicy: 'network-only',
  };
}

export function workflowStageMoveTo(context) {
  return {
    query: workflowStageMoveToQuery,
    variables: {
      input: {
        workflow_stage_id: getActiveStageId(context),
      },
    },
  };
}

// Mutations
const updateApprovers = (context, { variables }) => {
  return apollo.mutate({
    mutation: setAssignmentApprovalLevelApproversMutation,
    variables,
  });
};

const addApprovalLevel = (context, { workflowStageId, name }) => {
  return apollo.mutate({
    mutation: addApprovalLevelMutation,
    variables: {
      input: {
        workflow_stage_id: workflowStageId,
        name,
      },
    },
  });
};

const deleteApprovalLevel = async context => {
  const approvalLevelId = context.toEditApprovalLevelId;
  const result = await apollo.mutate({
    mutation: deleteApprovalLevelMutation,
    variables: {
      input: {
        workflow_stage_approval_level_id: approvalLevelId,
      },
    },
  });
  result.context = { approvalLevelId };
  return result;
};

export function editApprovalLevel(context, { name, approvalLevelId }) {
  return apollo.mutate({
    mutation: editApprovalLevelMutation,
    variables: {
      input: {
        workflow_stage_approval_level_id: approvalLevelId,
        name: name,
      },
    },
  });
}

const reorderApprovalLevel = (context, event) => {
  const ids = context.loadWorkflow.mod_approval_load_workflow.workflow.latest_version.stages
    .find(stage => stage.id == event.workflowStageId)
    .approval_levels.map(level => level.id);
  return apollo.mutate({
    mutation: reorderApprovalLevelMutation,
    variables: {
      input: {
        workflow_stage_id: event.workflowStageId,
        workflow_stage_approval_level_ids: ids,
      },
    },
    // the reorderApprovaLevel action only modifies the order in xstate context, not in apollo cache, so we must refetch here
    refetchQueries: [loadWorkflow(context)],
    awaitRefetchQueries: true,
  });
};

const mutations = {
  [ADD_APPROVAL_LEVEL]: addApprovalLevel,
  [DELETE_APPROVAL_LEVEL]: deleteApprovalLevel,
  [SELECT_APPROVER_TYPE]: updateApprovers,
  [UPDATE_APPROVAL_LEVEL_APPROVERS]: updateApprovers,
  [REORDER_APPROVAL_LEVEL]: reorderApprovalLevel,
  [RENAME_WORKFLOW_STAGE]: editStage,
  [RENAME_APPROVAL_LEVEL]: editApprovalLevel,
  [UPDATE_FORMVIEW]: updateFormview,
  [UPDATE_SECTION_VISIBILITY]: updateSectionVisibility,
};

export function addWorkflowStage(context, { values }) {
  return apollo.mutate({
    mutation: addStageMutation,
    variables: {
      input: {
        workflow_version_id: getLatestVersionId(context),
        name: values.name,
        type: values.type,
      },
    },
    refetchQueries: [loadWorkflow(context)],
    awaitRefetchQueries: true,
  });
}

export async function deleteWorkflowStage(context) {
  const stageId = getToEditStageId(context);
  const result = await apollo.mutate({
    mutation: deleteStageMutation,
    variables: {
      input: {
        workflow_stage_id: stageId,
      },
    },
  });
  result.context = { stageId };
  return result;
}

export function publishWorkflowVersion(context) {
  return apollo.mutate({
    mutation: publishWorkflowVersionMutation,
    variables: {
      input: {
        workflow_version_id: getLatestVersionId(context),
      },
    },
  });
}

export const saveWorkflow = async (context, event) => {
  const mutation = context.activeMutation || event;
  const result = await mutations[mutation.type](context, mutation);
  return {
    mutation,
    result,
  };
};

export function deleteWorkflow(context) {
  return apollo.mutate({
    mutation: deleteWorkflowMutation,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
      },
    },
  });
}

export function archiveWorkflow(context) {
  return apollo.mutate({
    mutation: archiveWorkflowMutation,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
      },
    },
  });
}

export function unarchiveWorkflow(context) {
  return apollo.mutate({
    mutation: unarchiveWorkflowMutation,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
      },
    },
  });
}

export function editStage(context, { name, workflowStageId }) {
  return apollo.mutate({
    mutation: editStageMutation,
    variables: {
      input: {
        workflow_stage_id: workflowStageId,
        name: name,
      },
    },
  });
}

export function cloneWorkflow(context, { data }) {
  const details = data.cloneData;
  return apollo.mutate({
    mutation: cloneWorkflowMutation,
    variables: {
      input: {
        workflow_id: getWorkflowId(context),
        name: details.name,
        default_assignment: details.defaultAssignment,
      },
    },
  });
}

export function editWorkflow(context, { data }) {
  return apollo.mutate({
    mutation: editWorkflowMutation,
    variables: {
      input: Object.assign(
        {
          workflow_id: getWorkflowId(context),
        },
        data
      ),
    },
  });
}

export function saveOverrides(context, { data }) {
  const { approversVariables } = data;

  return Promise.all(
    approversVariables.map(variables => {
      return apollo.mutate({
        mutation: setAssignmentApprovalLevelApproversMutation,
        variables,
      });
    })
  );
}

export function archiveOverrides({ toArchiveOverrides }) {
  return apollo.mutate({
    mutation: archiveOverrideAssignmentMutation,
    variables: {
      input: {
        assignment_id: toArchiveOverrides.assignment.id,
      },
    },
  });
}

export async function createApprovalOverrideToAssignRoles(context) {
  return await apollo.mutate({
    mutation: assignmentManageMutation,
    variables: {
      input: {
        type: context.assignRolesTargetAssignment.type,
        identifier: parseInt(context.assignRolesTargetAssignment.id),
        workflow_stage_id: getFirstWorkflowStage(context).id,
      },
    },
  });
}

export async function updateFormview(
  context,
  { variables: { workflowStageId, key, update } }
) {
  return apollo.mutate({
    mutation: configureFormviewsMutation,
    fetchPolicy: 'no-cache',
    variables: {
      input: {
        workflow_stage_id: workflowStageId,
        updates: [Object.assign({ field_key: key }, update)],
      },
    },
  });
}

export async function updateSectionVisibility(
  context,
  { variables: { workflowStageId, key } }
) {
  const updates = get(context.sectionVisibilityUpdates, [workflowStageId, key]);
  if (updates.length === 0) {
    // NOP
    return;
  }

  return apollo.mutate({
    mutation: configureFormviewsMutation,
    fetchPolicy: 'no-cache',
    variables: {
      input: {
        workflow_stage_id: workflowStageId,
        updates,
      },
    },
  });
}

export function updateDefaultTransition(context, { transition }) {
  return apollo.mutate({
    mutation: confgureTransitionMutation,
    variables: {
      input: {
        transition,
        workflow_stage_interaction_id: getInteractionId(context),
        workflow_stage_interaction_transition_id: getDefaultTransitionId(
          context
        ),
      },
    },
  });
}
