/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

import { mount } from '@vue/test-utils';
import ButtonAria from 'tui/components/buttons/ButtonAria';
import { axe } from 'jest-axe';

// Simple button implementation for testing
const SimpleButton = {
  props: ['disabled'],
  render(h) {
    return h(ButtonAria, [
      h(
        'div',
        {
          attrs: { disabled: this.disabled },
          on: { click: e => this.$emit('click', e) },
        },
        ['foo']
      ),
    ]);
  },
};

describe('ButtonAria', () => {
  it('has accessible attributes', () => {
    const wrapper = mount(SimpleButton);
    expect(wrapper.attributes()).toEqual({
      role: 'button',
      tabindex: '0',
    });
  });

  it('emits click event on button click', () => {
    const click = jest.fn();
    const wrapper = mount(SimpleButton, {
      listeners: { click },
    });

    wrapper.trigger('click');
    expect(click).toHaveBeenCalled();
    click.mockClear();

    wrapper.trigger('keydown.enter');
    expect(click).toHaveBeenCalled();
    click.mockClear();

    wrapper.trigger('keyup.space');
    expect(click).toHaveBeenCalled();
    click.mockClear();
  });

  it('behaves appropriately when button is disabled', () => {
    const click = jest.fn();
    const wrapper = mount(SimpleButton, {
      propsData: { disabled: true },
      listeners: { click },
    });
    wrapper.trigger('click');
    expect(click).not.toHaveBeenCalled();
    expect(wrapper.attributes()).toEqual({
      role: 'button',
      disabled: 'disabled',
      'aria-disabled': 'true',
    });
  });

  it('matches snapshot', () => {
    const wrapper = mount(SimpleButton);
    expect(wrapper.element).toMatchSnapshot();

    const disabledWrapper = mount(SimpleButton, {
      propsData: { disabled: true },
    });
    expect(disabledWrapper.element).toMatchSnapshot('disabled');
  });

  it('should not have any accessibility violations', async () => {
    const wrapper = mount(SimpleButton);
    const results = await axe(wrapper.element);
    expect(results).toHaveNoViolations();
  });
});
