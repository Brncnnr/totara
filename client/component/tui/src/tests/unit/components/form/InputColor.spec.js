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
 * @author Dave Wallace <dave.wallace@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import component from 'tui/components/form/InputColor.vue';
import { axe } from 'jest-axe';

let wrapper;
const inputEventFunc = jest.fn();
const badValue = 'red';
const goodValue = '#FF0000';

describe('InputColor', () => {
  beforeAll(() => {
    wrapper = shallowMount(component, {
      propsData: {
        ariaDescribedby: '#ariaDescription',
        ariaLabel: 'aria label',
        ariaLabelledby: '#ariaLabel',
        disabled: true,
        id: 'inputid',
        maxlength: 7,
        name: 'colorinput',
        readonly: true,
        required: true,
        value: goodValue,
      },
      listeners: {
        input: inputEventFunc,
      },
      mocks: {
        $id: () => 'inputid',
        $str: (x, y) => `[[${x}, ${y}]]`,
      },
    });
  });

  it('disabled can be set', () => {
    let propValue = wrapper.find('#inputid').props().disabled;
    expect(propValue).toBeTruthy();
  });

  it('maxlength can be set', () => {
    let propValue = wrapper.find('#inputid').props().maxlength;
    expect(propValue).toEqual(7);
  });

  it('name can be set', () => {
    let propValue = wrapper.find('#inputid').props().name;
    expect(propValue).toEqual('colorinput');
  });

  it('readonly can be set', () => {
    let propValue = wrapper.find('#inputid').props().readonly;
    expect(propValue).toBeTruthy();
  });

  it('required can be set', () => {
    let propValue = wrapper.find('#inputid').props().required;
    expect(propValue).toBeTruthy();
  });

  it('Valid value can be set', () => {
    let propValue = wrapper.find('#inputid').props().value;
    expect(propValue).toEqual(goodValue);
  });

  it('Invalid value cannot be set', () => {
    wrapper.setProps({ value: badValue });
    let propValue = wrapper.find('#inputid').props().value;
    expect(propValue).toEqual(goodValue);
  });

  it('Input event can be triggered', () => {
    let input = wrapper.find('#inputid');
    input.vm.$emit('input', goodValue);
    expect(inputEventFunc).toHaveBeenCalled();
  });

  it('should not have an a11y violations', async () => {
    const results = await axe(wrapper.element);
    expect(results).toHaveNoViolations();
  });

  it('Checks snapshot has not changed', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
