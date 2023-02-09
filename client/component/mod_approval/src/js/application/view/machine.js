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
  APPLICATION_FORM_SCHEMA,
  LOAD_APPLICATION,
  LOAD_APPLICATION_ACTIVITIES,
} from 'mod_approval/constants';
import { createMachine } from 'tui_xstate/xstate';
import * as actions from 'mod_approval/application/view/actions';
import * as selectors from 'mod_approval/application/view/selectors';
import * as services from 'mod_approval/application/view/services';
import * as guards from 'mod_approval/application/view/guards';
import createState from 'mod_approval/application/view/state';

export default function applicationViewMachine({
  loadApplicationResult,
  formData,
  currentUser,
}) {
  const queries = {
    [APPLICATION_APPROVERS]: true,
    [APPLICATION_FORM_SCHEMA]: true,
    [LOAD_APPLICATION]: true,
    [LOAD_APPLICATION_ACTIVITIES]: true,
  };

  const state = createState({
    loadApplicationResult,
    formData,
    currentUser,
  });

  const options = {
    guards,
    actions,
    services,
    selectors,
    queries,
  };

  return createMachine(state, options);
}
