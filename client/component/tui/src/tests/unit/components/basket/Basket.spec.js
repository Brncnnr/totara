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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import Vue from 'vue';
import { mount, shallowMount } from '@vue/test-utils';
import Basket from 'tui/components/basket/Basket';
import { axe } from 'jest-axe';

describe('Basket', () => {
  it('disables actions when no items', async () => {
    const wrapper = mount(Basket, {
      propsData: {
        items: [1, 2, 3],
        bulkActions: [
          { label: 'Action', action: () => {} },
          { label: 'Action', action: () => {} },
        ],
      },
      scopedSlots: {
        actions({ empty }) {
          return (
            !empty &&
            this.$createElement('div', { class: 'test-action' }, 'hello')
          );
        },
      },
    });

    expect(wrapper.find('.tui-basket__selectedCount').text()).toBe('3');
    expect(wrapper.find('button').attributes('disabled')).toBeFalsy();
    expect(wrapper.find('.test-action').exists()).toBeTrue();

    wrapper.setProps({ items: [] });
    await Vue.nextTick();

    expect(wrapper.find('.tui-basket__selectedCount').text()).toBe('0');
    expect(wrapper.find('button').attributes('disabled')).toBeTruthy();
    expect(wrapper.find('.test-action').exists()).toBeFalse();
  });

  it('calls action when single bulk action clicked', async () => {
    const action = jest.fn();

    const wrapper = mount(Basket, {
      propsData: {
        items: [1, 2, 3],
        bulkActions: [{ label: 'Action', action: action }],
      },
    });

    expect(action).not.toHaveBeenCalled();

    wrapper.find('button').trigger('click');

    expect(action).toHaveBeenCalledTimes(1);
  });

  it('calls action when multiple bulk action clicked', async () => {
    const action = jest.fn();

    const wrapper = mount(Basket, {
      propsData: {
        items: [1, 2, 3],
        bulkActions: [
          { label: 'Action', action: action },
          { label: 'Other', action: () => {} },
        ],
      },
    });

    expect(action).not.toHaveBeenCalled();

    wrapper.find('a.tui-dropdownItem').trigger('click');

    expect(action).toHaveBeenCalledTimes(1);
  });

  it('matches snapshot', () => {
    const wrapper = shallowMount(Basket, {
      propsData: {
        items: [1, 2, 3],
        bulkActions: [
          { label: 'Action', action: () => {} },
          { label: 'Action', action: () => {} },
        ],
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('should not have any accessibility violations', async () => {
    const action = jest.fn();

    const wrapper = mount(Basket, {
      propsData: {
        items: [1, 2, 3],
        bulkActions: [{ label: 'Action', action: action }],
      },
    });

    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });
});
