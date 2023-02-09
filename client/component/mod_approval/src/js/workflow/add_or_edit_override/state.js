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
import { $QUERY_ALL } from 'tui_xstate/constants';
import {
  MOD_APPROVAL__EDIT_OVERRIDES,
  SELECTABLE_USERS,
  ASSIGNMENT_IDENTIFIERS,
  ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS,
} from 'mod_approval/constants';
import makeContext from './context';

// EVENTS
export const ACTIVATE_TAGLIST = 'ACTIVATE_TAGLIST';
export const CANCEL = 'CANCEL';
export const FILTER = 'FILTER';
export const LOAD_MORE = 'LOAD_MORE';
export const NEXT = 'NEXT';
export const REMOVE = 'REMOVE';
export const SAVE = 'SAVE';
export const SELECT = 'SELECT';
export const SELECT_APPROVER_TYPE = 'SELECT_APPROVER_TYPE';
export const SELECT_ASSIGNMENT_TARGET = 'SELECT_ASSIGNMENT_TARGET';
export const TOGGLE_OVERRIDE = 'TOGGLE_OVERRIDE';

export default function makeState({ workflowId }) {
  return {
    id: MOD_APPROVAL__EDIT_OVERRIDES,
    initial: 'init',
    context: makeContext({ workflowId }),
    on: {
      [CANCEL]: 'cancelled',
      [ACTIVATE_TAGLIST]: {
        actions: [
          'activateTaglist',
          // TODO: TL-32767. Below sets search to '' on Taglist open.
          // This is a workaround to account for a bug in TagList
          'updateUserSearch',
        ],
      },
      [TOGGLE_OVERRIDE]: {
        actions: ['toggleOverride', 'setApproversVariables'],
      },
    },

    states: {
      init: {
        always: [
          { cond: 'hasOverrideAssignment', target: 'loading' },
          { target: 'createAssignment' },
        ],
      },

      createAssignment: {
        initial: 'fetchingDisabledIds',

        states: {
          /*
           * The CreateAssignmentStep will enter this state while the underlying WorkflowDefaultAssignmentPicker
           * is fetching orgs. Technically there could be race conditions where the totara_hierarchy query comes back before the
           * disabledIds query and the assignment targets are momentarily active before being disabled.
           * Therefore the WorkflowDefaultAssignmentPicker's :force-loading prop is set to true while in this state.
           */
          fetchingDisabledIds: {
            invoke: {
              src: ASSIGNMENT_IDENTIFIERS,
              onDone: 'ready',
            },
          },

          ready: {
            on: {
              [SELECT_ASSIGNMENT_TARGET]: [
                {
                  cond: 'hasNotFetchedAssignmentTypeIdentifers',
                  target: 'fetchingDisabledIds',
                  actions: 'setSelectedTarget',
                },
                { actions: 'setSelectedTarget' },
              ],
              [NEXT]: 'creatingAssignment',
            },
          },

          creatingAssignment: {
            invoke: {
              src: 'createOverrideAssignment',
              onDone: {
                target: '#loading',
                actions: ['setOverrideAssignment', 'notifySuccess'],
              },
            },
          },
        },
      },

      loading: {
        entry: 'setFormValues',
        id: 'loading',
        invoke: {
          src: {
            type: $QUERY_ALL,
            queries: [ANCESTOR_ASSIGNMENT_APPROVAL_LEVELS, SELECTABLE_USERS],
          },
          onDone: 'ready',
        },
      },

      ready: {
        on: {
          [SAVE]: [
            { cond: 'approversEmpty', actions: 'displayEmptyErrors' },
            { cond: 'variablesEmpty', target: 'cancelled' },
            { target: 'done' },
          ],
          [FILTER]: { actions: 'updateUserSearch', target: 'debouncing' },
          [LOAD_MORE]: {
            actions: ['updateUserSearch', 'setShouldAppend'],
            target: 'searching',
          },
          [REMOVE]: { actions: ['removeApprover', 'setApproversVariables'] },
          [SELECT]: { actions: ['selectApprover', 'setApproversVariables'] },
          [SELECT_APPROVER_TYPE]: {
            actions: ['selectApproverType', 'setApproversVariables'],
          },
        },
      },

      debouncing: {
        on: {
          [FILTER]: { target: 'debouncing', actions: 'updateUserSearch' },
        },
        after: {
          500: 'searching',
        },
      },

      searching: {
        on: {
          [FILTER]: { target: 'debouncing', actions: 'updateUserSearch' },
        },
        invoke: {
          src: SELECTABLE_USERS,
          onDone: [
            {
              cond: 'shouldAppend',
              actions: 'unsetShouldAppend',
              target: 'ready',
            },
            { target: 'ready' },
          ],
        },
      },

      done: {
        type: 'final',
        data: ({ approversVariables }) => {
          return {
            approversVariables,
          };
        },
      },

      cancelled: {
        id: 'cancelled',
        type: 'final',
        meta: { defaultErrorTarget: true },
        data: ({ isAdd, overrideAssignment }) => {
          return {
            hasAddedOverride: isAdd && Boolean(overrideAssignment),
          };
        },
      },
    },
  };
}
