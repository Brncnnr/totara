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
  MY_APPLICATIONS,
  APPLICATIONS_FROM_OTHERS,
  CREATE_NEW_APPLICATION_MENU,
  LOAD_WORKFLOW_TYPES,
} from 'mod_approval/constants';

export default function makeContext({
  showApplicationsFromOthers,
  currentUserId,
}) {
  return {
    currentUserId,
    showApplicationsFromOthers,
    fullname: '',
    [MY_APPLICATIONS]: {
      mod_approval_my_applications: {
        items: [],
        total: null,
        next_cursor: null,
      },
    },
    [APPLICATIONS_FROM_OTHERS]: {
      mod_approval_others_applications: {
        items: [],
        total: null,
        next_cursor: null,
      },
    },
    [CREATE_NEW_APPLICATION_MENU]: {
      mod_approval_create_new_application_menu: [],
    },
    [LOAD_WORKFLOW_TYPES]: {
      mod_approval_load_workflow_types: [],
    },
  };
}
