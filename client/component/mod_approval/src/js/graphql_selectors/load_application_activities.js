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
import { LOAD_APPLICATION_ACTIVITIES } from '../constants';

export const getActivities = context =>
  context[LOAD_APPLICATION_ACTIVITIES].mod_approval_load_application_activities
    .activities;

export const getActivitiesGroupedByStageId = createSelector(
  getActivities,
  activities =>
    activities.reduce((acc, item) => {
      if (!acc[item.stage.id]) {
        acc[item.stage.id] = [];
      }

      acc[item.stage.id].push(item);
      return acc;
    }, {})
);
