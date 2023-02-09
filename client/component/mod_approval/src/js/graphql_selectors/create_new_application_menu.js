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
import { CREATE_NEW_APPLICATION_MENU } from '../constants';

export const getJobAssignments = context =>
  context[CREATE_NEW_APPLICATION_MENU]
    .mod_approval_create_new_application_menu || [];

export const getDefaultJobAssignment = createSelector(
  getJobAssignments,
  jobAssignments => jobAssignments[0]
);

export const getJobAssignmentOptions = createSelector(
  getJobAssignments,
  jobAssignments =>
    jobAssignments.map(jobAssignment => ({
      id: jobAssignment.job_assignment_id,
      label: jobAssignment.job_assignment,
    }))
);
