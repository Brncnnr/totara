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

import { send } from 'tui_xstate/xstate';
import {
  MOD_APPROVAL__WORKFLOW_DASHBOARD,
  MANAGEABLE_WORKFLOWS,
  MOD_APPROVAL__WORKFLOW_CLONE,
  MOD_APPROVAL__WORKFLOW_CREATE,
} from 'mod_approval/constants';
import { getToMutateWorkflow } from './selectors';
import context from './context';
import cloneContext from '../clone/context';

// EVENTS
const CLONE = 'CLONE';
const ARCHIVE = 'ARCHIVE';
const CANCEL = 'CANCEL';
const CANCEL_MODAL = 'CANCEL_MODAL';
const CHANGE_PAGINATION = 'CHANGE_PAGINATION';
const CREATE_WORKFLOW = 'CREATE_WORKFLOW';
const DELETE = 'DELETE';
const FILTER = 'FILTER';
const SORT = 'SORT';
const UNARCHIVE = 'UNARCHIVE';

export default ({ categoryContextId }) => ({
  id: MOD_APPROVAL__WORKFLOW_DASHBOARD,
  context,
  initial: 'loading',
  states: {
    loading: {
      invoke: {
        src: MANAGEABLE_WORKFLOWS,
        onDone: 'ready',
      },
    },

    ready: {
      id: 'ready',
      meta: { defaultErrorTarget: true },
      on: {
        [CHANGE_PAGINATION]: { target: 'loading', actions: 'updatePagination' },
        [CREATE_WORKFLOW]: 'create',
        [FILTER]: { target: 'loading', actions: 'updateFilters' },
        [SORT]: { target: 'loading', actions: 'updateSort' },
        [DELETE]: { target: 'confirmDelete', actions: 'setToMutateId' },
        [CLONE]: { target: 'clone', actions: 'setToMutateId' },
        [ARCHIVE]: { target: 'confirmArchive', actions: 'setToMutateId' },
        [UNARCHIVE]: { target: 'confirmUnarchive', actions: 'setToMutateId' },
      },
    },

    create: {
      on: {
        [CANCEL_MODAL]: {
          actions: send(CANCEL, {
            to: MOD_APPROVAL__WORKFLOW_CREATE,
          }),
        },
      },
      invoke: {
        src: MOD_APPROVAL__WORKFLOW_CREATE,
        onDone: [
          { target: 'creating', cond: 'hasCreateData' },
          { target: 'ready' }, // cancelled
        ],
      },
    },

    creating: {
      invoke: {
        src: 'createWorkflow',
        onDone: {
          actions: 'navigateToNewWorkflow',
        },
      },
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
            workflow: getToMutateWorkflow(context),
            contextId: categoryContextId,
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

    confirmUnarchive: {
      initial: 'ready',
      states: {
        ready: {
          on: {
            [UNARCHIVE]: 'unarchiving',
            [CANCEL]: { target: '#ready', actions: 'unsetToMutateId' },
          },
        },

        unarchiving: {
          invoke: {
            src: 'unarchiveWorkflow',
            onDone: {
              target: '#ready',
              actions: ['notifySuccess'],
            },
          },
        },
      },
    },

    confirmArchive: {
      initial: 'ready',
      states: {
        ready: {
          on: {
            [ARCHIVE]: 'archiving',
            [CANCEL]: { target: '#ready', actions: 'unsetToMutateId' },
          },
        },

        archiving: {
          invoke: {
            src: 'archiveWorkflow',
            onDone: {
              target: '#ready',
              actions: ['notifySuccess'],
            },
          },
        },
      },
    },

    confirmDelete: {
      on: {
        [DELETE]: 'deleting',
        [CANCEL]: { target: '#ready', actions: 'unsetToMutateId' },
      },
    },

    deleting: {
      invoke: {
        src: 'deleteWorkflow',
        onDone: {
          target: '#ready',
          actions: ['notifySuccess', 'removeCachedWorkflow'],
        },
        onError: {
          target: '#ready',
          actions: 'genericErrorNotify',
        },
      },
    },
  },
});
