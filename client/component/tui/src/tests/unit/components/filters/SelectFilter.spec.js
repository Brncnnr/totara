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
import component from 'tui/components/filters/SelectFilter';
import { axe } from 'jest-axe';

let options;
let wrapper;

describe('SelectFilter', () => {
  beforeAll(() => {
    options = {
      propsData: {
        id: 'tempid',
        dropLabel: false,
        label: 'label text',
        options: [
          {
            id: 'course',
            label: 'Include courses',
          },
        ],
        showLabel: true,
      },
      mocks: {
        $str: function() {
          return 'tempstring';
        },
      },
    };
  });

  describe('SelectFilter with label', () => {
    beforeAll(() => {
      wrapper = mount(component, options);
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

  describe('SelectFilter with dropLabel', () => {
    beforeAll(() => {
      options.propsData.dropLabel = true;
      wrapper = mount(component, options);
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
});
