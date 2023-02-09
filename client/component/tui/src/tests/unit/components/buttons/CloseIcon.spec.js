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
import CloseIcon from 'tui/components/buttons/CloseIcon';
const mocks = {
  $str: (x, y) => `[[${x}, ${y}]]`,
};

describe('CloseIcon', () => {
  it('allows passing a different label', () => {
    const wrapper = shallowMount(CloseIcon, {
      mocks,
      propsData: { ariaLabel: 'DESTROY' },
    });
    expect(wrapper.find('buttonicon-stub').props().ariaLabel).toBe('DESTROY');
  });

  it('matches snapshot', () => {
    const wrapper = shallowMount(CloseIcon, { mocks });
    expect(wrapper.element).toMatchSnapshot();
  });
});
