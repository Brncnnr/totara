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

import { shallowMount } from '@vue/test-utils';
import ActionLink from 'tui/components/links/ActionLink';

const props = {
  styleclass: { primary: true, small: true },
  ariaLabel: 'Text that overrides the link text',
  href: 'https://www.google.com/',
  text: 'Text',
};

describe('ActionLink', () => {
  it('passes click event through', () => {
    const directHandler = jest.fn();
    const vueHandler = jest.fn();

    const wrapper = shallowMount(ActionLink, {
      propsData: props,
      listeners: {
        click: vueHandler,
      },
    });

    wrapper.element.addEventListener('click', directHandler);

    wrapper.trigger('click');

    expect(directHandler).toHaveBeenCalled();
    expect(vueHandler).toHaveBeenCalled();
    expect(wrapper.element.href).toBe(props.href);
  });

  it('supresses clicks when disabled', () => {
    const vueHandler = jest.fn();

    const wrapper = shallowMount(ActionLink, {
      propsData: { ...props, disabled: true },
      listeners: {
        click: vueHandler,
      },
    });

    wrapper.trigger('click');

    expect(vueHandler).not.toHaveBeenCalled();
    expect(wrapper.element.href).toBeEmpty();
  });

  it('matches snapshot', () => {
    let wrapper;

    wrapper = shallowMount(ActionLink, { propsData: props });
    expect(wrapper.element).toMatchSnapshot();

    wrapper = shallowMount(ActionLink, {
      propsData: {
        ...props,
        disabled: true,
      },
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
