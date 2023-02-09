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
import { WorkflowState, SUB_SECTION } from 'mod_approval/constants';

const approvals = str => str.includes(WorkflowState.APPROVALS);
const interactions = str => str.includes(WorkflowState.INTERACTIONS);
const notifications = str => str.includes(WorkflowState.NOTIFICATIONS);
const form = str => str.includes(WorkflowState.FORM);
const overrides = str => str.includes(WorkflowState.OVERRIDES);

/***
 * @param {string[]} statePaths
 * @param {string[]} prevStatePaths
 * @returns {Object}
 */
export default function setParams(statePaths, prevStatePaths) {
  if (statePaths.find(approvals) && !prevStatePaths.find(approvals)) {
    return {
      [SUB_SECTION]: WorkflowState.APPROVALS,
    };
  }

  /* The overrides view is 'nested' under the approvals view.
   * The conditions in this function only deal with state changes
   * and are merged into existing params.
   * This preserves the ?sub_section=approvals param when navigating in and out of overrides
   */
  const inOverrides = statePaths.find(overrides);
  const wasInOverrides = prevStatePaths.find(overrides);
  if (inOverrides && !wasInOverrides) {
    return {
      [WorkflowState.OVERRIDES]: true,
    };
  }

  const params = {};

  // removes the '?overrides=true' from the URL
  if (!inOverrides && wasInOverrides) {
    params[WorkflowState.OVERRIDES] = undefined;
  }

  if (statePaths.find(interactions) && !prevStatePaths.find(interactions)) {
    return {
      [SUB_SECTION]: WorkflowState.INTERACTIONS,
    };
  }

  if (statePaths.find(notifications) && !prevStatePaths.find(notifications)) {
    params[SUB_SECTION] = WorkflowState.NOTIFICATIONS;
  }

  if (statePaths.find(form) && !prevStatePaths.find(form)) {
    params[SUB_SECTION] = WorkflowState.FORM;
  }

  if (Object.keys(params).length > 0) {
    return params;
  }

  return false;
}
