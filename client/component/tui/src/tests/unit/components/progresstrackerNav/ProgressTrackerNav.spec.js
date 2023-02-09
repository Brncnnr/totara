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
import component from 'tui/components/progresstracker/ProgressTracker';
import { axe } from 'jest-axe';

let wrapper;

const props = {
  items: [
    {
      id: 1,
      description: 'Basic knowledge description',
      label: 'Basic knowledge',
    },
    {
      id: 2,
      description: 'Competent with supervision description',
      label: 'Competent with supervision',
    },
  ],
  gap: 'medium',
  currentId: 1,
  targetId: 2,
};

describe('ProgressTracker', () => {
  beforeAll(() => {
    wrapper = mount(component, {
      propsData: props,
      mocks: {
        $str: function() {
          return 'tempstring';
        },
      },
      stubs: ['CloseButton'],
    });
  });

  it('should check snapshot', () => {
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
