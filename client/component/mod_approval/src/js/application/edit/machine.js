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
import { createMachine } from 'tui_xstate/xstate';
import * as actions from 'mod_approval/application/edit/actions';
import * as selectors from 'mod_approval/application/edit/selectors';
import * as services from 'mod_approval/application/edit/services';
import * as guards from 'mod_approval/application/edit/guards';
import createState from 'mod_approval/application/edit/state';

export default function applicationEditMachine({ loadApplicationResult }) {
  const queries = {
    [APPLICATION_FORM_SCHEMA]: true,
    [LOAD_APPLICATION]: true,
  };

  const state = createState({ loadApplicationResult });
  const options = {
    guards,
    actions,
    services,
    selectors,
    queries,
  };

  return createMachine(state, options);
}
