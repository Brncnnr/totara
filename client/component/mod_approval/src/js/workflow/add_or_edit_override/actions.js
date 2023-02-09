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
import { assign } from 'tui_xstate/xstate';
import { get } from 'tui/util';
import { variables } from 'mod_approval/graphql_selectors/selectable_users';
import { ApproverType as types } from 'mod_approval/constants';
import * as selectors from './selectors';
export { notifySuccess } from 'mod_approval/workflow/edit/actions';

export const updateUserSearch = assign(
  (context, { approvalLevelId, search, nextCursor }) => {
    let srch;
    // allow setting search to '' by testing for undefined or null explicitly
    if (search == null) {
      srch =
        get(context, [
          'userSearchVariables',
          approvalLevelId,
          'input',
          'filters',
          'fullname',
        ]) || '';
    } else {
      srch = search;
    }

    return {
      activeLevelId: approvalLevelId,
      userSearchVariables: Object.assign(context.userSearchVariables, {
        [approvalLevelId]: variables({
          workflowId: context.workflowId,
          search: srch,
          cursor: nextCursor,
        }),
      }),
    };
  }
);

export const activateTaglist = assign({
  activeLevelId: (context, event) => event.approvalLevelId,
});

export const setShouldAppend = assign({ shouldAppend: true });
export const unsetShouldAppend = assign({ shouldAppend: false });

export const setApproversVariables = assign({
  approversVariables: context => {
    const { approversChanged, approverTypes } = context.formValues;
    return Object.keys(approversChanged)
      .filter(levelId => approversChanged[levelId])
      .map(levelId => {
        const approverType = approverTypes[levelId];

        // check if approvers are overridden
        // if not then set mutation to empty approvers (inherited)
        const approvers = context.formValues.overridden[levelId]
          ? context.formValues[approverType][levelId]
          : [];

        return {
          input: {
            assignment_id: selectors.getAssignmentId(context),
            approval_level_id: levelId,
            approvers: approvers.map(approver => ({
              assignment_approver_type: approverType,
              identifier: approver.id,
            })),
          },
        };
      });
  },
});

export const selectApproverType = assign(
  (context, { approverType, approvalLevelId }) => {
    const { formValues, approversEmpty } = context;
    formValues.approverTypes[approvalLevelId] = approverType;
    formValues.approversChanged[approvalLevelId] = haveApproversChanged(
      context,
      { approverType, approvalLevelId },
      formValues
    );

    if (formValues[approverType][approvalLevelId].length > 0) {
      approversEmpty[approvalLevelId] = false;
    }

    return {
      approversEmpty,
      formValues,
    };
  }
);

export const selectApprover = assign(
  (context, { approverType, approvalLevelId, user }) => {
    const { formValues, approversEmpty } = context;
    const tags = formValues[approverType][approvalLevelId];
    const updated = [...tags, { id: user.id, text: user.fullname }];
    formValues[approverType][approvalLevelId] = updated;
    formValues.approversChanged[approvalLevelId] = haveApproversChanged(
      context,
      { approverType, approvalLevelId },
      formValues
    );

    approversEmpty[approvalLevelId] = false;

    return {
      approversEmpty,
      // new reference object needed to trigger reselect recompute
      formValues: Object.assign({}, formValues),
    };
  }
);

export const removeApprover = assign(
  (context, { approverType, approvalLevelId, tag }) => {
    const { formValues } = context;
    const tags = formValues[approverType][approvalLevelId];
    const updated = tags.filter(t => t.id !== tag.id);
    formValues[approverType][approvalLevelId] = updated;
    formValues.approversChanged[approvalLevelId] = haveApproversChanged(
      context,
      { approverType, approvalLevelId },
      formValues
    );

    return {
      formValues,
    };
  }
);

export const toggleOverride = assign(
  (context, { approvalLevelId, checked }) => {
    const { formValues } = context;
    const {
      inherited_from_assignment_approval_level,
    } = selectors
      .getApprovalLevels(context)
      .find(level => level.approval_level.id === approvalLevelId);

    formValues.overridden[approvalLevelId] = checked;
    formValues.approversChanged[approvalLevelId] =
      Boolean(inherited_from_assignment_approval_level) === checked;

    return {
      formValues,
    };
  }
);

export const displayEmptyErrors = assign({
  approversEmpty: ({ formValues }) => {
    return Object.keys(formValues.overridden)
      .filter(approvalLevelId => formValues.overridden[approvalLevelId])
      .filter(approvalLevelId => {
        const approverType = formValues.approverTypes[approvalLevelId];
        const levelApprovers = formValues[approverType][approvalLevelId];
        return levelApprovers.length === 0;
      })
      .reduce((acc, approvalLevelId) => {
        acc[approvalLevelId] = true;
        return acc;
      }, {});
  },
});

export const setSelectedTarget = assign((context, event) => {
  return {
    selectedIdentifier: event.identifier,
    selectedAssignmentType: event.assignmentType,
  };
});

export const setOverrideAssignment = assign({
  overrideAssignment: (context, event) =>
    event.data.data.mod_approval_assignment_manage,
  allOverriden: true,
});

export const setFormValues = assign({
  formValues: context => {
    const { overrideAssignment, approverTypes } = context;
    const formValues = {
      approversChanged: {},
      overridden: {},
      approverTypes: {},
      search: {},
    };

    approverTypes.forEach(approverType => {
      formValues[approverType.type] = {};
    });

    let relationships = [];
    const relationshipType = approverTypes.find(
      approverType => approverType.type === types.RELATIONSHIP
    );

    if (relationshipType) {
      relationships = relationshipType.options.map(option => ({
        option,
        id: option.identifier,
        text: option.name,
      }));
    }

    overrideAssignment.assignment_approval_levels.forEach(
      ({
        approval_level,
        inherited_from_assignment_approval_level,
        approvers,
      }) => {
        const { id } = approval_level;
        const approver = approvers[0];
        const approverType = approver ? approver.type : types.USER; // default approverType

        // set default relationship approvers
        formValues[types.RELATIONSHIP][id] = relationships;
        formValues[types.USER][id] = [];
        formValues.approverTypes[id] = approverType;
        formValues.overridden[id] =
          !inherited_from_assignment_approval_level || context.allOverriden;
        formValues.search[id] = '';
        formValues[approverType][id] = approvers.map(approver => ({
          id: approver.approver_entity.id,
          text: approver.approver_entity.name,
        }));
      }
    );

    return formValues;
  },
});

function haveApproversChanged(
  context,
  { approvalLevelId, approverType },
  formValues
) {
  const { approvers } = selectors
    .getApprovalLevels(context)
    .find(level => level.approval_level.id === approvalLevelId);

  const update = formValues[approverType][approvalLevelId];
  if (update.length !== approvers.length) {
    return true;
  }

  const formApproverType = formValues.approverTypes[approvalLevelId];
  // formvalues for these approvers match the orginal approvers for the changed approval level
  return !update.every(formApprover => {
    const match = approvers.find(
      approver =>
        approver.approver_entity.id === formApprover.id &&
        approver.type === formApproverType
    );

    return Boolean(match);
  });
}
