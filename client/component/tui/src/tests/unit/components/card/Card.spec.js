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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import component from 'tui/components/card/Card';
const eventFunc = jest.fn();
import { axe } from 'jest-axe';

let wrapper;

describe('Card', () => {
  beforeAll(() => {
    wrapper = mount(component, {
      propsData: {
        id: 'card',
        clickable: true,
      },
      directives: {
        'focus-within': eventFunc,
      },
    });
  });

  it('clickable can be set', () => {
    let propValue = wrapper.find('#card').props().clickable;
    expect(propValue).toBeTruthy();
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('should not have any accessibility violations', async () => {
    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });
});
