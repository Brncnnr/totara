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
import {
  MOD_APPROVAL__WORKFLOW_CREATE,
  WORKFLOW_ID_NUMBER_IS_UNIQUE,
  GET_ACTIVE_FORMS,
} from 'mod_approval/constants';
import makeContext from './context';

export const BACK = 'BACK';
export const CANCEL = 'CANCEL';
export const CREATE = 'CREATE';
export const NEXT = 'NEXT';
export const SELECT_ASSIGNMENT_TARGET = 'SELECT_ASSIGNMENT_TARGET';
export const SELECT_FORM = 'SELECT_FORM';
export const UPDATE_FORM_PAGE = 'UPDATE_FORM_PAGE';
export const UPDATE_FORM_LIMIT = 'UPDATE_FORM_LIMIT';
export const UPDATE_FORM_SEARCH = 'UPDATE_FORM_SEARCH';
export const UPDATE_WORKFLOW_ID = 'UPDATE_WORKFLOW_ID';

export default function makeState({ workflowTypeOptions }) {
  return {
    id: MOD_APPROVAL__WORKFLOW_CREATE,
    context: makeContext({ workflowTypeOptions }),
    initial: 'details',

    on: {
      [CANCEL]: 'cancelled',
    },

    states: {
      details: {
        initial: 'ready',
        on: {
          [CREATE]: 'done',
          [NEXT]: { target: 'chooseForm', actions: 'setDetails' },
          [UPDATE_WORKFLOW_ID]: {
            target: 'details.debouncing',
            actions: 'setIdNumber',
            cond: 'idNumberNotEmpty',
          },
        },

        states: {
          ready: {},

          debouncing: {
            after: {
              500: 'checkingUniqueness',
            },
          },

          checkingUniqueness: {
            invoke: {
              src: WORKFLOW_ID_NUMBER_IS_UNIQUE,
              onDone: 'ready',
            },
          },
        },
      },

      chooseForm: {
        initial: 'searching',

        on: {
          [BACK]: 'details',
          [NEXT]: 'chooseAssignment',
          [SELECT_FORM]: { actions: 'setSelectedFormId' },
          [UPDATE_FORM_PAGE]: {
            target: 'chooseForm.searching',
            actions: 'setFormPage',
          },
          [UPDATE_FORM_LIMIT]: {
            target: 'chooseForm.searching',
            actions: 'setFormLimit',
          },
          [UPDATE_FORM_SEARCH]: {
            target: 'chooseForm.debouncing',
            actions: 'setFormSearch',
          },
        },

        states: {
          ready: {},

          debouncing: {
            after: {
              500: 'searching',
            },
          },

          searching: {
            invoke: {
              src: GET_ACTIVE_FORMS,
              onDone: { target: 'ready' },
            },
          },
        },
      },

      chooseAssignment: {
        on: {
          [BACK]: 'chooseForm',
          [CREATE]: 'done',
          [SELECT_ASSIGNMENT_TARGET]: { actions: 'setSelectedTarget' },
        },
      },

      done: {
        id: 'done',
        type: 'final',
        data: ({
          details,
          selectedFormId,
          selectedAssignmentType,
          selectedIdentifier,
        }) => ({
          createData: Object.assign({}, details, {
            form_id: selectedFormId,
            assignment_type: selectedAssignmentType,
            assignment_identifier: selectedIdentifier,
          }),
        }),
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
