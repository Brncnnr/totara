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

export const shouldAppend = context => context.shouldAppend;

export const approversEmpty = ({ formValues }) =>
  Object.keys(formValues.overridden)
    .filter(approvalLevelId => formValues.overridden[approvalLevelId])
    .some(approvalLevelId => {
      const approverType = formValues.approverTypes[approvalLevelId];
      const levelApprovers = formValues[approverType][approvalLevelId];
      return levelApprovers.length === 0;
    });

export const variablesEmpty = ({ approversVariables }) =>
  approversVariables.length === 0;

export const hasOverrideAssignment = context =>
  Boolean(context.overrideAssignment);

export const hasNotFetchedAssignmentTypeIdentifers = (
  { disabledIds },
  { assignmentType }
) => {
  return disabledIds[assignmentType] === null;
};
