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

import { mount, createLocalVue } from '@vue/test-utils';
import component from 'tui/components/filters/FilterSidePanel';
import { axe } from 'jest-axe';

const localVue = createLocalVue();
let wrapper;

describe('FilterSidePanel', () => {
  beforeAll(() => {
    localVue.directive('focus-within', {});

    wrapper = mount(component, {
      localVue,
      propsData: {
        title: 'title text',
        value: {
          optionA: 'aa',
          optionB: '',
          optionC: 'cc',
          optionD: '',
        },
      },
      mocks: {
        $str: function() {
          return 'tempstring';
        },
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
