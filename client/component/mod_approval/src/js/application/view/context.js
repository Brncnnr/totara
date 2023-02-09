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
  APPLICATION_APPROVERS,
  LOAD_APPLICATION,
  LOAD_APPLICATION_ACTIVITIES,
  USER_OWN_PROFILE,
} from 'mod_approval/constants';

export default function createContext({ loadApplicationResult, currentUser }) {
  return {
    // url params
    notify: null,
    notifyType: null,
    fromDashboard: false,
    backQueryParams: {},

    schemaReady: false,
    hasRejectionComment: false,
    formData: null,
    parsedFormSchema: {},
    refetchSchema: false,

    [LOAD_APPLICATION]: {
      mod_approval_load_application: loadApplicationResult,
    },
    [LOAD_APPLICATION_ACTIVITIES]: {
      mod_approval_load_application_activities: {
        activities: [],
      },
    },
    [APPLICATION_APPROVERS]: {
      mod_approval_application_approvers: [],
    },
    // TODO: not a query TL-31452
    [USER_OWN_PROFILE]: {
      profile: currentUser,
    },
  };
}
