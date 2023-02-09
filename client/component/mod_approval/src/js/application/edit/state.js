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
  MOD_APPROVAL__APPLICATION,
  MOD_APPROVAL__SCROLL_MACHINE,
  LOAD_APPLICATION,
  APPLICATION_FORM_SCHEMA,
} from 'mod_approval/constants';
import createContext from 'mod_approval/application/edit/context';
import { actions } from 'tui_xstate/xstate';
import { SET_SECTIONS } from '../scroll/state';

// EVENTS
const CONFIRM_SAVE = 'CONFIRM_SAVE';
const TOGGLE_KEEP_APPROVALS = 'TOGGLE_KEEP_APPROVALS';
const CANCEL = 'CANCEL';
const DELETE = 'DELETE';
const NAVIGATE_TO_SECTION = 'NAVIGATE_TO_SECTION';
const SAVE = 'SAVE';
const SUBMIT = 'SUBMIT';
const UPDATE_FORM_DATA = 'UPDATE_FORM_DATA';
const VALIDATION_CHANGED = 'VALIDATION_CHANGED';
const SET_REF_METHODS = 'SET_REF_METHODS';
const FORM_READY = 'FORM_READY';
const CLONE_APPLICATION = 'CLONE_APPLICATION';
const CONFIRM_DELETE_APPLICATION = 'CONFIRM_DELETE_APPLICATION';
const DISMISS_NOTIFICATION = 'DISMISS_NOTIFICATION';
const WITHDRAW = 'WITHDRAW';
const WITHDRAW_APPLICATION = 'WITHDRAW_APPLICATION';
const UPDATE_ACTIVE_SECTION = 'UPDATE_ACTIVE_SECTION';

const { choose, send } = actions;

export default function createState({ loadApplicationResult }) {
  return {
    id: MOD_APPROVAL__APPLICATION,
    initial: 'loading',
    on: {
      [SET_REF_METHODS]: {
        actions: [
          'validate',
          'setRefMethods',
          send(
            (context, { sectionIds }) => ({
              type: SET_SECTIONS,
              sectionIds,
            }),
            { to: MOD_APPROVAL__SCROLL_MACHINE }
          ),
        ],
      },
      [NAVIGATE_TO_SECTION]: {
        actions: [
          choose([
            { cond: 'preventScrollSupported', actions: 'focusRefNormal' },
            { actions: 'focusRefPolyfill' },
          ]),
          choose([
            {
              cond: 'prefersReducedMotionNoPreference',
              actions: 'smoothScrollToRef',
            },
            { actions: 'abruptScrollToRef' },
          ]),
        ],
      },
      [UPDATE_ACTIVE_SECTION]: {
        actions: 'updateActiveSection',
        cond: 'sectionChanged',
      },
      [UPDATE_FORM_DATA]: {
        actions: [
          'updateFormData',
          'setUnsaved',
          'addBeforeUnloadEventListener',
        ],
      },
      [VALIDATION_CHANGED]: { actions: 'updateValidationErrors' },
      [DISMISS_NOTIFICATION]: { actions: 'dismissNotification' },

      // targets defined in commonStates
      [CLONE_APPLICATION]: 'cloningApplication',
      [WITHDRAW_APPLICATION]: 'confirmWithdraw',
      [CONFIRM_DELETE_APPLICATION]: 'confirmDelete',
    },
    context: createContext({ loadApplicationResult }),
    states: {
      loading: {
        entry: [
          'spawnScrollmachine',
          choose([{ cond: 'hasNotify', actions: 'showNotify' }]),
        ],
        initial: 'queryLoading',
        states: {
          queryLoading: {
            invoke: {
              src: {
                type: $QUERY_ALL,
                queries: [LOAD_APPLICATION, APPLICATION_FORM_SCHEMA],
              },
              onDone: 'preparing',
            },
          },
          preparing: {
            invoke: {
              id: 'prepare',
              src: 'prepare',
              onDone: {
                actions: 'setupFormData',
                // <SchemaForm /> has its own loading state
                // So we hook into that and wait for the FORM_READY event below
                target: 'formLoading',
              },
            },
          },
          formLoading: {
            on: {
              [FORM_READY]: '#ready',
            },
            exit: choose([{ cond: 'hasNotify', actions: 'unsetNotify' }]),
          },
        },
      },

      ready: {
        id: 'ready',
        entry: choose([
          { cond: 'fromDashboard', actions: 'stripDashboardParams' },
        ]),
        meta: {
          defaultErrorTarget: true,
        },
        on: {
          [SUBMIT]: 'confirmApplicationSubmission',
          [SAVE]: [
            { actions: ['emptyNotify', 'scrollToTop'], cond: 'isFormEmpty' },
            { actions: ['noChangesNotify', 'scrollToTop'], cond: 'isSaved' },
            { target: 'savingDraftApplication', cond: 'isDraft' },
            { target: 'confirmSavingApplication' },
          ],
          [CANCEL]: { actions: 'navigateToSubmitted' },
        },

        /* machine recieves a VALIDATION_CHANGED event from SchemaForm component
         * This updates validationErrors and triggers the 'always' cond: 'isFormInvalid' test below
         */

        always: {
          cond: 'isFormInvalid',
          target: 'invalid',
        },
      },

      invalid: {
        on: {
          [SAVE]: [
            { actions: ['emptyNotify', 'scrollToTop'], cond: 'isFormEmpty' },
            { actions: ['noChangesNotify', 'scrollToTop'], cond: 'isSaved' },
            {
              actions: ['trySubmit', 'focusFirstInvalid'],
              cond: 'isInApprovals',
            },
            { target: 'savingDraftApplication', cond: 'isDraft' },
            { target: 'confirmSavingApplication' },
          ],
          [SUBMIT]: { actions: ['trySubmit', 'focusFirstInvalid'] },
          [CANCEL]: { actions: 'navigateToSubmitted' },
        },

        /* machine recieves a VALIDATION_CHANGED event from SchemaForm component
         * This updates validationErrors and triggers the 'always' cond: 'isFormValid' test below
         */

        always: {
          cond: 'isFormValid',
          target: 'ready',
        },
      },

      savingDraftApplication: {
        invoke: {
          src: 'saveAsDraftApplication',
          onDone: {
            target: 'ready',
            actions: [
              'savedNotify',
              'setSaved',
              'dismissNotification',
              'removeBeforeUnloadListener',
            ],
          },
        },
      },

      confirmSavingApplication: {
        initial: 'ready',
        states: {
          ready: {
            on: {
              [CONFIRM_SAVE]: [
                {
                  target: 'savingApplication',
                  actions: 'removeBeforeUnloadListener',
                },
              ],
              [TOGGLE_KEEP_APPROVALS]: { actions: ['updateKeepApprovals'] },
              [CANCEL]: '#ready',
            },
          },

          savingApplication: {
            invoke: {
              src: 'saveApplication',
              onDone: [
                {
                  cond: 'isInApprovals',
                  actions: [
                    'dismissNotification',
                    'removeBeforeUnloadListener',
                    'setSaved',
                    'savedNotify',
                    'resetKeepApprovals',
                    'navigateToSubmittedSuccess',
                  ],
                },
                {
                  target: '#ready',
                  actions: [
                    'dismissNotification',
                    'removeBeforeUnloadListener',
                    'setSaved',
                    'savedNotify',
                    'resetKeepApprovals',
                  ],
                },
              ],
            },
          },
        },
      },

      confirmApplicationSubmission: {
        on: {
          [SUBMIT]: {
            target: 'submittingApplication',
            actions: 'removeBeforeUnloadListener',
          },
          [CANCEL]: '#ready',
        },
      },

      submittingApplication: {
        invoke: {
          src: 'submitApplication',
          onDone: { actions: 'navigateToSubmittedSuccess' },
          onError: { target: '#ready', actions: 'errorNotify' },
        },
      },

      confirmWithdraw: {
        initial: 'ready',
        states: {
          ready: {
            on: {
              [WITHDRAW]: 'withdrawingApplication',
              [CANCEL]: '#ready',
            },
          },
          withdrawingApplication: {
            invoke: {
              src: 'withdrawApplication',
              onDone: {
                target: '#ready',
                actions: ['withdrawnNotify', 'dismissNotification'],
              },
            },
          },
        },
      },

      cloningApplication: {
        invoke: {
          src: 'cloneApplication',
          onDone: { actions: 'navigateToClone' },
        },
      },

      confirmDelete: {
        on: {
          [DELETE]: 'deletingApplication',
          [CANCEL]: '#ready',
        },
      },

      deletingApplication: {
        invoke: {
          src: 'deleteApplication',
          onDone: {
            actions: ['removeBeforeUnloadListener', 'navigateToDashboard'],
          },
          onError: { target: 'ready', actions: 'errorNotify' },
        },
      },
    },
  };
}
