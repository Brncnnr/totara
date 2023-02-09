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

import * as actions from 'mod_approval/workflow/edit/actions';
import makeContext from 'mod_approval/workflow/edit/context';
import { UPDATE_APPROVAL_LEVEL_APPROVERS } from 'mod_approval/workflow/edit/state/persistence';
import { ApproverType } from 'mod_approval/constants';

test('addToQueue', () => {
  const { assignment } = actions.addToQueue;
  const context = makeContext({});
  const inputId = '123';
  const variables = {
    input: {
      assignment_id: '1',
      approval_level_id: '2',
      approvers: [
        {
          assignment_approver_type: ApproverType.USER,
          identifier: '3',
        },
        {
          assignment_approver_type: ApproverType.USER,
          identifier: '4',
        },
      ],
    },
  };

  const event = {
    type: UPDATE_APPROVAL_LEVEL_APPROVERS,
    variables,
    inputId,
  };

  const updatedContext = assignment(context, event);
  expect(updatedContext).toMatchObject({
    mutationQueue: [
      {
        type: UPDATE_APPROVAL_LEVEL_APPROVERS,
        variables,
        inputId,
      },
    ],
  });
});

test('setActiveMutation', () => {
  const { assignment } = actions.setActiveMutation;
  const context = makeContext({});
  const inputId = '123';
  const variables = {
    input: {
      assignment_id: '1',
      approval_level_id: '2',
      approvers: [
        {
          assignment_approver_type: ApproverType.USER,
          identifier: '3',
        },
        {
          assignment_approver_type: ApproverType.USER,
          identifier: '4',
        },
      ],
    },
  };
  const mutation = {
    type: UPDATE_APPROVAL_LEVEL_APPROVERS,
    variables,
    inputId,
  };
  context.mutationQueue = [mutation];
  const updatedContext = assignment(context);

  expect(updatedContext).toMatchObject({
    mutationQueue: [],
    activeMutation: mutation,
  });
});
