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
import context from 'mod_approval/application/index/table/context';
import { getQueryStringParam } from 'tui/util';
import { actions } from 'tui_xstate/xstate';
import { $SHOW_ERROR } from 'tui_xstate/constants';
import {
  MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  TAB,
  MOD_APPROVAL__DASHBOARD_TABLE,
} from 'mod_approval/constants';

const { choose } = actions;

// EVENTS
const CANCEL = 'CANCEL';
const CHANGE_COUNT = 'CHANGE_COUNT';
const CHANGE_PAGE = 'CHANGE_PAGE';
const CLONE_APPLICATION = 'CLONE_APPLICATION';
const CONFIRM_DELETE_APPLICATION = 'CONFIRM_DELETE_APPLICATION';
const DELETE = 'DELETE';
const FILTER = 'FILTER';
const SORT = 'SORT';
const SWITCH_TAB = 'SWITCH_TAB';

function applicationsFromOthers() {
  return {
    initial: 'loading',
    id: 'applications-from-others',
    on: {
      [SWITCH_TAB]: [
        {
          target: `#my-applications.ready`,
          cond: 'hasMyApplications',
        },
        {
          target: `#my-applications.loading`,
        },
      ],
    },
    entry: 'setBaseParamsOthers',

    states: {
      loading: {
        entry: choose([{ cond: 'hasNotify', actions: 'showNotify' }]),
        invoke: {
          id: `load_${APPLICATIONS_FROM_OTHERS}`,
          src: APPLICATIONS_FROM_OTHERS,
          onDone: 'ready',
          onError: {
            target: '#applications-from-others.ready',
            actions: $SHOW_ERROR,
          },
        },
        exit: choose([{ cond: 'hasNotify', actions: 'unsetNotify' }]),
      },

      querying: {
        entry: choose([
          { cond: 'setQueryOptionsEvent', actions: 'setBaseParamsOthers' },
        ]),
        invoke: {
          id: `query_${APPLICATIONS_FROM_OTHERS}`,
          src: APPLICATIONS_FROM_OTHERS,
          onDone: 'ready',
          onError: {
            target: '#applications-from-others.ready',
            actions: $SHOW_ERROR,
          },
        },
      },

      ready: {
        on: {
          [CHANGE_PAGE]: {
            target: 'querying',
            actions: 'setQueryOptions',
          },
          [CHANGE_COUNT]: {
            target: 'querying',
            actions: ['setQueryOptions', 'setPageTo1'],
          },
          [FILTER]: {
            target: 'querying',
            actions: ['setQueryOptions', 'setPageTo1'],
          },
          [SORT]: {
            target: 'querying',
            actions: ['setQueryOptions', 'setPageTo1'],
          },
          [CONFIRM_DELETE_APPLICATION]: {
            target: 'confirmDelete',
            actions: 'setApplicationToDelete',
          },
          [CLONE_APPLICATION]: 'cloning',
        },
      },

      cloning: {
        invoke: {
          id: 'cloningApplication',
          src: 'cloningApplication',
          onDone: { actions: 'navigateToCloned' },
          onError: {
            target: '#applications-from-others.ready',
            actions: 'errorNotify',
          },
        },
      },

      confirmDelete: {
        on: {
          [DELETE]: 'deletingApplication',
          [CANCEL]: '#applications-from-others.ready',
        },
      },
      deletingApplication: {
        invoke: {
          id: 'deleteApplication',
          src: 'deleteApplication',
          onDone: {
            target: '#applications-from-others.ready',
            actions: ['deletedNotify', 'removeFromOthersApplication'],
          },
          onError: {
            target: '#applications-from-others.ready',
            actions: 'errorNotify',
          },
        },
      },
    },
  };
}

/**
 * Generates the init state always transitions.
 *
 * @param {Boolean} canApprove
 */
function initAlwaysTransitions(canApprove) {
  const defaultState = canApprove ? APPLICATIONS_FROM_OTHERS : MY_APPLICATIONS;
  const specifiedTab = getQueryStringParam(TAB);
  const alwaysTransitions = [
    {
      target: defaultState,
      cond: { type: 'invalidTab', specifiedTab },
    },
    {
      target: 'myApplications',
      cond: {
        type: 'canViewMyApplicationsTab',
        canApprove,
        specifiedTab,
      },
    },
  ];

  if (canApprove) {
    alwaysTransitions.push({
      target: 'applicationsFromOthers',
      cond: {
        type: 'canViewOtherApplicationsTab',
        canApprove,
        specifiedTab,
      },
    });
  }

  return alwaysTransitions;
}

export default function makeState({ canApprove }) {
  const state = {
    id: MOD_APPROVAL__DASHBOARD_TABLE,
    peserveActionOrder: true,
    initial: 'init',
    context,

    states: {
      init: {
        always: initAlwaysTransitions(canApprove),
      },
      myApplications: {
        id: 'my-applications',
        initial: 'loading',

        on: {
          [SWITCH_TAB]: canApprove
            ? [
                {
                  target: `#applications-from-others.ready`,
                  cond: 'hasApplications',
                },
                {
                  target: `#applications-from-others.querying`,
                },
              ]
            : undefined,
        },
        entry: 'setBaseParamsMy',

        states: {
          loading: {
            id: 'loading',
            entry: choose([{ cond: 'hasNotify', actions: 'showNotify' }]),
            invoke: {
              id: `load_${MY_APPLICATIONS}`,
              src: MY_APPLICATIONS,
              onDone: 'ready',
              onError: {
                target: '#my-applications.ready',
                actions: $SHOW_ERROR,
              },
            },
            exit: choose([{ cond: 'hasNotify', actions: 'unsetNotify' }]),
          },

          querying: {
            id: 'querying',
            entry: choose([
              { cond: 'setQueryOptionsEvent', actions: 'setBaseParamsMy' },
            ]),
            invoke: {
              id: `query_${MY_APPLICATIONS}`,
              src: MY_APPLICATIONS,
              onDone: 'ready',
              onError: {
                target: '#my-applications.ready',
                actions: $SHOW_ERROR,
              },
            },
          },

          ready: {
            on: {
              [CHANGE_PAGE]: {
                target: 'querying',
                actions: 'setQueryOptions',
              },
              [CHANGE_COUNT]: {
                target: 'querying',
                actions: ['setQueryOptions', 'setPageTo1'],
              },
              [SORT]: {
                target: 'querying',
                actions: ['setQueryOptions', 'setPageTo1'],
              },
              [FILTER]: {
                target: 'querying',
                actions: ['setQueryOptions', 'setPageTo1'],
              },
              [CONFIRM_DELETE_APPLICATION]: {
                target: 'confirmDelete',
                actions: 'setApplicationToDelete',
              },
              [CLONE_APPLICATION]: 'cloning',
            },
          },

          cloning: {
            invoke: {
              src: 'cloningApplication',
              onDone: { actions: 'navigateToCloned' },
              onError: {
                target: '#my-applications.ready',
                actions: 'errorCloningNotify',
              },
            },
          },

          confirmDelete: {
            on: {
              [DELETE]: 'deletingApplication',
              [CANCEL]: '#my-applications.ready',
            },
          },
          deletingApplication: {
            invoke: {
              id: 'deleteApplication',
              src: 'deleteApplication',
              onDone: {
                target: '#my-applications.ready',
                actions: ['deletedNotify', 'removeFromMyApplications'],
              },
              onError: {
                target: '#my-applications.ready',
                actions: 'errorNotify',
              },
            },
          },
        },
      },
    },
  };

  if (canApprove) {
    state.states.applicationsFromOthers = applicationsFromOthers();
  }

  return state;
}
