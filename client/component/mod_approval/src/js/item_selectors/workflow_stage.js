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
import { WorkflowStageFeatureType } from 'mod_approval/constants';

export const getFeatures = stage => stage.features || [];

export const getApprovalLevelFeature = createSelector(getFeatures, features =>
  features.find(
    feature => feature.enum === WorkflowStageFeatureType.APPROVAL_LEVELS
  )
);

export const getFormViewsFeature = createSelector(getFeatures, features =>
  features.find(feature => feature.enum === WorkflowStageFeatureType.FORMVIEWS)
);

export const getInteractionsFeature = createSelector(getFeatures, features =>
  features.find(
    feature => feature.enum === WorkflowStageFeatureType.INTERACTIONS
  )
);

export const hasApprovalLevels = createSelector(
  getApprovalLevelFeature,
  Boolean
);

export const hasFormViews = createSelector(getFormViewsFeature, Boolean);

export const hasInteractions = createSelector(getInteractionsFeature, Boolean);
