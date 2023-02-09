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

import { assign } from 'tui_xstate/xstate';
import { SELECTABLE_APPLICANTS } from 'mod_approval/constants';
import {
  jobAssignmentSelectors,
  applicantSelectors,
} from 'mod_approval/graphql_selectors';
import { getWorkflowTypeOptions } from 'mod_approval/application/index/create_new/selectors';

export const removeSearch = assign({
  variables: () => ({
    input: {
      filters: { fullname: '' },
      pagination: {},
    },
  }),
});

export const setShouldAppend = assign({ shouldAppend: true });
export const unsetShouldAppend = assign({ shouldAppend: false });

// used to close the TagList options on select
// non-standard behaviour, usually can you can select multiple
export const removeUsers = assign(() => {
  return {
    [SELECTABLE_APPLICANTS]: applicantSelectors.create(),
  };
});

export const setSelectedUser = assign({
  selectedUser: (context, event) => event.selectedUser,
});

export const setSelectedJobAssignment = assign({
  selectedJobAssignment: (context, event) =>
    jobAssignmentSelectors
      .getJobAssignments(context)
      .find(
        jobAssignment =>
          jobAssignment.job_assignment_id === event.jobAssignmentId
      ),
});

export const setSelectedWorkflowType = assign({
  selectedWorkflowType: (context, event) => {
    return getWorkflowTypeOptions(context).find(
      workflowType => workflowType.id === event.id
    );
  },
});

export const unsetSelectedUser = assign({
  selectedUser: null,
});

export const updateVariables = assign({
  variables: (context, event) => event.variables,
});
