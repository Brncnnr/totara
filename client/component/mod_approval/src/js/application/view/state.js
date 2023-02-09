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
  APPLICATION_FORM_SCHEMA,
  MOD_APPROVAL__APPLICATION_VIEW,
  LOAD_APPLICATION,
  LOAD_APPLICATION_ACTIVITIES,
  APPLICATION_APPROVERS,
} from 'mod_approval/constants';
import createContext from 'mod_approval/application/view/context';
import { actions } from 'tui_xstate/xstate';

//EVENTS
const APPROVE_APPLICATION = 'APPROVE_APPLICATION';
const CANCEL = 'CANCEL';
const COMMENTS_UPDATED = 'COMMENTS_UPDATED';
const CONFIRM = 'CONFIRM';
const DELETE = 'DELETE';
const REJECT_APPLICATION = 'REJECT_APPLICATION';
const SHOW_ALL_APPROVERS = 'SHOW_ALL_APPROVERS';
const SWITCH_TAB = 'SWITCH_TAB';
const SET_HAS_REJECTION_COMMENT = 'SET_HAS_REJECTION_COMMENT';
const CLONE_APPLICATION = 'CLONE_APPLICATION';
const CONFIRM_DELETE_APPLICATION = 'CONFIRM_DELETE_APPLICATION';
const DISMISS_NOTIFICATION = 'DISMISS_NOTIFICATION';
const WITHDRAW_APPLICATION = 'WITHDRAW_APPLICATION';
const WITHDRAW = 'WITHDRAW';
const SCHEMA_READY = 'SCHEMA_READY';

const { choose } = actions;

export default function createState({ loadApplicationResult, currentUser }) {
  const loadingQueries = [LOAD_APPLICATION, APPLICATION_FORM_SCHEMA];
  const sidePanelInitial = loadApplicationResult.application
    .current_approval_level
    ? 'loading'
    : 'actionsTab';

  return {
    id: MOD_APPROVAL__APPLICATION_VIEW,
    type: 'parallel',
    context: createContext({ loadApplicationResult, currentUser }),
    on: {
      [DISMISS_NOTIFICATION]: { actions: 'dismissNotification' },
    },
    states: {
      sidePanel: {
        initial: sidePanelInitial,
        on: {
          [SWITCH_TAB]: [
            { target: '.activityTab', cond: 'gotToActivity' },
            { target: '.commentsTab', cond: 'gotToComments' },
            { target: '.actionsTab' },
          ],
        },
        states: {
          loading: {
            invoke: {
              src: APPLICATION_APPROVERS,
              onDone: 'actionsTab',
            },
          },

          activityTab: {
            initial: 'init',
            states: {
              init: {
                always: [
                  { cond: 'emptyActivity', target: 'loading' },
                  { target: 'ready' },
                ],
              },

              loading: {
                invoke: {
                  src: LOAD_APPLICATION_ACTIVITIES,
                  onDone: 'ready',
                },
              },

              ready: {},
            },
          },

          commentsTab: {
            on: {
              // the LOAD_APPLICATION_ACTIVITIES query refetch is triggered here as side-effect
              // rather than a state because the activity is in the not currently visible activityTab
              [COMMENTS_UPDATED]: { actions: 'refetchApplicationActivity' },
            },
          },

          actionsTab: {
            id: 'actions-tab',
            initial: 'ready',
            meta: { defaultErrorTarget: true },
            states: {
              ready: {
                id: 'actions-ready',
                on: {
                  [SHOW_ALL_APPROVERS]: { target: 'viewApproversOpen' },
                  [APPROVE_APPLICATION]: 'approvingApplication',
                  [REJECT_APPLICATION]: 'confirmReject',
                },
              },

              confirmReject: {
                initial: 'ready',
                states: {
                  ready: {
                    on: {
                      [CONFIRM]: 'submitting',
                      [CANCEL]: '#actions-ready',
                      [SET_HAS_REJECTION_COMMENT]: {
                        actions: 'setHasRejectionComment',
                      },
                    },
                  },

                  submitting: {
                    initial: 'creatingComment',
                    states: {
                      creatingComment: {
                        invoke: {
                          id: 'createComment',
                          src: 'createComment',
                          onDone: {
                            target: 'rejectingApplication',
                            actions: 'writeOrFetchComments',
                          },
                          onError: {
                            target: '#actions-ready',
                            actions: 'errorNotify',
                          },
                        },
                      },
                      rejectingApplication: {
                        invoke: {
                          id: 'rejectApplication',
                          src: 'rejectApplication',
                          onDone: {
                            target: '#actions-ready',
                            // refetch activity in the background
                            actions: [
                              'successNotify',
                              'refetchApplicationActivity',
                            ],
                          },
                          onError: {
                            target: '#actions-ready',
                            actions: 'errorNotify',
                          },
                        },
                      },
                    },
                  },
                },
              },

              approvingApplication: {
                initial: 'approving',
                states: {
                  approving: {
                    entry: choose([
                      {
                        cond: 'isFinalApprovalLevel',
                        actions: 'setRefetchSchema',
                      },
                    ]),
                    invoke: {
                      id: 'approveApplication',
                      src: 'approveApplication',
                      onDone: [
                        {
                          target: 'refetchingSchema',
                          // refetch activity in the background
                          actions: [
                            'successNotify',
                            'refetchApplicationActivity',
                          ],
                          cond: context => context.refetchSchema,
                        },
                        {
                          target: '#actions-ready',
                          // refetch activity in the background
                          actions: [
                            'successNotify',
                            'refetchApplicationActivity',
                          ],
                        },
                      ],
                      onError: {
                        target: '#actions-ready',
                        actions: 'errorNotify',
                      },
                    },
                  },
                  refetchingSchema: {
                    initial: 'refetching',
                    exit: 'unsetRefetchSchema',
                    states: {
                      refetching: {
                        invoke: {
                          id: `refetch_${APPLICATION_FORM_SCHEMA}`,
                          src: APPLICATION_FORM_SCHEMA,
                          onDone: 'preparing',
                        },
                      },
                      preparing: {
                        invoke: {
                          id: 'refetchPrepare',
                          src: 'prepare',
                          onDone: {
                            actions: 'setupFormData',
                            target: '#actions-ready',
                          },
                        },
                      },
                    },
                  },
                },
              },

              viewApproversOpen: {
                on: {
                  [CANCEL]: '#actions-ready',
                },
              },
            },
          },
        },
      },

      application: {
        initial: 'loading',
        // targets defined in commonStates
        on: {
          [CLONE_APPLICATION]: '.cloningApplication',
          [WITHDRAW_APPLICATION]: '.confirmWithdraw',
          [CONFIRM_DELETE_APPLICATION]: '.confirmDelete',
        },
        states: {
          loading: {
            entry: choose([{ cond: 'hasNotify', actions: 'showNotify' }]),
            initial: 'queryLoading',
            on: {
              [SCHEMA_READY]: { actions: 'setSchemaReady' },
            },
            states: {
              queryLoading: {
                invoke: {
                  id: 'loadingQueries',
                  src: {
                    type: $QUERY_ALL,
                    queries: loadingQueries,
                  },
                  onDone: 'preparing',
                },
              },
              preparing: {
                invoke: {
                  id: 'prepare',
                  src: 'prepare',
                  onDone: [
                    {
                      actions: 'setupFormData',
                      target: '#application-ready',
                      cond: 'isSchemaReady',
                    },
                    {
                      actions: 'setupFormData',
                      target: 'schemaLoading',
                    },
                  ],
                },
              },

              schemaLoading: {
                on: {
                  [SCHEMA_READY]: '#application-ready',
                },
              },
            },
            exit: choose([{ cond: 'hasNotify', actions: 'unsetNotify' }]),
          },

          ready: {
            entry: choose([
              { cond: 'fromDashboard', actions: 'stripDashboardParams' },
            ]),
            id: 'application-ready',
            meta: { defaultErrorTarget: true },
          },

          confirmWithdraw: {
            initial: 'ready',
            states: {
              ready: {
                on: {
                  [WITHDRAW]: 'withdrawingApplication',
                  [CANCEL]: '#application-ready',
                },
              },
              withdrawingApplication: {
                invoke: {
                  src: 'withdrawApplication',
                  onDone: {
                    target: '#application-ready',
                    actions: [
                      'refetchApplicationActivity',
                      'withdrawnNotify',
                      'dismissNotification',
                    ],
                  },
                  onError: {
                    target: '#application-ready',
                    actions: 'errorNotify',
                  },
                },
              },
            },
          },

          cloningApplication: {
            invoke: {
              src: 'cloneApplication',
              onDone: { actions: 'navigateToClone' },
              onError: { target: '#application-ready', actions: 'errorNotify' },
            },
          },

          confirmDelete: {
            initial: 'ready',
            states: {
              ready: {
                on: {
                  [DELETE]: 'deletingApplication',
                  [CANCEL]: '#application-ready',
                },
              },
              deletingApplication: {
                invoke: {
                  src: 'deleteApplication',
                  onDone: {
                    target: '#application-ready',
                    actions: [
                      'removeBeforeUnloadListener',
                      'navigateToDashboard',
                    ],
                  },
                  onError: {
                    target: '#application-ready',
                    actions: 'errorNotify',
                  },
                },
              },
            },
          },
        },
      },
    },
  };
}
