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

import { createLocalVue, mount } from '@vue/test-utils';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';
import vueXstatePlugin from 'tui_xstate/vue_xstate_plugin';
import workflowEditMachine from 'mod_approval/workflow/edit/machine';
import ApprovalLevel from 'mod_approval/components/workflow/ApprovalLevel.vue';
import ApprovalLevelsManagement from 'mod_approval/components/workflow/ApprovalLevelsManagement.vue';
import IndividualTagList from 'mod_approval/components/workflow/taglist_type/IndividualTagList.vue';
import RelationshipTagList from 'mod_approval/components/workflow/taglist_type/RelationshipTagList.vue';
import TagList from 'tui/components/tag/TagList';
import {
  approverTypes,
  selectableUsers,
  workflow,
} from '../../../fixtures/workflow_mocks';

jest.mock('tui/apollo_client', () => {
  const { ApolloClient } = jest.requireActual('apollo-client');
  const { ApolloLink } = jest.requireActual('apollo-link');
  const { InMemoryCache } = jest.requireActual('apollo-cache-inmemory');
  const knownOperations = [
    'mod_approval_selectable_users',
    'mod_approval_load_workflow',
    'mod_approval_assignment_identifiers',
  ];

  class MockLink extends ApolloLink {
    request({ query: { definitions } }) {
      // Assumes definitions is an array of one query.
      const operation = definitions[0].name.value;
      if (knownOperations.includes(operation)) {
        // Return just a fake Observable instance because we are mocking the apollo client.
        return { subscribe() {} };
      }
      throw new Error(`Operation ${operation} is not mocked`);
    }
  }

  const mockLink = new MockLink();
  const apollo = new ApolloClient({
    cache: new InMemoryCache({ addTypename: false }),
    link: mockLink,
  });

  return apollo;
});

let localVue;

beforeEach(() => {
  localVue = createLocalVue();
  localVue.directive('focus-within', {});
  window.history.replaceState({}, null, '/test.html?application_id=1');
});

test('tag lists inside ApprovalLevelsManagement', () => {
  const draftWorkflow = Object.assign({}, workflow, {
    latest_version: Object.assign(workflow.latest_version, {
      status: 1,
      status_label: 'Draft',
    }),
  });
  const stagesExtendedContexts = draftWorkflow.latest_version.stages.map(
    stage => ({
      component: 'mod_approval',
      area: 'workflow_stage',
      itemId: parseInt(stage.id),
      contextId: parseInt(draftWorkflow.context_id),
    })
  );
  const machine = workflowEditMachine({
    id: MOD_APPROVAL__WORKFLOW_EDIT,
    workflow: draftWorkflow,
    stagesExtendedContexts,
    approverTypes,
  });

  const Parent = {
    components: {
      Child: ApprovalLevelsManagement,
    },
    data() {
      return {};
    },
    xState: {
      machine: () => machine,
    },
    render(h) {
      return h('Child', { ref: 'child' });
    },
  };

  // Taken from tui_vue_plugin.js
  localVue.component('render', {
    functional: true,
    props: ['vnode'],
    render: (h, { props }) => props.vnode,
  });

  // Install plugin
  localVue.use(vueXstatePlugin);

  // Override query result before mount
  machine.context.selectableUsers.mod_approval_selectable_users = selectableUsers;

  const wrapper = mount(Parent, { localVue });
  expect(wrapper.vm.$refs.child.$selectors).not.toBeUndefined();

  const expectedApproverTypeOptions = [
    {
      id: 'RELATIONSHIP',
      label: 'Relationship',
    },
    {
      id: 'USER',
      label: 'Individual',
    },
  ];

  const levels = wrapper.findAll(ApprovalLevel);
  expect(levels).toHaveLength(4);

  const firstLevel = levels.at(0);
  expect(firstLevel.vm.tagList).toBe(RelationshipTagList);
  expect(firstLevel.vm.selectedApproverType).toBe('RELATIONSHIP');
  expect(firstLevel.vm.approverTypeOptions).toMatchObject(
    expectedApproverTypeOptions
  );
  expect(firstLevel.vm.canDelete).toBeTrue();

  const firstLevelApproversTagList = firstLevel
    .find(RelationshipTagList)
    .find(TagList);
  let { items, tags } = firstLevelApproversTagList.vm.$props;
  // items is an array of items supplied through 'approver-types' by approver_type\relationship::options
  // TODO: TL-31105 right now, it contains anything other than manager, which is nothing in reality
  expect(items).toHaveLength(2);
  expect(items[0].name).toBe("Manager's manager");
  expect(items[1].name).toBe('Appraiser');
  // tags is an array of mod_approval_assignment_approver
  // TODO: TL-31105 right now, it always contains just 'manager' regardless of actual database records
  expect(tags).toHaveLength(1);
  expect(tags[0].name).toBe('Manager');
  expect(tags[0].approver_entity.__typename).toBe('totara_core_relationship');
  expect(tags[0].approver_entity.name).toBe('Manager');

  const secondLevel = levels.at(1);
  expect(secondLevel.vm.tagList).toBe(IndividualTagList);
  expect(secondLevel.vm.selectedApproverType).toBe('USER');
  expect(secondLevel.vm.approverTypeOptions).toMatchObject(
    expectedApproverTypeOptions
  );
  expect(secondLevel.vm.canDelete).toBeTrue();

  const secondLevelApprovers = secondLevel.find(IndividualTagList);
  expect(secondLevelApprovers.vm.approverLevel.name).toBe(
    'Second-level Approver'
  );
  expect(secondLevelApprovers.vm.fullnameSearch).toBe('');

  const secondLevelApproversTagList = secondLevelApprovers.find(TagList);
  items = secondLevelApproversTagList.vm.$props.items;
  tags = secondLevelApproversTagList.vm.$props.tags;
  // items is an array of core_user
  expect(items).toHaveLength(5);
  expect(items[0]).toHaveProperty('card_display');
  expect(items[0].name).toBe('Admin User');
  expect(items[1].name).toBe('Carol Terry');
  expect(items[2].name).toBe('Leonard Cameron');
  expect(items[3].name).toBe('Olivia Parsons');
  expect(items[4].name).toBe('Sarah Ellison');
  // tags is an array of mod_approval_assignment_approver
  expect(tags).toHaveLength(1);
  expect(tags[0]).toHaveProperty('approver_entity');
  expect(tags[0].type).toBe('USER');
  expect(tags[0].approver_entity.__typename).toBe('core_user');
  expect(tags[0].approver_entity.name).toBe('Anthony Blake');
});
