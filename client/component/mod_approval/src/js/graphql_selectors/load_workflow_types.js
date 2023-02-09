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
 * @author Jack Humphrey <jack.humphrey@totaralearning.com>
 * @module mod_approval
 */
import { createSelector } from 'tui_xstate/util';
import { get } from 'tui/util';
import { LOAD_WORKFLOW_TYPES } from '../constants';

export const getWorkflowTypes = context => {
  return (
    get(context, [
      LOAD_WORKFLOW_TYPES,
      'mod_approval_load_workflow_types',
      'workflow_types',
    ]) || []
  );
};

export const getDefaultWorkflowType = createSelector(
  getWorkflowTypes,
  workflowTypes => workflowTypes[0]
);
