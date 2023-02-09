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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module mod_approval
 */
import { createSelector } from 'tui_xstate/util';
import { LOAD_APPLICATION, OverallProgressState } from '../constants';

export const getApplication = context =>
  context[LOAD_APPLICATION].mod_approval_load_application.application;

export const getParsedFormSchema = context => context.parsedFormSchema;

export const getRootFields = createSelector(
  getParsedFormSchema,
  parsedFormSchema => parsedFormSchema.fields || []
);

export const getSections = createSelector(getParsedFormSchema, formSchema =>
  formSchema && formSchema.sections ? formSchema.sections : []
);

export const getTitle = createSelector(
  getParsedFormSchema,
  formData => formData.title
);

export const getApplicationId = createSelector(
  getApplication,
  application => application.id
);

export const getApplicationIdNumber = createSelector(
  getApplication,
  application => application.id_number
);

export const getApplicationTitle = createSelector(
  getApplication,
  application => application.title
);

export const getOverallProgress = createSelector(
  getApplication,
  application => application.overall_progress
);

export const getCurrentApprovalLevel = createSelector(
  getApplication,
  application =>
    application.current_state.approval_level
      ? application.current_state.approval_level
      : null
);

export const getCurrentApprovalLevelId = createSelector(
  getCurrentApprovalLevel,
  currentApprovalLevel =>
    currentApprovalLevel ? currentApprovalLevel.id : null
);

export const getApprovers = createSelector(
  getApplication,
  application => application.approver_users || []
);

export const getCurrentStage = createSelector(getApplication, application =>
  application.current_state.stage ? application.current_state.stage : null
);

export const getCurrentStageApprovalLevelIds = createSelector(
  getCurrentStage,
  stage =>
    stage && stage.approval_levels
      ? stage.approval_levels.map(({ id }) => id)
      : []
);

export const getFinalApprovalLevelId = createSelector(
  getCurrentStageApprovalLevelIds,
  approvalLevelIds =>
    approvalLevelIds.length > 0
      ? approvalLevelIds[approvalLevelIds.length - 1]
      : null
);

export const getIsFinalApprovalLevel = createSelector(
  getFinalApprovalLevelId,
  getCurrentApprovalLevelId,
  (finalApprovalLevelId, currentApprovalLevelId) =>
    finalApprovalLevelId === currentApprovalLevelId
);

export const getLastAction = createSelector(
  getApplication,
  application => application.last_action
);

export const getLastActionName = createSelector(getLastAction, action =>
  action ? action.label : ''
);

export const getLastActionUser = createSelector(getLastAction, action =>
  action ? action.user : null
);

export const getInteractor = createSelector(
  getApplication,
  application => application.interactor
);

export const getCanEdit = createSelector(
  getInteractor,
  interactor => interactor.can_edit
);

export const getCanWithdraw = createSelector(
  getInteractor,
  interactor => interactor.can_withdraw
);

export const getCanBeDeleted = createSelector(
  getInteractor,
  interactor => interactor.can_delete
);

export const getCanClone = createSelector(
  getInteractor,
  interactor => interactor.can_clone
);

export const getCanEditWithoutInvalidating = createSelector(
  getInteractor,
  interactor => interactor.can_edit_without_invalidating
);

export const getCanComplete = createSelector(
  getCanEdit,
  getOverallProgress,
  (canEdit, progress) =>
    canEdit &&
    ![OverallProgressState.REJECTED, OverallProgressState.WITHDRAWN].includes(
      progress
    )
);

export const getPageUrls = createSelector(
  getApplication,
  application => application.page_urls
);

export const getEditUrl = createSelector(
  getPageUrls,
  pageUrls => pageUrls.edit
);

export const getPreviewUrl = createSelector(
  getPageUrls,
  pageUrls => pageUrls.preview
);

export const getCreated = createSelector(
  getApplication,
  application => application.created
);

export const getCreator = createSelector(
  getApplication,
  application => application.creator
);

export const getApplicant = createSelector(
  getApplication,
  application => application.user
);

export const getCompleted = createSelector(
  getApplication,
  application => application.completed
);

export const getActionUser = createSelector(
  getApplication,
  application => application.action_user
);

export const getSubmitter = createSelector(
  getApplication,
  application => application.submitter
);

export const getSubmitted = createSelector(
  getApplication,
  application => application.submitted
);

export const getLastPublishedSubmission = createSelector(
  getApplication,
  application => application.last_published_submission
);

export const getWorkflowType = createSelector(
  getApplication,
  application => application.workflow_type
);

export const getOverallProgressLabel = createSelector(
  getApplication,
  application => application.overall_progress_label
);

export const getIsBeforeSubmission = createSelector(
  getApplication,
  application => application.current_state.is_before_submission
);

export const getIsInApprovals = createSelector(
  getApplication,
  application => application.current_state.is_in_approvals
);

export const getWorkflowStages = createSelector(
  getApplication,
  application => application.workflow_stages
);

export const getCurrentWorkflowStageOrdinalNumber = createSelector(
  getCurrentStage,
  stage => (stage !== null ? stage.ordinal_number : Infinity)
);

export const getUser = createSelector(
  getApplication,
  application => application.user
);

// TODO TL-31441 add unit tests over complex selectors and guards
export const getDefaultValuesMap = createSelector(
  getRootFields,
  getSections,
  (rootFields, sections) =>
    sections
      .map(section => section.fields)
      .reduce((acc, fields) => acc.concat(fields), rootFields)
      .reduce((acc, field) => {
        acc[field.key] = field.default;
        return acc;
      }, {})
);
