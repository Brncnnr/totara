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
import { WorkflowState, SUB_SECTION } from 'mod_approval/constants';
import { getActiveStage } from 'mod_approval/workflow/edit/selectors';
import {
  hasApprovalLevels,
  hasInteractions,
  hasFormViews,
} from 'mod_approval/item_selectors/workflow_stage';

export default function initialActiveSubsection({ params = {}, context }) {
  if (Object.values(WorkflowState).includes(params[SUB_SECTION])) {
    return params[SUB_SECTION];
  }

  const activeStage = getActiveStage(context);

  if (activeStage) {
    if (hasFormViews(activeStage)) {
      return WorkflowState.FORM;
    }

    if (hasApprovalLevels(activeStage)) {
      return WorkflowState.APPROVALS;
    }

    if (hasInteractions(activeStage)) {
      return WorkflowState.INTERACTIONS;
    }
  }

  return WorkflowState.NOTIFICATIONS;
}
