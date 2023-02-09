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

import { createSelector } from 'tui_xstate/util';
import {
  OVERRIDE_ASSIGNMENTS,
  ApproverType,
  OverridesSortBy,
  StageType,
  FormviewVisibility,
} from 'mod_approval/constants';
import {
  getWorkflowStages,
  getWorkflowId,
  getFormSchema,
} from 'mod_approval/graphql_selectors/load_workflow';
import { variables as selectableUsersVariables } from 'mod_approval/graphql_selectors/selectable_users';
import { get, totaraUrl } from 'tui/util';
export { getMoveToOptions } from 'mod_approval/graphql_selectors/workflow_stage_move_to';

export * from 'mod_approval/graphql_selectors/load_workflow';
export * from 'mod_approval/graphql_selectors/selectable_users';
export * from 'mod_approval/graphql_selectors/ancestor_assignment_approval_levels';

const getContext = context => context;

export const getCategoryContextId = context => context.categoryContextId;

export const getApproverTypes = context => context.approverTypes;

export const getRoleType = createSelector(getApproverTypes, types =>
  types.find(approverType => approverType.type === ApproverType.RELATIONSHIP)
);

export const getRoleTypeOptions = createSelector(
  getRoleType,
  roleType => roleType.options || []
);

export const getStageNames = createSelector(getWorkflowStages, stages =>
  stages.map(stage => stage.name)
);

export const getToEditApprovalLevelId = context =>
  context.toEditApprovalLevelId;

export const getToEditStageId = context => context.toEditStageId;
export const getActiveStageId = context => context.activeWorkflowStageId;

export const getToEditStage = createSelector(
  getToEditStageId,
  getWorkflowStages,
  (stageId, stages) => stages.find(x => x.id === stageId)
);

export const getActiveStage = createSelector(
  getActiveStageId,
  getWorkflowStages,
  (id, stages) => stages && stages.find(x => x.id === id)
);

export const getActiveStageIndex = createSelector(
  getActiveStageId,
  getWorkflowStages,
  (id, stages) => stages && stages.findIndex(x => x.id === id)
);

export const getOverridesStageKey = createSelector(
  getActiveStageId,
  activeStageId => `${OVERRIDE_ASSIGNMENTS}_stage_${activeStageId}`
);

export const getActiveStageName = createSelector(
  getActiveStage,
  stage => stage.name
);
export const getActiveStageNumber = createSelector(
  getActiveStage,
  stage => stage.ordinal_number
);

export const getPreviousStage = createSelector(
  getActiveStageIndex,
  getWorkflowStages,
  (index, stages) => stages[index - 1]
);

export const getNextStage = createSelector(
  getActiveStageIndex,
  getWorkflowStages,
  (index, stages) => stages[index + 1]
);

export const getExtendedContexts = context => context.stagesExtendedContexts;

export const getActiveExtendedContext = createSelector(
  getActiveStageId,
  getExtendedContexts,
  (activeStageId, extendedContexts) =>
    extendedContexts.find(
      extendedContext => extendedContext.itemId === parseInt(activeStageId, 10)
    )
);

export const getContextId = createSelector(
  getActiveExtendedContext,
  extendedContext => extendedContext.contextId
);

export const getActiveStageApprovalLevels = createSelector(
  getActiveStage,
  stage => (stage && stage.approval_levels) || []
);

export const getToEditApprovalLevel = createSelector(
  getToEditApprovalLevelId,
  getActiveStageApprovalLevels,
  /** @returns ?object */
  (approvalLevelId, approvalLevels) =>
    approvalLevels.find(approvalLevel => approvalLevel.id === approvalLevelId)
);

export const getHasMultipleApprovalLevels = createSelector(
  getActiveStageApprovalLevels,
  approvalLevels => approvalLevels.length > 1
);

export const getToEditApprovalLevelName = createSelector(
  getToEditApprovalLevel,
  approvalLevel => (approvalLevel ? approvalLevel.name : '')
);

export const getActiveStageFormviews = createSelector(
  getActiveStage,
  stage => stage.formviews || []
);

export const getActiveStageFormviewsObject = createSelector(
  getActiveStageFormviews,
  /** @returns {{ [fieldKey: string]: object }} */
  formviews =>
    formviews.reduce((acc, cur) => {
      acc[cur.field_key] = cur;
      return acc;
    }, {})
);

export const getStagesSectionVisibility = context =>
  context.stagesSectionVisibility;

export const getFormSchemaSections = createSelector(
  getFormSchema,
  /** @returns {{ key: string, title: string, fields: object[] }[]} */
  schema => {
    const sections = [];
    if (schema.fields) {
      sections.push({
        key: 'root',
        title: schema.title,
        fields: schema.fields,
      });
    }
    if (schema.sections) {
      schema.sections.forEach(x => sections.push(x));
    }
    return sections;
  }
);

export const getFormSchemaSectionsFieldKeys = createSelector(
  getFormSchemaSections,
  /** @returns {{[section_key: string]: string[]}} */
  sections =>
    sections.reduce((acc, section) => {
      acc[section.key] = (section.fields || []).map(x => x.key);
      return acc;
    }, {})
);

export const getActiveStageSectionConfig = createSelector(
  getActiveStageId,
  getActiveStageFormviewsObject,
  getStagesSectionVisibility,
  getFormSchemaSections,
  /** @returns {{ [section_key: string]: { visible: boolean } }} */
  (stageId, formviews, stagesSectionVisibility, sections) =>
    sections.reduce((acc, section) => {
      const sectionVisibility = get(stagesSectionVisibility, [
        stageId,
        section.key,
      ]);
      acc[section.key] = {
        visible:
          sectionVisibility != null
            ? sectionVisibility
            : section.fields &&
              section.fields.some(
                field =>
                  formviews[field.key] &&
                  formviews[field.key].visibility != FormviewVisibility.HIDDEN
              ),
      };
      return acc;
    }, {})
);

export const getActiveStageInteractions = createSelector(
  getActiveStage,
  /** @returns {Object[]} */
  stage => stage.interactions || []
);

export const getToEditStageName = createSelector(
  getToEditStage,
  stage => stage && stage.name
);

export const getVariables = context => context.variables;

/**
 * Variables for each stages's override_assignments query is placed
 * under context.variables[overridesStageKey].
 * This allows the machine to preserve query state (search, sort_by, pagination)
 * as the user switches between stages
 */
export const getOverridesVariables = createSelector(
  getVariables,
  getOverridesStageKey,
  (variables, key) => variables[key]
);

export const getOverridesInput = createSelector(
  getOverridesVariables,
  variables => variables.input
);

export const getOverridesSortBy = createSelector(
  getOverridesInput,
  input => input.sort_by
);

export const getOverridesPagination = createSelector(
  getOverridesInput,
  input => input.pagination
);

export const getOverridesPage = createSelector(
  getOverridesPagination,
  pagination => pagination.page
);

export const getOverridesLimit = createSelector(
  getOverridesPagination,
  pagination => pagination.limit
);

export const getOverridesFilters = createSelector(
  getOverridesInput,
  input => input.filters
);

export const getOverridesNameSearch = createSelector(
  getOverridesFilters,
  filters => filters.name || ''
);

export const getActiveVariables = createSelector(
  getVariables,
  getOverridesStageKey,
  (variables, overridesStageKey) => variables[overridesStageKey]
);

export const getDefaultVariables = createSelector(
  getActiveStageId,
  defaultVariables
);

export const getOverridesData = createSelector(
  getContext,
  getOverridesStageKey,
  (context, key) => context[key]
);

export const getOverrides = createSelector(getOverridesData, data =>
  data ? data.items : []
);

export const getOverridesTotal = createSelector(getOverridesData, data =>
  data ? data.total : 0
);

export const getFirstOverride = createSelector(
  getOverrides,
  overrides => overrides[0] || { assignment_approval_levels: [] }
);

export const getAssignment = createSelector(
  getFirstOverride,
  override => override.assignment
);

export const getAssignmentApprovalLevels = createSelector(
  getFirstOverride,
  override => override.assignment_approval_levels
);

export const getApprovalLevels = createSelector(
  getAssignmentApprovalLevels,
  assignmentApprovalLevels =>
    assignmentApprovalLevels.map(
      assignmentApprovalLevel => assignmentApprovalLevel.approval_level
    )
);

export const getActiveApprovalLevelId = context =>
  context.activeApprovalLevelId;

export const getUserSearchVariables = context => context.userSearchVariables;
export const getActiveUserSearchVariables = createSelector(
  getActiveApprovalLevelId,
  getUserSearchVariables,
  getWorkflowId,
  (activeApprovalLevelId, searchVariables, workflowId) =>
    searchVariables[activeApprovalLevelId] ||
    selectableUsersVariables({ workflowId })
);

export const getUsers = context => context.users;

export const getSelectedApproverTypes = context =>
  context.selectedApproverTypes;

export function defaultVariables(stageId) {
  return {
    input: {
      workflow_stage_id: stageId,
      pagination: {
        page: 1,
        limit: 20,
      },
      filters: {},
      sort_by: OverridesSortBy.NAME_ASC,
    },
  };
}

export const getWorkflowStagesDeletable = createSelector(
  getWorkflowStages,
  stages => {
    const endStages = stages.filter(x => x.type.enum == StageType.FINISHED);
    return stages.reduce((acc, stage, index) => {
      if (stage.type.enum == StageType.FINISHED) {
        // prevent deleting last end stage
        acc[stage.id] = endStages.length > 1;
      } else {
        // prevent deleting first stage
        acc[stage.id] = index !== 0;
      }
      return acc;
    }, {});
  }
);

export const getFormPreviewUrl = createSelector(
  getWorkflowId,
  getActiveStageId,
  (workflowId, stageId) => {
    return totaraUrl('/mod/approval/workflow/form_view/preview.php', {
      workflow_id: workflowId,
      stage_id: stageId,
    });
  }
);

/** @returns {?Object} */
export const getToEditInteraction = context => context.toEditInteraction;
export const getDefaultTransition = createSelector(
  getToEditInteraction,
  /** @returns {?Object} */
  interaction => (interaction ? interaction.default_transition : null)
);

export const getTransition = createSelector(
  getDefaultTransition,
  /** @returns {?string} */
  defaultTransition => (defaultTransition ? defaultTransition.transition : null)
);

export const getDefaultTransitionId = createSelector(
  getDefaultTransition,
  /** @returns {?string} */
  defaultTransition => (defaultTransition ? defaultTransition.id : null)
);

export const getInteractionId = createSelector(
  getToEditInteraction,
  /** @returns {?string} */
  interaction => (interaction ? interaction.id : null)
);
