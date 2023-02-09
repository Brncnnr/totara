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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @module totara_notification
 */

import NotificationTable from 'totara_notification/components/table/NotificationTable';
import { shallowMount } from '@vue/test-utils';

let wrapper;

const props = {
  contextId: 1,
  extendedContext: {},
  resolvers: {},
};

describe('NotificationTable', () => {
  beforeEach(() => {
    wrapper = shallowMount(NotificationTable, {
      mocks: {
        $str(identifier, component, param) {
          return param
            ? `[${identifier}, ${component} - ${param}]`
            : `[${identifier}, ${component}]`;
        },
        $url(path, params) {
          const start = '';
          return (
            path +
            '?' +
            Object.entries(params).reduce((prev, option) => {
              return prev + '&' + option.join('=');
            }, start)
          );
        },
      },
      propsData: props,
    });
  });

  it('isNaturalContext works as expected', () => {
    let result = wrapper.vm.isNaturalContext(null);
    expect(result).toBeTrue();

    result = wrapper.vm.isNaturalContext({});
    expect(result).toBeTrue();

    result = wrapper.vm.isNaturalContext({
      component: '',
      area: '',
      item_id: 0,
    });
    expect(result).toBeTrue();

    result = wrapper.vm.isNaturalContext({
      component: 'test_component',
      area: 'test_area',
      item_id: 123,
    });
    expect(result).toBeFalse();
  });

  it('isDefinedInThisContext works as expected', () => {
    // 1 This is natural and pref is same natural.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {},
      resolvers: {},
    });
    let result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: '',
        area: '',
        item_id: 0,
      },
    });
    expect(result).toBeTrue();

    // 2 This is extended and pref is same extended.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {
        component: 'test_component',
        area: 'test_area',
        itemId: 456,
      },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: 'test_component',
        area: 'test_area',
        item_id: 456,
      },
    });
    expect(result).toBeTrue();

    // 3 This is natural and pref is natural with different context_id.
    wrapper.setProps({
      contextId: 123,
      extendedContext: { component: '', area: '', itemId: 0 },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 789,
        component: '',
        area: '',
        item_id: 0,
      },
    });
    expect(result).toBeFalse();

    // 4 This is extended and pref is extended with different context_id.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {
        component: 'test_component',
        area: 'test_area',
        itemId: 456,
      },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 789,
        component: 'test_component',
        area: 'test_area',
        item_id: 456,
      },
    });
    expect(result).toBeFalse();

    // 5 This is extended and pref is natural with same context_id.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {
        component: 'test_component',
        area: 'test_area',
        itemId: 456,
      },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: '',
        area: '',
        item_id: 0,
      },
    });
    expect(result).toBeFalse();

    // This is natural and pref is extended with same context_id.
    wrapper.setProps({
      contextId: 123,
      extendedContext: { component: '', area: '', itemId: 0 },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: 'test_component',
        area: 'test_area',
        item_id: 456,
      },
    });
    expect(result).toBeFalse();

    // This is extended and pref is extended with different component.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {
        component: 'test_component',
        area: 'test_area',
        itemId: 456,
      },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: 'other_component',
        area: 'test_area',
        item_id: 456,
      },
    });
    expect(result).toBeFalse();

    // This is extended and pref is extended with different area.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {
        component: 'test_component',
        area: 'test_area',
        itemId: 456,
      },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: 'test_component',
        area: 'other_area',
        item_id: 456,
      },
    });
    expect(result).toBeFalse();

    // This is extended and pref is extended with different item id.
    wrapper.setProps({
      contextId: 123,
      extendedContext: {
        component: 'test_component',
        area: 'test_area',
        itemId: 456,
      },
      resolvers: {},
    });
    result = wrapper.vm.isDefinedInThisContext({
      extended_context: {
        context_id: 123,
        component: 'test_component',
        area: 'test_area',
        item_id: 789,
      },
    });
    expect(result).toBeFalse();
  });

  it('canAudit works as expected', () => {
    // All true
    wrapper.setProps({
      eventResolvers: [
        {
          plugin_name: 'abc',
          resolvers: [
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
          ],
        },
        {
          plugin_name: 'def',
          resolvers: [
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
          ],
        },
      ],
    });
    expect(wrapper.vm.canAudit).toBeTrue();

    // Some true and some false
    wrapper.setProps({
      eventResolvers: [
        {
          plugin_name: 'abc',
          resolvers: [
            { interactor: { can_audit: true } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: true } },
            { interactor: { can_audit: true } },
          ],
        },
        {
          plugin_name: 'def',
          resolvers: [
            { interactor: { can_audit: true } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: true } },
          ],
        },
      ],
    });
    expect(wrapper.vm.canAudit).toBeTrue();

    // All false
    wrapper.setProps({
      eventResolvers: [
        {
          plugin_name: 'abc',
          resolvers: [
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
          ],
        },
        {
          plugin_name: 'def',
          resolvers: [
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
            { interactor: { can_audit: false } },
          ],
        },
      ],
    });
    expect(wrapper.vm.canAudit).toBeFalse();
  });
});
