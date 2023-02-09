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
import collapsible from 'tui/components/collapsible/Collapsible';
import { axe } from 'jest-axe';

let wrapper;

describe('collapsible', () => {
  beforeAll(() => {
    wrapper = shallowMount(collapsible, {
      propsData: { label: 'hello', value: true, initialState: true },
      mocks: {
        $id: x => 'id' + x,
        $str: function() {
          return 'fff';
        },
      },
      scopedSlots: {
        default({ empty }) {
          return (
            !empty &&
            this.$createElement('div', { class: 'test-action' }, 'hello')
          );
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
