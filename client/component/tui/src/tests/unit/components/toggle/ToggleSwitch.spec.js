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
import component from 'tui/components/toggle/ToggleSwitch.vue';
import Vue from 'vue';
import { axe } from 'jest-axe';

const inputEventFunc = jest.fn();
let wrapper;
let propsData;
let listeners;
let button;

describe('ToggleSwitch', () => {
  beforeEach(() => {
    propsData = {
      value: false,
      disabled: false,
      text: 'toggle button text',
      id: 'toggle',
    };
    listeners = {
      input: inputEventFunc,
    };
  });

  inputEventFunc.mockClear();

  describe('Toggles aria-pressed on click', () => {
    it('adds aria-pressed', async () => {
      wrapper = shallowMount(component, { propsData, listeners });
      button = wrapper.find('button');

      let propValue = wrapper.props().value;
      expect(propValue).toBeFalsy();

      expect(button.attributes('aria-pressed')).toBeUndefined();
      button.trigger('click');
      expect(inputEventFunc).toHaveBeenCalled();

      wrapper.setProps({
        value: true,
      });
      await Vue.nextTick();

      propValue = wrapper.props().value;
      expect(propValue).toBeTruthy();

      expect(button.attributes('aria-pressed')).toBe('true');
    });

    it('does nothing if the button is disabled', async () => {
      wrapper = shallowMount(component, { propsData, listeners });
      button = wrapper.find('button');

      expect(button.attributes('aria-pressed')).toBeUndefined();

      wrapper.setProps({
        disabled: true,
      });

      button.trigger('click');
      await Vue.nextTick();
      expect(button.attributes('aria-pressed')).toBeUndefined();
    });

    it('Renders correctly with something in the slot', () => {
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
        scopedSlots: {
          icon() {
            return this.$createElement('div', {}, ['icon button']);
          },
        },
      });

      expect(wrapper.element).toMatchSnapshot();
    });

    it('Renders correctly with an empty slot and some text', () => {
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
      });

      expect(wrapper.element).toMatchSnapshot();
    });

    it('Renders correctly with an empty slot, an aria-label, and some text', () => {
      propsData = { ...propsData, ariaLabel: 'Aria label text' };
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
      });

      expect(wrapper.element).toMatchSnapshot();
    });

    it('Renders correctly with an empty slot, an aria-label, and no text', () => {
      propsData = { ...propsData, ariaLabel: 'Aria label text' };
      delete propsData.text;
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
      });

      expect(wrapper.element).toMatchSnapshot();
    });

    it('Renders correctly with something in the slot and an aria-label', () => {
      propsData = { ...propsData, ariaLabel: 'Different button text' };
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
        scopedSlots: {
          icon() {
            return this.$createElement('div', {}, ['icon button']);
          },
        },
      });

      expect(wrapper.element).toMatchSnapshot();
    });

    it('should not have any accessibility violations with something in the slot', async () => {
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
        scopedSlots: {
          icon() {
            return this.$createElement('div', {}, ['icon button']);
          },
        },
      });

      const results = await axe(wrapper.element, {
        rules: {
          region: { enabled: false },
        },
      });

      expect(results).toHaveNoViolations();
    });

    it('should not have any accessibility violations with an empty slot', async () => {
      wrapper = shallowMount(component, {
        propsData,
        $mocks: { pressed: false },
      });

      const results = await axe(wrapper.element, {
        rules: {
          region: { enabled: false },
        },
      });

      expect(results).toHaveNoViolations();
    });
  });
});
