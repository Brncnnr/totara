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
import component from 'tui/components/form/InputNumber.vue';
let wrapper;
const inputEventFunc = jest.fn();
const submitEventFunc = jest.fn();

describe('InputNumber', () => {
  beforeAll(() => {
    wrapper = shallowMount(component, {
      propsData: {
        ariaDescribedby: '#ariaDescription',
        ariaLabel: 'aria label',
        ariaLabelledby: '#ariaLabel',
        autocomplete: 'on',
        autofocus: true,
        disabled: true,
        id: 'inputid',
        max: 100,
        min: 0,
        name: 'phoneinput',
        pattern: '[A-Za-z]',
        placeholder: 'phone number here',
        readonly: true,
        required: true,
        step: 20,
        value: 'foo',
      },
      listeners: {
        input: inputEventFunc,
        submit: submitEventFunc,
      },
    });
  });

  it('ariaDescribedby can be set', () => {
    let propValue = wrapper.find('#inputid').props().ariaDescribedby;
    expect(propValue).toEqual('#ariaDescription');
  });

  it('ariaLabel can be set', () => {
    let propValue = wrapper.find('#inputid').props().ariaLabel;
    expect(propValue).toEqual('aria label');
  });

  it('ariaLabelledby can be set', () => {
    let propValue = wrapper.find('#inputid').props().ariaLabelledby;
    expect(propValue).toEqual('#ariaLabel');
  });

  it('autocomplete can be set', () => {
    let propValue = wrapper.find('#inputid').props().autocomplete;
    expect(propValue).toEqual('on');
  });

  it('autofocus can be set', () => {
    let propValue = wrapper.find('#inputid').props().autofocus;
    expect(propValue).toBeTruthy();
  });

  it('disabled can be set', () => {
    let propValue = wrapper.find('#inputid').props().disabled;
    expect(propValue).toBeTruthy();
  });

  it('max can be set', () => {
    let propValue = wrapper.find('#inputid').props().max;
    expect(propValue).toEqual(100);
  });

  it('minlength can be set', () => {
    let propValue = wrapper.find('#inputid').props().min;
    expect(propValue).toEqual(0);
  });

  it('name can be set', () => {
    let propValue = wrapper.find('#inputid').props().name;
    expect(propValue).toEqual('phoneinput');
  });

  it('pattern can be set', () => {
    let propValue = wrapper.find('#inputid').props().pattern;
    expect(propValue).toEqual('[A-Za-z]');
  });

  it('placeholder can be set', () => {
    let propValue = wrapper.find('#inputid').props().placeholder;
    expect(propValue).toEqual('phone number here');
  });

  it('readonly can be set', () => {
    let propValue = wrapper.find('#inputid').props().readonly;
    expect(propValue).toBeTruthy();
  });

  it('required can be set', () => {
    let propValue = wrapper.find('#inputid').props().required;
    expect(propValue).toBeTruthy();
  });

  it('step can be set', () => {
    let propValue = wrapper.find('#inputid').props().step;
    expect(propValue).toEqual(20);
  });

  it('Value can be set', () => {
    let propValue = wrapper.find('#inputid').props().value;
    expect(propValue).toEqual('foo');
  });

  it('Input event can be triggered', () => {
    let input = wrapper.find('#inputid');
    input.vm.$emit('input', 'some value');
    expect(inputEventFunc).toHaveBeenCalled();
  });

  it('Submit event can be triggered', () => {
    let input = wrapper.find('#inputid');
    input.vm.$emit('submit');
    expect(submitEventFunc).toHaveBeenCalled();
  });

  it('Checks snapshot has not changed', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
