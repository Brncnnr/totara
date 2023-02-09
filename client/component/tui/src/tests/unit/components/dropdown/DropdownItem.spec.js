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
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import Vue from 'vue';

let wrapper;
const dropdownSelected = 'val';
const Dropdown = {
  template: '<dropdown></dropdown>',
  data() {
    return {
      selected: dropdownSelected,
    };
  },
};

describe('DropdownItem', () => {
  beforeEach(() => {
    wrapper = shallowMount(DropdownItem, {
      parentComponent: Dropdown,
      propsData: { isDropdown: true },
    });
  });

  it('render correctly', () => {
    expect(wrapper.html()).toMatchSnapshot();
  });

  it('returns item classes accordingly', async () => {
    const value = dropdownSelected;
    const disabled = false;
    const noPadding = true;
    wrapper.setProps({
      value,
      disabled,
      noPadding,
    });
    await Vue.nextTick();
    expect(wrapper.classes()).toContain('tui-dropdownItem--noPadding');
  });
});
