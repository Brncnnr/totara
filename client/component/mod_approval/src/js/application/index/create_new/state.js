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
import { $CANCEL_QUERY } from 'tui_xstate/constants';
import {
  MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
  SELECTABLE_APPLICANTS,
  CREATE_NEW_APPLICATION_MENU,
} from 'mod_approval/constants';
import makeContext from 'mod_approval/application/index/create_new/context';

// EVENTS
const BACK = 'BACK';
const CANCEL = 'CANCEL';
const CREATE = 'CREATE';
const FILTER = 'FILTER';
const NEXT = 'NEXT';
const REMOVE = 'REMOVE';
const SELECT = 'SELECT';
const MORE = 'MORE';

export default function makeState({ currentUserId }) {
  return {
    id: MOD_APPROVAL__CREATE_NEW_APPLICATION_MODAL,
    context: makeContext({ currentUserId }),
    initial: 'init',
    states: {
      init: {
        // automatic transition
        always: [
          { target: 'selectType', cond: 'hasMultipleWorkflowTypes' },
          { target: 'selectJobAssignment', cond: 'forYourself' },
          { target: 'selectUser' },
        ],
      },
      selectType: {
        initial: 'init',
        states: {
          init: {
            always: [
              { target: 'loading', cond: 'forYourself' },
              { target: 'ready' },
            ],
          },
          ready: {
            on: {
              [SELECT]: [
                {
                  target: 'loading',
                  cond: 'forYourself',
                  actions: 'setSelectedWorkflowType',
                },
                {
                  actions: ['setSelectedWorkflowType', 'unsetSelectedUser'],
                },
              ],
              [NEXT]: [
                { target: '#selectJobAssignment', cond: 'forYourself' },
                { target: '#selectUser' },
              ],
              [CREATE]: '#done',
              [CANCEL]: '#cancelled',
            },
          },
          loading: {
            invoke: {
              id: 'loadingJobAssignments',
              src: CREATE_NEW_APPLICATION_MENU,
              onDone: { target: 'ready' },
            },
          },
        },
      },
      selectUser: {
        id: 'selectUser',
        initial: 'searching',
        on: {
          [CANCEL]: 'cancelled',
          [BACK]: 'selectType',
          [SELECT]: {
            target: '.checkingPermission',
            actions: ['setSelectedUser', 'removeSearch', 'removeUsers'],
          },
          [REMOVE]: {
            target: '.searching',
            actions: 'setSelectedUser',
          },
        },

        states: {
          ready: {
            always: [{ target: 'selected', cond: 'hasSelectedUser' }],
            on: {
              [FILTER]: [
                {
                  actions: 'updateVariables',
                  cond: 'emptySearchWithExisting',
                },
                { target: 'debouncing', actions: 'updateVariables' },
              ],
              [MORE]: {
                cond: 'moreUsers',
                target: 'searching',
                actions: ['setShouldAppend', 'updateVariables'],
              },
            },
          },

          debouncing: {
            on: {
              [FILTER]: { target: 'debouncing', actions: 'updateVariables' },
            },
            after: {
              500: 'searching',
            },
          },

          searching: {
            on: {
              [FILTER]: {
                target: 'debouncing',
                actions: [
                  'updateVariables',
                  // FILTER event includes { queryId: SELECTABLE_APPLICANTS } key:value to
                  // tell Tui XState plugin to unsubscribe from this in-flight query.
                  $CANCEL_QUERY,
                ],
              },
            },

            invoke: {
              src: SELECTABLE_APPLICANTS,
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

          checkingPermission: {
            invoke: {
              id: 'checkingPermission',
              src: CREATE_NEW_APPLICATION_MENU,
              onDone: [
                { target: 'selected', cond: 'hasJobAssignments' },
                { target: 'noPermission' },
              ],
            },
          },

          noPermission: {
            on: {
              // selectedUser: null
              [REMOVE]: {
                target: 'searching',
                actions: 'setSelectedUser',
              },
            },
          },

          selected: {
            on: {
              [NEXT]: '#selectJobAssignment',
              [CREATE]: '#done',
            },
          },
        },
      },

      selectJobAssignment: {
        id: 'selectJobAssignment',
        on: {
          [SELECT]: { actions: 'setSelectedJobAssignment' },
          [CREATE]: 'done',
          [CANCEL]: 'cancelled',
          [BACK]: [
            { target: 'selectType.ready', cond: 'forYourself' },
            { target: 'selectUser' },
          ],
        },
      },

      done: {
        id: 'done',
        type: 'final',
        // pass the selected data back to dashboard_machine
        // which will create the application
        data: {
          selectedUser: context => context.selectedUser,
          selectedJobAssignment: context => context.selectedJobAssignment,
          selectedWorkflowType: context => context.selectedWorkflowType,
        },
      },

      cancelled: {
        id: 'cancelled',
        type: 'final',
        meta: {
          defaultErrorTarget: true,
        },
      },
    },
  };
}
