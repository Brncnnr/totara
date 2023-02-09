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
 * @module totara_engage
 */

import Vue from 'vue';
import { mount } from '@vue/test-utils';
import component from 'totara_engage/components/modal/EngageAdderModal';
import { axe } from 'jest-axe';

Vue.directive('focus-within', {});

let wrapper;

const propsData = {
  open: true,
  existingItems: [1, 2],
  units: 0,
  gridDirection: 'undefined',
  title: 'Test',
  cards: [],
  filterComponent: 'filterComponent',
  filterArea: 'filterArea',
};

describe('AdderModal', () => {
  beforeAll(() => {
    let counter = 0;
    wrapper = mount(component, {
      propsData,
      mocks: {
        $str: function() {
          return 'tempString';
        },
        $apollo: {
          loading: false,
        },
        $id: function(id) {
          return `id-${id || counter++}`;
        },
      },
      stubs: ['CloseButton', 'ContributionFilter'],
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
