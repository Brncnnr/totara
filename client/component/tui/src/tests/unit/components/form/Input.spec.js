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
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import component from 'tui/components/form/Input.vue';
let wrapper;
const eventFunc = jest.fn();

describe('Input', () => {
  beforeAll(() => {
    wrapper = shallowMount(component, {
      propsData: {
        type: 'text',
        autofocus: true,
      },
      attachToDocument: true,
      listeners: {
        input: eventFunc,
        change: eventFunc,
        submit: eventFunc,
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks for focus', () => {
    expect(wrapper.element).toBe(document.activeElement);
  });
});
