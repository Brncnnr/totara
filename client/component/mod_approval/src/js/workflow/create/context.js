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
  AssignmentType,
  GET_ACTIVE_FORMS,
  WORKFLOW_ID_NUMBER_IS_UNIQUE,
} from 'mod_approval/constants';

export default function makeContext({ workflowTypeOptions }) {
  return {
    details: null,
    formSearch: '',
    formLimit: 10,
    formPage: 1,
    idNumber: null,
    selectedFormId: null,
    selectedIdentifier: null,
    selectedAssignmentType: AssignmentType.ORGANISATION,
    workflowTypeOptions,

    [WORKFLOW_ID_NUMBER_IS_UNIQUE]: {
      mod_approval_workflow_id_number_is_unique: null,
    },
    [GET_ACTIVE_FORMS]: {
      mod_approval_get_active_forms: {
        items: [],
        total: null,
      },
    },
  };
}
