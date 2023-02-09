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

import { mount } from '@vue/test-utils';
import component from 'tui/components/form/FormRowActionButtons.vue';
import { axe } from 'jest-axe';

let wrapper;
const eventFunc = jest.fn();

describe('FormRowActionButtons', () => {
  beforeAll(() => {
    wrapper = mount(component, {
      listeners: {
        cancel: eventFunc,
        submit: eventFunc,
      },
      propsData: { id: 'example' },
      mocks: {
        $id: x => 'id-' + x,
      },
      stubs: ['ButtonCancel', 'ButtonSubmit'],
    });
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
