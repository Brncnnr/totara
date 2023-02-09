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
import { createSelector } from 'tui_xstate/util';

export * from 'mod_approval/graphql_selectors/load_application';
export const getFormData = context => context.formData;
export const getBackQueryParams = context => context.backQueryParams;
export const getFromDashboard = context => context.fromDashboard;
export const getBackParams = createSelector(
  getBackQueryParams,
  getFromDashboard,
  (backQueryParams, fromDashboard) => {
    if (fromDashboard) {
      return Object.assign({}, backQueryParams, { from_dashboard: true });
    } else {
      return {};
    }
  }
);
