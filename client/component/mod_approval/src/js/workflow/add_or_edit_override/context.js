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

import { AssignmentType } from 'mod_approval/constants';

export default function makeContext({
  workflowId,
  approverTypes = [],
  overrideAssignment = null,
  workflowStageId = null,
  isAdd,
}) {
  const disabledIds = {};
  Object.keys(AssignmentType).forEach(type => {
    disabledIds[type] = null;
  });

  return {
    activeLevelId: null,
    allOverriden: false,
    approversChanged: {},
    approversEmpty: {},
    approversVariables: [],
    approverTypes,
    disabledIds,
    isAdd,
    formValues: null,
    overrideAssignment,
    selectedIdentifier: null,
    selectedAssignmentType: AssignmentType.ORGANISATION,
    shouldAppend: false,
    users: {},
    userSearchVariables: {},
    workflowId,
    workflowStageId,
  };
}
