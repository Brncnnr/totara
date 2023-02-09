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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_approval
 */

import {
  OVERRIDE_ASSIGNMENTS,
  SELECTABLE_USERS,
  MOD_APPROVAL__EDIT_OVERRIDES,
  WORKFLOW_STAGE_MOVE_TO,
} from 'mod_approval/constants';
import {
  getActiveStageId,
  getApproverTypes,
} from 'mod_approval/workflow/edit/selectors';
import { getWorkflowId } from 'mod_approval/graphql_selectors/load_workflow';
import addOrEditOverrideContext from 'mod_approval/workflow/add_or_edit_override/context';
import { actions, send } from 'tui_xstate/xstate';
import initialActiveSubsection from './initial_active_subsection';

const { choose } = actions;

// EVENTS
const ADD_OVERRIDE = 'ADD_OVERRIDE';
const BACK = 'BACK';
const CANCEL = 'CANCEL';
const CONFIGURE_OVERRIDES = 'CONFIGURE_OVERRIDES';
const EDIT_OVERRIDES = 'EDIT_OVERRIDES';
const EDIT_TRANSITION = 'EDIT_TRANSITION';
const ARCHIVE_OVERRIDES = 'ARCHIVE_OVERRIDES';
const FILTER = 'FILTER';
const LOAD_MORE_INDIVIDUAL_APPROVERS = 'LOAD_MORE_INDIVIDUAL_APPROVERS';
const PAGE = 'PAGE';
const SAVE_TRANSITION = 'SAVE_TRANSITION';
const SORT = 'SORT';
const TOGGLE_WORKFLOW_STAGE = 'TOGGLE_WORKFLOW_STAGE';
const TO_APPROVALS_SUBSECTION = 'TO_APPROVALS_SUBSECTION';
const TO_NOTIFICATIONS_SUBSECTION = 'TO_NOTIFICATIONS_SUBSECTION';
const TO_INTERACTIONS_SUBSECTION = 'TO_INTERACTIONS_SUBSECTION';
const TO_FORM_SUBSECTION = 'TO_FORM_SUBSECTION';
const VIEW_APPROVERS = 'VIEW_APPROVERS';

export default function({ params, context }) {
  const subsection = initialActiveSubsection({ params, context });

  return {
    initial: 'init',
    on: {
      [TOGGLE_WORKFLOW_STAGE]: [
        {
          target: '.approvals',
          actions: 'updateSelectedWorkflowStageId',
          cond: 'hasApprovalsStage',
        },
        {
          target: '.form',
          actions: 'updateSelectedWorkflowStageId',
          cond: 'hasFormViewsStage',
        },
        {
          target: '.notifications',
          actions: 'updateSelectedWorkflowStageId',
        },
      ],
      [TO_APPROVALS_SUBSECTION]: '.approvals',
      [TO_NOTIFICATIONS_SUBSECTION]: '.notifications',
      [TO_INTERACTIONS_SUBSECTION]: '.interactions',
      [TO_FORM_SUBSECTION]: '.form',
      'done.invoke.deleteWorkflowStage': '.switchSubsection',
    },
    states: {
      init: {
        always: [
          {
            target: 'approvals.overrides',
            cond: 'navigateToOverrides',
          },
          { target: subsection },
        ],
      },
      switchSubsection: {
        always: [
          { target: 'approvals', cond: 'navigateToApprovals' },
          { target: 'form', cond: 'navigateToForm' },
          { target: 'notifications', cond: 'navigateToNotifications' },
        ],
      },
      approvals: {
        initial: 'approvers',
        states: {
          approvers: {
            initial: 'searching',

            on: {
              [CONFIGURE_OVERRIDES]: {
                target: 'overrides',
              },
            },

            states: {
              ready: {
                id: 'approvals-ready',
                meta: {
                  defaultErrorTarget: true,
                },
                on: {
                  [LOAD_MORE_INDIVIDUAL_APPROVERS]: {
                    target: 'searching',
                    actions: ['updateUserSearch', 'setAppendUsers'],
                  },
                  [FILTER]: [
                    { target: 'debouncing', actions: 'updateUserSearch' },
                  ],
                },
              },
              debouncing: {
                on: {
                  [FILTER]: {
                    target: 'debouncing',
                    actions: 'updateUserSearch',
                  },
                },
                after: {
                  500: 'searching',
                },
              },

              searching: {
                on: {
                  [FILTER]: { actions: 'updateUserSearch' },
                },
                invoke: {
                  src: SELECTABLE_USERS,
                  onDone: [
                    {
                      cond: 'shouldAppend',
                      actions: 'unsetAppendUsers',
                      target: 'ready',
                    },
                    { target: 'ready' },
                  ],
                },
              },
            },
          },
          overrides: {
            entry: choose([
              { cond: 'noActiveVariables', actions: 'setActiveVariables' },
            ]),
            initial: 'loading',
            on: {
              [BACK]: {
                target: 'approvers',
              },
              [ADD_OVERRIDE]: '#add-or-edit-override',
            },
            states: {
              ready: {
                id: 'overrides-ready',
                meta: {
                  defaultErrorTarget: true,
                },
                on: {
                  [SORT]: {
                    target: 'loading',
                    actions: 'setActiveVariables',
                  },
                  [FILTER]: {
                    target: 'debouncing',
                    actions: 'setActiveVariables',
                  },
                  [PAGE]: {
                    target: 'loading',
                    actions: 'setActiveVariables',
                  },
                  [VIEW_APPROVERS]: {
                    target: 'viewApprovers',
                    actions: 'setApprovers',
                  },
                  [EDIT_OVERRIDES]: {
                    target: 'addOrEditOverride',
                  },
                  [ARCHIVE_OVERRIDES]: {
                    target: 'confirmArchiveOverrides',
                    actions: 'setToArchiveOverrides',
                  },
                },
              },

              viewApprovers: {
                on: {
                  [CANCEL]: 'ready',
                },
              },

              addOrEditOverride: {
                id: 'add-or-edit-override',
                on: {
                  [CANCEL]: {
                    actions: send(CANCEL, { to: MOD_APPROVAL__EDIT_OVERRIDES }),
                  },
                },

                invoke: {
                  src: MOD_APPROVAL__EDIT_OVERRIDES,
                  data: (context, event) => {
                    return addOrEditOverrideContext({
                      workflowId: getWorkflowId(context),
                      workflowStageId: getActiveStageId(context),
                      approverTypes: getApproverTypes(context),
                      overrideAssignment: event.overrideAssignment,
                      isAdd: !event.overrideAssignment,
                    });
                  },
                  onDone: [
                    { cond: 'hasAddedOverride', target: 'refetching' },
                    { cond: 'cancelled', target: '#overrides-ready' },
                    { target: 'savingOverrides' },
                  ],
                },
              },

              confirmArchiveOverrides: {
                on: {
                  [ARCHIVE_OVERRIDES]: 'archivingOverrides',
                  [CANCEL]: 'ready',
                },
              },

              savingOverrides: {
                invoke: {
                  src: 'saveOverrides',
                  onDone: {
                    actions: 'notifySuccess',
                    target: 'refetching',
                  },
                },
              },

              archivingOverrides: {
                invoke: {
                  src: 'archiveOverrides',
                  onDone: {
                    actions: 'notifySuccess',
                    target: 'refetching',
                  },
                  onError: {
                    actions: 'genericErrorNotify',
                    target: 'refetching',
                  },
                },
              },

              refetching: {
                invoke: {
                  id: `refetch_${OVERRIDE_ASSIGNMENTS}`,
                  src: OVERRIDE_ASSIGNMENTS,
                  onDone: 'ready',
                },
              },

              debouncing: {
                on: {
                  [FILTER]: {
                    target: 'debouncing',
                    actions: 'setActiveVariables',
                  },
                },
                after: {
                  500: 'loading',
                },
              },

              loading: {
                invoke: {
                  id: `load_${OVERRIDE_ASSIGNMENTS}`,
                  src: OVERRIDE_ASSIGNMENTS,
                  onDone: 'ready',
                },
              },
            },
          },
        },
      },

      form: {},

      interactions: {
        id: 'interactions-ready',
        initial: 'ready',
        meta: {
          defaultErrorTarget: true,
        },

        states: {
          ready: {
            on: {
              [EDIT_TRANSITION]: {
                target: 'editTransition',
                actions: 'setToEditInteraction',
              },
            },
          },

          editTransition: {
            initial: 'loading',

            on: {
              [CANCEL]: 'ready',
            },

            states: {
              loading: {
                invoke: {
                  src: WORKFLOW_STAGE_MOVE_TO,
                  onDone: 'ready',
                },
              },

              ready: {
                on: {
                  [SAVE_TRANSITION]: 'updatingTransition',
                },
              },

              updatingTransition: {
                invoke: {
                  src: 'updateDefaultTransition',
                  onDone: {
                    actions: 'notifySuccess',
                    target: '#interactions-ready',
                  },
                },
              },
            },
          },
        },
      },

      notifications: {},
    },
  };
}
