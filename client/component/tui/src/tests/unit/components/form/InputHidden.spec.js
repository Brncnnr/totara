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

import { shallowMount } from '@vue/test-utils';
import component from 'tui/components/form/InputHidden.vue';
let wrapper;

describe('InputHidden', () => {
  beforeAll(() => {
    wrapper = shallowMount(component, {
      propsData: {
        autocomplete: 'on',
        disabled: true,
        id: 'inputid',
        name: 'hiddeninput',
        readonly: true,
        value: 'hidden value',
      },
    });
  });

  it('autocomplete can be set', () => {
    let propValue = wrapper.find('#inputid').props().autocomplete;
    expect(propValue).toEqual('on');
  });

  it('disabled can be set', () => {
    let propValue = wrapper.find('#inputid').props().disabled;
    expect(propValue).toBeTruthy();
  });

  it('name can be set', () => {
    let propValue = wrapper.find('#inputid').props().name;
    expect(propValue).toEqual('hiddeninput');
  });

  it('readonly can be set', () => {
    let propValue = wrapper.find('#inputid').props().readonly;
    expect(propValue).toBeTruthy();
  });

  it('Value can be set', () => {
    let propValue = wrapper.find('#inputid').props().value;
    expect(propValue).toEqual('hidden value');
  });

  it('Checks snapshot has not changed', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
