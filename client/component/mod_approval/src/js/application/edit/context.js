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
  APPLICATION_FORM_SCHEMA,
  LOAD_APPLICATION,
} from 'mod_approval/constants';

export default function createContext({ loadApplicationResult }) {
  let preventScrollSupported = false;
  document.createElement('div').focus({
    get preventScroll() {
      preventScrollSupported = true;
      return false;
    },
  });

  return {
    // query param checks
    hasNotify: false,
    fromDashboard: false,
    notify: null,
    notifyType: null,
    backQueryParams: {},

    // ref methods from Reform
    focusFirstInvalid: null,
    trySubmit: null,
    preventScrollSupported,

    // form context
    activeSectionIndex: 0,
    formData: null,
    unsavedChanges: false,
    validationErrors: null,
    keepApprovals: false,

    // query data
    [APPLICATION_FORM_SCHEMA]: {
      mod_approval_application_form_schema: {
        form_data: null,
      },
    },
    [LOAD_APPLICATION]: {
      mod_approval_load_application: loadApplicationResult,
    },
  };
}
