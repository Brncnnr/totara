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
import { MANAGEABLE_WORKFLOWS } from 'mod_approval/constants';
import { createSelector } from 'tui_xstate/util';

export const getWorkflowsData = context =>
  context[MANAGEABLE_WORKFLOWS].mod_approval_manageable_workflows;
export const getWorkflows = createSelector(
  getWorkflowsData,
  data => data.items || []
);
export const getTotal = createSelector(
  getWorkflowsData,
  data => data.total || 0
);
