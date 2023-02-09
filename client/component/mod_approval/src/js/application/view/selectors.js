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
import { USER_OWN_PROFILE } from 'mod_approval/constants';
import { createSelector } from 'tui_xstate/util';
import { totaraUrl } from 'tui/util';
import {
  getCurrentWorkflowStageOrdinalNumber,
  getWorkflowStages,
  getEditUrl,
} from 'mod_approval/graphql_selectors/load_application';
import { getActivitiesGroupedByStageId } from 'mod_approval/graphql_selectors/load_application_activities';
export * from 'mod_approval/graphql_selectors/load_application';
export * from 'mod_approval/graphql_selectors/load_application_activities';

export const getOwnProfile = context => context[USER_OWN_PROFILE].profile;
export const getFormData = context => context.formData;
export const getBackQueryParams = context => context.backQueryParams;
export const getFromDashboard = context => context.fromDashboard;
export const getIsFinalStageApprovalLevel = context =>
  context.finalStageApprovalLevel;

export const getStagesWithStates = createSelector(
  getCurrentWorkflowStageOrdinalNumber,
  getActivitiesGroupedByStageId,
  getWorkflowStages,
  (currentOrdinalNumber, activitiesGroupedByStageId, allStages) =>
    // { stage } is nested in worflow_stage in the graphql
    allStages.map(({ stage }) =>
      Object.assign({}, stage, {
        states: progressTrackerStates(
          currentOrdinalNumber,
          stage.ordinal_number
        ),
        activities: activitiesGroupedByStageId[stage.id] || [],
      })
    )
);

function progressTrackerStates(currentOrdinalNumber, ordinalNumber) {
  if (currentOrdinalNumber === ordinalNumber) {
    return ['ready', 'selected'];
  }

  if (currentOrdinalNumber > ordinalNumber) {
    return ['ready', 'done'];
  }

  return ['ready'];
}

export const getEditUrlWithParams = createSelector(
  getEditUrl,
  getBackQueryParams,
  getFromDashboard,
  (editUrl, backQueryParams, fromDashboard) => {
    if (fromDashboard) {
      return totaraUrl(
        editUrl,
        Object.assign({}, backQueryParams, { from_dashboard: true })
      );
    } else {
      return editUrl;
    }
  }
);
