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
import {
  WorkflowState,
  WorkflowStageFeatureType,
  SUB_SECTION,
} from 'mod_approval/constants';
import initialActiveSubsection from 'mod_approval/workflow/edit/state/initial_active_subsection';
import makeContext from 'mod_approval/workflow/edit/context';

const makeWorkflow = stages => ({
  id: '1',
  latest_version: {
    id: '1',
    stages,
  },
});

test('navigates to approvals subsection with specified param', () => {
  const params = { [SUB_SECTION]: WorkflowState.APPROVALS };
  const workflow = makeWorkflow([
    {
      approval_levels: [],
      features: [{ enum: WorkflowStageFeatureType.APPROVAL_LEVELS }],
    },
  ]);

  const context = makeContext({ workflow, params });
  const subsection = initialActiveSubsection({ params, context });
  expect(subsection).toEqual(WorkflowState.APPROVALS);
});

test('navigates to approvals subsection without specified param', () => {
  const params = {};
  const workflow = makeWorkflow([
    {
      approval_levels: [],
      features: [{ enum: WorkflowStageFeatureType.APPROVAL_LEVELS }],
    },
  ]);

  const context = makeContext({ workflow, params });
  const subsection = initialActiveSubsection({ params, context });
  expect(subsection).toEqual(WorkflowState.APPROVALS);
});

test('navigates to form subsection without specified param', () => {
  const params = {};
  const workflow = makeWorkflow([
    {
      approval_levels: [],
      features: [
        { enum: WorkflowStageFeatureType.FORMVIEWS },
        { enum: WorkflowStageFeatureType.APPROVAL_LEVELS },
      ],
    },
  ]);

  const context = makeContext({ workflow, params });
  const subsection = initialActiveSubsection({ params, context });
  expect(subsection).toEqual(WorkflowState.FORM);
});

test('navigates to notifications subsection as fallback', () => {
  const params = {};
  const workflow = makeWorkflow([
    {
      approval_levels: [],
      features: [],
    },
  ]);

  const context = makeContext({ workflow, params });
  const subsection = initialActiveSubsection({ params, context });
  expect(subsection).toEqual(WorkflowState.NOTIFICATIONS);
});
