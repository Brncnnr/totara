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
import {
  jobAssignmentSelectors,
  applicantSelectors,
} from 'mod_approval/graphql_selectors';
import {
  getForYourself,
  getSelectedUser,
} from 'mod_approval/application/index/create_new/selectors';

export const hasJobAssignments = context =>
  jobAssignmentSelectors.getJobAssignments(context).length > 0;

export const forYourself = context => getForYourself(context);

// guard against going into 'searching' state
// when executing a generic search with the default 20 users
export const emptySearchWithExisting = (context, event) => {
  const users = applicantSelectors.getSelectableApplicants(context);
  return users.length === 20 && event.fullname === '';
};

export const moreUsers = context => {
  const users = applicantSelectors.getSelectableApplicants(context);
  const total = applicantSelectors.getTotal(context);
  return users.length < total;
};

export const shouldAppend = context => context.shouldAppend;

export const hasMultipleWorkflowTypes = context => {
  return context.workflowTypeOptions.length > 1;
};

export const hasSelectedUser = context => getSelectedUser(context) != null;
