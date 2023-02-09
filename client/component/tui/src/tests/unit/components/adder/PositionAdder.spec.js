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
 * @author Arshad Anwer <Arshad.Anwer@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import PositionAdder from 'tui/components/adder/PositionAdder';
import { axe } from 'jest-axe';

let wrapper;

const propsData = {
  open: true,
  'existing-items': [1, 2],
};

describe('PositionAdder', () => {
  beforeAll(() => {
    let counter = 0;
    wrapper = mount(PositionAdder, {
      propsData,
      mocks: {
        $apollo: {
          addSmartQuery: function() {},
          loading: false,
        },
        hierarchyData: function() {
          return { items: [] };
        },
        $id: function(id) {
          return `id-${id || counter++}`;
        },
      },
      stubs: ['CloseButton', 'FilterBar'],
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
