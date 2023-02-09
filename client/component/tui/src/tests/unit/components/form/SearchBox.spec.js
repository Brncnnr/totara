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
import component from 'tui/components/form/SearchBox';
import { axe } from 'jest-axe';
import Vue from 'vue';

let wrapper;

describe('SearchBox', () => {
  beforeAll(() => {
    wrapper = mount(component, {
      propsData: {
        id: 'tempid',
        dropLabel: false,
        ariaLabel: 'label',
      },
      mocks: {
        $str: function() {
          return 'tempstring';
        },
      },
      stubs: ['SearchIcon'],
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

  it('should have a clear icon when characters have been entered', async () => {
    wrapper = mount(component, {
      propsData: {
        id: 'tempid',
        dropLabel: false,
        ariaLabel: 'label',
        value: '',
      },
      mocks: {
        $str: function() {
          return 'tempstring';
        },
      },
      attachToDocument: true,
    });

    expect(wrapper.find('.tui-searchBox__clearContainer').exists()).toBeFalse();

    wrapper.setProps({ value: 'A' });
    await Vue.nextTick();
    expect(wrapper.find('.tui-searchBox__clearContainer').exists()).toBeTrue();

    wrapper.setProps({ value: 'A Name' });
    await Vue.nextTick();
    expect(wrapper.find('.tui-searchBox__clearContainer').exists()).toBeTrue();

    wrapper.find('.tui-searchBox__clearContainer').element.click();
    await Vue.nextTick();
    expect(wrapper.emittedByOrder().map(e => [e.name, e.args[0]])).toEqual([
      ['clear', undefined],
      ['input', ''],
    ]);

    expect(wrapper.find('input').element).toBe(document.activeElement);
  });
});
