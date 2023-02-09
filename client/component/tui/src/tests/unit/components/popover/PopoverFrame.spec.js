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
import PopoverFrame from 'tui/components/popover/PopoverFrame';

describe('PopoverFrame', () => {
  it('matches snapshot', () => {
    const wrapper = shallowMount(PopoverFrame, {
      scopedSlots: {
        default() {
          return this.$createElement('div', {}, ['hello']);
        },
      },
    });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('matches snapshot when non-closeable', () => {
    const wrapper = shallowMount(PopoverFrame, {
      propsData: {
        side: 'sunny',
        closeable: false,
      },
      scopedSlots: {
        default() {
          return this.$createElement('div', {}, ['hello']);
        },
      },
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
