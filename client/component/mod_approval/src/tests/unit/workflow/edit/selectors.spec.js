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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */

import { getWorkflowStagesDeletable } from 'mod_approval/workflow/edit/selectors';

import { set } from 'tui/util';
import { StageType } from 'mod_approval/constants';

test('getWorkflowStagesDeletable', () => {
  const createContext = stages => {
    const context = {};
    const path =
      'loadWorkflow.mod_approval_load_workflow.workflow.latest_version.stages';
    set(context, path.split('.'), stages);
    return context;
  };

  let context;

  context = createContext([
    { id: '1', type: { enum: StageType.FORM_SUBMISSION } },
  ]);
  expect(getWorkflowStagesDeletable(context)).toEqual({ '1': false });

  context = createContext([
    { id: '1', type: { enum: StageType.FORM_SUBMISSION } },
    { id: '2', type: { enum: StageType.FINISHED } },
  ]);
  expect(getWorkflowStagesDeletable(context)).toEqual({
    '1': false,
    '2': false,
  });

  context = createContext([
    { id: '1', type: { enum: StageType.FORM_SUBMISSION } },
    { id: '2', type: { enum: StageType.APPROVALS } },
    { id: '3', type: { enum: StageType.FINISHED } },
  ]);
  expect(getWorkflowStagesDeletable(context)).toEqual({
    '1': false,
    '2': true,
    '3': false,
  });

  context = createContext([
    { id: '1', type: { enum: StageType.FORM_SUBMISSION } },
    { id: '2', type: { enum: StageType.APPROVALS } },
    { id: '3', type: { enum: StageType.FORM_SUBMISSION } },
    { id: '4', type: { enum: StageType.FINISHED } },
    { id: '5', type: { enum: StageType.FINISHED } },
  ]);
  expect(getWorkflowStagesDeletable(context)).toEqual({
    '1': false,
    '2': true,
    '3': true,
    '4': true,
    '5': true,
  });
});
