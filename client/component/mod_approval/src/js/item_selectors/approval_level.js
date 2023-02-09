/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
import { createSelector } from 'tui_xstate/util';
import { ApproverType } from 'mod_approval/constants';

export const getOrdinalNumber = level => level.ordinal_number;
export const getApprovalLevel = level => level.approval_level;
export const getApprovalLevelName = createSelector(
  getApprovalLevel,
  approvalLevel => approvalLevel.name
);
export const getApprovers = level => level.approvers || [];

export const getApproverEntities = createSelector(getApprovers, approvers =>
  approvers.map(approver => approver.approver_entity)
);

export const getRelationshipApprovers = createSelector(
  getApprovers,
  approvers =>
    approvers.filter(({ type }) => type === ApproverType.RELATIONSHIP)
);
export const getPersonApprovers = createSelector(getApprovers, approvers =>
  approvers.filter(({ type }) => type === ApproverType.USER)
);

export const getHasRelationships = createSelector(
  getRelationshipApprovers,
  relationshipApprovers => relationshipApprovers.length > 0
);

export const getHasPersons = createSelector(
  getPersonApprovers,
  personApprovers => personApprovers.length > 0
);

export const getPersonTags = createSelector(getPersonApprovers, approvers =>
  approvers.map(({ id, approver_entity }) => ({
    id,
    text: approver_entity.fullname,
  }))
);

export const getRelationshipTags = createSelector(
  getRelationshipApprovers,
  approvers =>
    approvers.map(({ id, approver_entity }) => ({
      id,
      text: approver_entity.name,
    }))
);

export const getInitialApproverType = createSelector(
  getHasRelationships,
  hasRelationships =>
    hasRelationships ? ApproverType.RELATIONSHIP : ApproverType.USER
);

export const getInherited = level =>
  level.inherited_from_assignment_approval_level;
export const getInheritedAssignment = createSelector(
  getInherited,
  inherited => inherited.assignment
);
export const getInheritedAssignmentName = createSelector(
  getInheritedAssignment,
  assignment => assignment.name
);
