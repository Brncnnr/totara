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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import component from 'tui/components/buttons/Button.vue';
import { axe } from 'jest-axe';

describe('Button', () => {
  it('emits click event on button click', () => {
    const click = jest.fn();
    const wrapper = shallowMount(component, {
      propsData: { text: 'text' },
      listeners: { click },
    });
    wrapper.find('button').trigger('click');
    expect(click).toHaveBeenCalled();
  });

  it('does not emit click event when button is disabled or loading', () => {
    const click = jest.fn();
    const wrapper = shallowMount(component, {
      propsData: {
        text: 'text',
        loading: true,
      },
      listeners: { click },
    });
    wrapper.find('button').trigger('click');
    expect(click).not.toHaveBeenCalled();
  });

  it('matches snapshot', () => {
    const wrapper = shallowMount(component, {
      propsData: { text: 'btn text' },
    });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('should not have any accessibility violations', async () => {
    const wrapper = shallowMount(component, {
      propsData: { text: 'text' },
    });
    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });

  it('should support autofocus', () => {
    const wrapper = shallowMount(component, {
      propsData: {
        text: 'btn text',
        autofocus: true,
      },
      attachToDocument: true,
    });
    expect(wrapper.element).toBe(document.activeElement);
  });

  it.each([
    [null, undefined],
    [true, 'true'],
    ['true', 'true'],
    [false, 'false'],
    ['false', 'false'],
  ])('should set aria-disabled appropriately when passed %p', (val, attr) => {
    const wrapper = shallowMount(component, {
      propsData: {
        text: 'text',
        ariaDisabled: val,
      },
    });
    expect(wrapper.find('button').attributes('aria-disabled')).toBe(attr);
  });
});
