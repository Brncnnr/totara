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
import { createSelector } from 'tui_xstate/util';
import { GET_ACTIVE_FORMS } from '../constants';

export const getActiveFormsData = context =>
  context[GET_ACTIVE_FORMS].mod_approval_get_active_forms;
export const getActiveForms = createSelector(
  getActiveFormsData,
  formsData => formsData.items
);
export const getFormsTotal = createSelector(
  getActiveFormsData,
  formsData => formsData.total
);
