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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */
import { MOD_APPROVAL__WORKFLOW_CLONE } from 'mod_approval/constants';
import makeContext from './context';

// EVENTS
export const BACK = 'BACK';
export const CANCEL = 'CANCEL';
export const CLONE = 'CLONE';
export const NEXT = 'NEXT';

export default ({ workflow, contextId }) => ({
  id: MOD_APPROVAL__WORKFLOW_CLONE,
  context: makeContext({ workflow, contextId }),
  initial: 'collectForm',
  states: {
    collectForm: {
      on: {
        [NEXT]: {
          actions: 'setClonedWorkflowDetails',
          target: 'collectAssignment',
        },
        [CANCEL]: 'cancelled',
      },
    },

    collectAssignment: {
      on: {
        [CLONE]: {
          actions: 'setClonedWorkflowAssignment',
          target: 'done',
        },
        [BACK]: 'collectForm',
        [CANCEL]: 'cancelled',
      },
    },

    done: {
      id: 'done',
      type: 'final',
      data: ({ clonedWorkflowDetails, clonedWorkflowAssignment }) => ({
        cloneData: {
          name: clonedWorkflowDetails.name,
          defaultAssignment: clonedWorkflowAssignment,
        },
      }),
    },

    cancelled: {
      type: 'final',
    },
  },
});
