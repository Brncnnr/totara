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
  SELECTABLE_APPLICANTS,
  CREATE_NEW_APPLICATION_MENU,
} from 'mod_approval/constants';
import createNewApplicationMenuQuery from 'mod_approval/graphql/create_new_application_menu';
import selectableApplicantsQuery from 'mod_approval/graphql/selectable_applicants';
import * as selectors from 'mod_approval/application/index/create_new/selectors';
import { applicantSelectors } from 'mod_approval/graphql_selectors';

export function selectableApplicants(context) {
  return {
    query: selectableApplicantsQuery,
    variables: selectors.getVariables(context),
    fetchPolicy: 'network-only',
  };
}
selectableApplicants.updateContext = (context, event) => {
  const data = event.data.mod_approval_selectable_applicants;
  const { items } = data;
  const users = context.shouldAppend
    ? applicantSelectors.getSelectableApplicants(context).concat(items)
    : items;

  return {
    [SELECTABLE_APPLICANTS]: applicantSelectors.create(
      users,
      data.total,
      data.next_cursor
    ),
  };
};

export function createNewApplicationMenu({
  selectedUser,
  selectedWorkflowType,
}) {
  return {
    query: createNewApplicationMenuQuery,
    variables: {
      query: {
        applicant_id: selectedUser.id,
        workflow_type_id: selectedWorkflowType.id,
      },
    },
  };
}
createNewApplicationMenu.updateContext = (
  context,
  { data: { mod_approval_create_new_application_menu } }
) => {
  // pre-populate the <Select /> default with the 0th jobAssignment
  const selectedJobAssignment =
    mod_approval_create_new_application_menu.length > 0
      ? mod_approval_create_new_application_menu[0]
      : null;

  return {
    selectedJobAssignment,
    [CREATE_NEW_APPLICATION_MENU]: {
      mod_approval_create_new_application_menu,
    },
  };
};
