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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_approval
 */

import {
  LOAD_WORKFLOW,
  OVERRIDE_FOR_ASSIGNMENT,
  MOD_APPROVAL__WORKFLOW_CLONE,
  MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES,
} from 'mod_approval/constants';
import { workflowSelectors } from 'mod_approval/graphql_selectors';
import { actions, send } from 'tui_xstate/xstate';
import cloneContext from '../../clone/context';
import assignRolesContext from '../../assign_roles/context';
import { getCategoryContextId } from '../selectors';

const { choose } = actions;

export const ADD_APPROVAL_LEVEL = 'ADD_APPROVAL_LEVEL';
export const ADD_WORKFLOW_STAGE = 'ADD_WORKFLOW_STAGE';
export const ARCHIVE = 'ARCHIVE';
export const ASSIGN_ROLES = 'ASSIGN_ROLES';
export const BACK = 'BACK';
export const CANCEL = 'CANCEL';
export const CANCEL_MODAL = 'CANCEL_MODAL';
export const CLONE = 'CLONE';
export const CONFIRM = 'CONFIRM';
export const DELETE = 'DELETE';
export const DELETE_APPROVAL_LEVEL = 'DELETE_APPROVAL_LEVEL';
export const DELETE_WORKFLOW_STAGE = 'DELETE_WORKFLOW_STAGE';
export const EDIT = 'EDIT';
export const RENAME_APPROVAL_LEVEL = 'RENAME_APPROVAL_LEVEL';
export const RENAME_WORKFLOW_STAGE = 'RENAME_WORKFLOW_STAGE';
export const NEXT = 'NEXT';
export const PUBLISH = 'PUBLISH';
export const SELECT_APPROVER_TYPE = 'SELECT_APPROVER_TYPE';
export const REORDER_APPROVAL_LEVEL = 'REORDER_APPROVAL_LEVEL';
export const UNARCHIVE = 'UNARCHIVE';
export const UPDATE_APPROVAL_LEVEL_APPROVERS =
  'UPDATE_APPROVAL_LEVEL_APPROVERS';
export const UPDATE_FORMVIEW = 'UPDATE_FORMVIEW';
export const UPDATE_SECTION_VISIBILITY = 'UPDATE_SECTION_VISIBILITY';

const on = {
  [CLONE]: 'clone',
  [RENAME_WORKFLOW_STAGE]: {
    target: 'renameStage',
    actions: 'setRenameWorkflowStageId',
  },
  [RENAME_APPROVAL_LEVEL]: {
    target: 'renameApprovalLevel',
    actions: 'setToEditApprovalLevelId',
  },
  [ARCHIVE]: 'confirmArchive',
  [UNARCHIVE]: 'confirmUnarchive',
  [ASSIGN_ROLES]: 'assignRoles',
  [DELETE]: 'confirmDelete',
  [PUBLISH]: 'confirmPublishWorkflowVersion',
  [UPDATE_APPROVAL_LEVEL_APPROVERS]: {
    target: 'saving',
    actions: [
      'setApprovalLevelApprovers',
      'addToQueue',
      'unsetActiveFullnameSearch',
    ],
  },
  [SELECT_APPROVER_TYPE]: {
    target: 'saving',
    actions: ['updateSelectedApproverType', 'addToQueue'],
  },
  [REORDER_APPROVAL_LEVEL]: {
    target: 'saving',
    actions: 'reorderApprovalLevel',
  },
  [ADD_APPROVAL_LEVEL]: 'addLevel',
  [ADD_WORKFLOW_STAGE]: 'addStage',
  [DELETE_APPROVAL_LEVEL]: {
    target: 'confirmDeleteLevel',
    actions: 'setToEditApprovalLevelId',
  },
  [DELETE_WORKFLOW_STAGE]: {
    target: 'deleteStage',
    actions: 'setToEditStageId',
  },
  [EDIT]: 'editDetails',
  [UPDATE_FORMVIEW]: {
    target: 'saving',
    actions: ['updateFormviewInCache', 'addFormviewUpdateToQueue'],
  },
  [UPDATE_SECTION_VISIBILITY]: {
    target: 'saving',
    actions: [
      choose([
        { cond: 'hidingSection', actions: 'storeSectionFieldVisibility' },
      ]),
      'updateSectionVisibilityInContext',
      'updateSectionVisibilityInCache',
      choose([
        { cond: 'showingSection', actions: 'setShowSectionVisibilityUpdates' },
        { cond: 'hidingSection', actions: 'setHideSectionVisibilityUpdates' },
      ]),
      'addSectionVisibilityToQueue',
    ],
  },
};

/** @returns {import('xstate').StateNodeConfig} */
export default function() {
  return {
    initial: 'loading',
    states: {
      loading: {
        entry: choose([{ cond: 'hasNotify', actions: 'showNotify' }]),
        invoke: {
          id: `load_${LOAD_WORKFLOW}`,
          src: LOAD_WORKFLOW,
          onDone: 'ready',
        },
        exit: choose([{ cond: 'hasNotify', actions: 'unsetNotify' }]),
      },

      ready: {
        id: 'ready',
        on,

        meta: {
          defaultErrorTarget: true,
        },
      },

      addLevel: {
        on: {
          [ADD_APPROVAL_LEVEL]: {
            target: 'saving',
            actions: 'addOptimisticApprovalLevel',
          },
          [CANCEL]: 'ready',
        },
      },

      confirmDeleteLevel: {
        on: {
          [DELETE_APPROVAL_LEVEL]: {
            target: 'saving',
            actions: 'replaceApprovalLevelWithLoader',
          },
          [CANCEL]: { target: 'ready', actions: 'unsetToEditApprovalLevelId' },
        },
      },

      confirmPublishWorkflowVersion: {
        initial: 'ready',
        states: {
          ready: {
            on: {
              [PUBLISH]: 'publishingWorkflowVersion',
              [CANCEL]: '#ready',
            },
          },

          publishingWorkflowVersion: {
            invoke: {
              src: 'publishWorkflowVersion',
              onDone: {
                target: '#ready',
                actions: 'notifySuccess',
              },
            },
          },
        },
      },

      saving: {
        on: {
          [UPDATE_APPROVAL_LEVEL_APPROVERS]: {
            actions: ['setApprovalLevelApprovers', 'addToQueue'],
          },
          [SELECT_APPROVER_TYPE]: {
            actions: ['updateSelectedApproverType', 'addToQueue'],
          },
          [UPDATE_FORMVIEW]: {
            actions: on[UPDATE_FORMVIEW].actions,
          },
          [UPDATE_SECTION_VISIBILITY]: {
            actions: on[UPDATE_SECTION_VISIBILITY].actions,
          },
        },

        entry: choose([
          { cond: 'mutationQueued', actions: 'setActiveMutation' },
        ]),

        invoke: {
          src: 'saveWorkflow',
          onDone: [
            {
              cond: 'mutationQueued',
              target: 'saving',
              actions: ['mutationDone'],
            },
            { target: 'savedNotify', actions: ['mutationDone'] },
          ],
          onError: 'error',
        },

        exit: ['unsetActiveMutation'],
      },

      savedNotify: {
        entry: choose([
          { cond: 'hasUpdatedStageName', actions: 'updatedNameNotify' },
          { actions: 'savedNotify' },
        ]),
        always: 'ready',
      },

      renameApprovalLevel: {
        on: {
          [RENAME_APPROVAL_LEVEL]: {
            target: 'saving',
            actions: 'updateApprovalLevelName',
          },
          [CANCEL]: 'ready',
        },
      },

      renameStage: {
        on: {
          [RENAME_WORKFLOW_STAGE]: {
            target: 'saving',
            actions: 'updateStageName',
          },
          [CANCEL]: 'ready',
        },
      },

      addStage: {
        on: {
          [ADD_WORKFLOW_STAGE]: 'addingStage',
          [CANCEL]: 'ready',
        },
      },

      addingStage: {
        initial: 'creatingStage',
        states: {
          creatingStage: {
            invoke: {
              src: 'addWorkflowStage',
              onDone: {
                target: '#ready',
                actions: [
                  'setNewWorkflowStagePending',
                  'addStageExtendedContext',
                  'doPendingStageSwitch',
                  'savedNotify',
                ],
              },
            },
          },
        },
      },

      deleteStage: {
        on: {
          [CONFIRM]: {
            target: 'deletingStage',
            actions: choose([
              {
                cond: 'editingActiveStage',
                actions: 'setPendingSwitchToOtherStage',
              },
            ]),
          },
          [CANCEL]: {
            target: 'ready',
            actions: 'unsetToEditStageId',
          },
        },
      },

      deletingStage: {
        invoke: {
          src: 'deleteWorkflowStage',
          onDone: {
            target: 'ready',
            actions: [
              choose([
                {
                  cond: 'hasPendingStageSwitch',
                  actions: 'doPendingStageSwitch',
                },
              ]),
              'removeDeletedWorkflowStage',
              'savedNotify',
            ],
          },
        },
        exit: ['unsetToEditStageId'],
      },

      clone: {
        on: {
          [CANCEL_MODAL]: {
            actions: send(CANCEL, {
              to: MOD_APPROVAL__WORKFLOW_CLONE,
            }),
          },
        },
        invoke: {
          src: MOD_APPROVAL__WORKFLOW_CLONE,
          onDone: [
            { target: 'cloning', cond: 'hasCloneData' },
            { target: 'ready' },
          ],
          data: context =>
            cloneContext({
              workflow: workflowSelectors.getWorkflow(context),
              contextId: getCategoryContextId(context),
            }),
        },
      },

      cloning: {
        invoke: {
          src: 'cloneWorkflow',
          onDone: {
            actions: 'navigateToClone',
          },
        },
      },

      confirmArchive: {
        initial: 'ready',
        states: {
          ready: {
            on: {
              [ARCHIVE]: 'archiving',
              [CANCEL]: '#ready',
            },
          },

          archiving: {
            invoke: {
              src: 'archiveWorkflow',
              onDone: {
                target: '#ready',
                actions: 'notifySuccess',
              },
            },
          },
        },
      },

      confirmUnarchive: {
        initial: 'ready',
        states: {
          ready: {
            on: {
              [UNARCHIVE]: 'unarchiving',
              [CANCEL]: '#ready',
            },
          },

          unarchiving: {
            invoke: {
              src: 'unarchiveWorkflow',
              onDone: {
                target: '#ready',
                actions: 'notifySuccess',
              },
            },
          },
        },
      },

      confirmDelete: {
        on: {
          [DELETE]: 'deleting',
          [CANCEL]: '#ready',
        },
      },

      deleting: {
        invoke: {
          src: 'deleteWorkflow',
          onDone: {
            actions: 'navigateToDashboard',
          },
          onError: {
            target: '#ready',
            actions: 'genericErrorNotify',
          },
        },
      },

      editDetails: {
        initial: 'ready',
        states: {
          ready: {
            on: {
              [EDIT]: 'updating',
              [CANCEL]: '#ready',
            },
          },

          updating: {
            invoke: {
              src: 'editWorkflow',
              onDone: {
                target: '#ready',
                actions: 'notifySuccess',
              },
            },
          },
        },
      },

      assignRoles: {
        on: {
          [CANCEL_MODAL]: {
            actions: send(CANCEL, {
              to: MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES,
            }),
          },
        },
        invoke: {
          src: MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES,
          onDone: [
            {
              target: 'preparingApprovalOverrideToAssignRoles',
              actions: 'setAssignRolesTargetAssignment',
              cond: 'assignRolesInApprovalOverride',
            },
            {
              target: '#ready',
              actions: 'navigateToAssignRolesInWorkflow',
              cond: 'assignRolesInWorkflow',
            },
            {
              target: '#ready',
            },
          ],
          data: context =>
            assignRolesContext({
              contextId: getCategoryContextId(context),
              workflow: workflowSelectors.getWorkflow(context),
            }),
        },
      },

      preparingApprovalOverrideToAssignRoles: {
        initial: 'checkOverride',
        states: {
          checkOverride: {
            invoke: {
              src: OVERRIDE_FOR_ASSIGNMENT,
              onDone: [
                {
                  target: '#ready',
                  actions: 'navigateToAssignRolesInExistingApprovalOverride',
                  cond: 'existingOverrideForAssignment',
                },
                {
                  target: 'createOverride',
                },
              ],
            },
          },

          createOverride: {
            invoke: {
              src: 'createApprovalOverrideToAssignRoles',
              onDone: [
                {
                  target: '#ready',
                  actions: 'navigateToAssignRolesInNewApprovalOverride',
                },
              ],
            },
          },
        },
      },

      error: {},
    },
  };
}
