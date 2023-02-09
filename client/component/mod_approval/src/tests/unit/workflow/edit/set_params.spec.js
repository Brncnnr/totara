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

import setParams from 'mod_approval/workflow/edit/set_params';
import { SUB_SECTION, WorkflowState } from 'mod_approval/constants';

test('setParams updates navigation to approvals', () => {
  const prevStatePaths = ['navigation.form'];
  const statePaths = ['navigation.approvals'];

  const params = setParams(statePaths, prevStatePaths);
  expect(params).toMatchObject({ [SUB_SECTION]: WorkflowState.APPROVALS });
});

test('setParams updates navigation to form', () => {
  const prevStatePaths = ['navigation.notifications'];
  const statePaths = ['navigation.form'];

  const params = setParams(statePaths, prevStatePaths);
  expect(params).toMatchObject({ [SUB_SECTION]: WorkflowState.FORM });
});

test('setParams updates navigation to notifications', () => {
  const prevStatePaths = ['navigation.form'];
  const statePaths = ['navigation.notifications'];

  const params = setParams(statePaths, prevStatePaths);
  expect(params).toMatchObject({ [SUB_SECTION]: WorkflowState.NOTIFICATIONS });
});

test('setParams updates navigation to overrides', () => {
  const prevStatePaths = ['navigation.approvals'];
  const statePaths = ['navigation.approvals.overrides'];

  const params = setParams(statePaths, prevStatePaths);
  expect(params).toMatchObject({ [WorkflowState.OVERRIDES]: true });
});

test('setParams updates navigation out of overrides', () => {
  const prevStatePaths = ['navigation.approvals.overrides'];
  const statePaths = ['navigation.approvals.approvers.ready'];

  const params = setParams(statePaths, prevStatePaths);
  expect(params).toHaveProperty(WorkflowState.OVERRIDES);
  expect(params).toMatchObject({ [WorkflowState.OVERRIDES]: undefined });
});
