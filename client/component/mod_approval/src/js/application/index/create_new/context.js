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
import {
  CREATE_NEW_APPLICATION_MENU,
  SELECTABLE_APPLICANTS,
} from 'mod_approval/constants';
import { applicantSelectors } from 'mod_approval/graphql_selectors';

export default function makeContext({
  currentUserId,
  forYourself = true,
  selectedUser = null,
  selectedJobAssignment = null,
  selectedWorkflowType = null,
  createNewApplicationMenu,
  workflowTypeOptions = [],
}) {
  return {
    currentUserId,
    forYourself,
    fullname: '',
    selectedJobAssignment,
    selectedWorkflowType,
    workflowTypeOptions,
    selectedUser,

    shouldAppend: false,

    variables: {
      input: {
        filters: {
          fullname: '',
        },
        pagination: {},
      },
    },

    [CREATE_NEW_APPLICATION_MENU]: createNewApplicationMenu || {
      mod_approval_create_new_application_menu: [],
    },

    [SELECTABLE_APPLICANTS]: applicantSelectors.create(),
  };
}
