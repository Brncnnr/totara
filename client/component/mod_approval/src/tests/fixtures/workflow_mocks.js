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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module mod_approval
 */

const mock = {
  relationship(id, idnumber, name) {
    return {
      __typename: 'totara_core_relationship',
      id: String(id),
      idnumber,
      name,
    };
  },
  user(id, firstName, lastName) {
    const fullname = `${firstName} ${lastName}`;
    const url = `http://example.com/user/profile.php?id=${id}`;
    const cardField = (label = null, value = null, url = null) => ({
      __typename: 'core_user_card_display_field',
      associate_url: url,
      label,
      value,
      is_custom: false,
    });
    return {
      __typename: 'core_user',
      id: String(id),
      name: fullname,
      fullname,
      card_display: {
        __typename: 'core_user_card_display',
        profile_picture_alt: null,
        profile_picture_url: `http://example.com/theme/image.php/ventura/core/1234${id}/u/f1`,
        profile_url: url,
        display_fields: [
          cardField('Full name', fullname, url),
          cardField('Department'),
          cardField(),
          cardField(),
        ],
      },
    };
  },
  approver(id, type, entity) {
    return {
      id: String(id),
      type,
      identifier: parseInt(entity.id),
      approver_entity: entity,
    };
  },
  level(id, ordinalNumber, name, approvers) {
    return {
      id: String(id),
      name,
      active: true,
      ordinal_number: ordinalNumber,
      created: '11/23/2020',
      updated: '11/23/2020',
      approvers,
    };
  },
  stage(id, ordinalNumber, name, levels) {
    return {
      id: String(id),
      name: name,
      ordinal_number: ordinalNumber,
      approval_levels: levels,
    };
  },
};

const approverTypes = [
  {
    label: 'Relationship',
    type: 'RELATIONSHIP',
    options: [
      {
        identifier: 6,
        idnumber: 'manager',
        name: 'Manager',
      },
      // Add a few more relationship types
      {
        identifier: 7,
        idnumber: 'managers_manager',
        name: "Manager's manager",
      },
      {
        identifier: 8,
        idnumber: 'appraiser',
        name: 'Appraiser',
      },
    ],
  },
  {
    label: 'Individual',
    type: 'USER',
    options: null,
  },
];

const stages = [
  mock.stage(101, 1, 'Request Approval', [
    mock.level(201, 1, 'Immediate Supervisor', [
      mock.approver(
        302,
        'RELATIONSHIP',
        mock.relationship(6, 'manager', 'Manager')
      ),
      // Add appraiser for testing
      mock.approver(
        301,
        'RELATIONSHIP',
        mock.relationship(8, 'appraiser', 'Appraiser')
      ),
    ]),
    mock.level(202, 2, 'Second-level Approver', [
      mock.approver(305, 'USER', mock.user(3, 'Anthony', 'Blake')),
    ]),
    mock.level(205, 3, 'Third-level Approver', [
      mock.approver(303, 'USER', mock.user(4, 'Leonard', 'Cameron')),
    ]),
    mock.level(204, 4, 'Fourth-level Approver', [
      mock.approver(306, 'USER', mock.user(6, 'Carol', 'Terry')),
    ]),
  ]),
  mock.stage(103, 2, 'Training', []),
  mock.stage(102, 3, 'Verification', [
    mock.level(203, 1, 'Verification Approver', [
      mock.approver(304, 3, 'USER', mock.user(5, 'Sarah', 'Ellison')),
    ]),
  ]),
];

const latest_version = {
  status: 2,
  status_label: 'Active',
  stages,
};

const workflow_type = {
  id: '1',
  name: 'Test Workflow Type',
};

const default_assignment = {
  id: '1',
  assignment_type_label: 'Organisation',
  assigned_to: {
    __typename: 'totara_hierarchy_organisation',
    id: '1',
    fullname: 'Agency',
  },
};

const interactor = {
  can_edit: true,
  can_archive: true,
  can_unarchive: false,
  can_activate: false,
  can_clone: true,
  can_delete: false,
  can_upload_approver_overrides: true,
  can_assign_roles: true,
  can_publish: true,
  can_edit_without_invalidating: false,
};

const workflow = {
  __typename: 'mod_approval_workflow',
  id: '1',
  id_number: 'workflow-test-1234',
  context_id: '42',
  name: 'Test Workflow',
  description: null,
  latest_version,
  workflow_type,
  default_assignment,
  interactor,
};

const selectableUsers = {
  items: [
    mock.user(2, 'Admin', 'User'),
    mock.user(3, 'Anthony', 'Blake'),
    mock.user(6, 'Carol', 'Terry'),
    mock.user(4, 'Leonard', 'Cameron'),
    mock.user(9, 'Olivia', 'Parsons'),
    mock.user(5, 'Sarah', 'Ellison'),
  ],
  total: 6,
  next_cursor: '',
};

export { approverTypes, selectableUsers, workflow };
