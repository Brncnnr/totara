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
import Select from 'tui/components/form/Select';
import { axe } from 'jest-axe';

describe('Select', () => {
  let handleInput, wrapper;
  beforeAll(() => {
    handleInput = jest.fn();
    wrapper = shallowMount(Select, {
      propsData: {
        options: [
          'abc',
          { id: 'def', label: 'ghi' },
          {
            label: 'capitals',
            options: ['ABC', { id: 'DEF', label: 'GHI' }],
          },
        ],
        ariaLabel: 'Letters',
      },
      listeners: {
        input: handleInput,
      },
    });
  });

  it('matches snapshot', () => {
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
