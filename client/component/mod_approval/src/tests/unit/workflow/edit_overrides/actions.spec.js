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

import * as actions from 'mod_approval/workflow/add_or_edit_override/actions';
import makeContext from 'mod_approval/workflow/add_or_edit_override/context';
import { SAVE } from 'mod_approval/workflow/add_or_edit_override/state';
import { ApproverType as aTypes } from 'mod_approval/constants';

const APPROVAL_LEVEL_ID_1 = '1';
const APPROVAL_LEVEL_ID_2 = '2';

const overrideAssignment = {
  assignment: {
    id: '1',
    name: 'Sub agency A',
  },
  assignment_approval_levels: [
    {
      approval_level: {
        id: APPROVAL_LEVEL_ID_1,
        name: 'Immediate Supervisor',
        ordinal_number: 1,
      },
      approvers: [],
      inherited_from_assignment_approval_level: {},
    },
    {
      approval_level: {
        id: APPROVAL_LEVEL_ID_2,
        name: '2nd level',
        ordinal_number: 2,
      },
      approvers: [
        {
          type: 'USER',
          approver_entity: {
            id: '123',
            name: 'Alicia Lopez',
          },
        },
      ],
      inherited_from_assignment_approval_level: null,
    },
  ],
};

const approverTypes = [
  {
    label: 'Relationship',
    type: aTypes.RELATIONSHIP,
    options: [
      {
        identifier: 6,
        idnumber: 'manager',
        name: 'Manager',
      },
    ],
  },
  { type: aTypes.USER, options: null, label: 'individual' },
];

test('setApproversVariables', () => {
  const context = makeContext({ overrideAssignment, approverTypes });
  const formValues = actions.setFormValues.assignment.formValues(context);
  context.formValues = formValues;

  context.formValues[aTypes.USER][APPROVAL_LEVEL_ID_1] = [
    { id: '456' }, //added
  ];

  context.formValues[aTypes.USER][APPROVAL_LEVEL_ID_2] = [
    { id: '123' }, //existing
    { id: '789' }, //added
  ];

  context.formValues.approversChanged[APPROVAL_LEVEL_ID_1] = true;
  context.formValues.approversChanged[APPROVAL_LEVEL_ID_2] = true;
  context.formValues.approverTypes[APPROVAL_LEVEL_ID_1] = aTypes.USER;
  context.formValues.approverTypes[APPROVAL_LEVEL_ID_2] = aTypes.USER;
  context.formValues.overridden[APPROVAL_LEVEL_ID_1] = true;
  context.formValues.overridden[APPROVAL_LEVEL_ID_2] = true;

  const event = { type: SAVE };
  const { assignment } = actions.setApproversVariables;
  const variables = assignment.approversVariables(context, event);

  expect(variables).toHaveProperty('length', 2);
  expect(variables[0]).toMatchObject({
    input: {
      approval_level_id: APPROVAL_LEVEL_ID_1,
      approvers: [
        {
          assignment_approver_type: aTypes.USER,
          identifier:
            context.formValues[aTypes.USER][APPROVAL_LEVEL_ID_1][0].id,
        },
      ],
    },
  });
});
