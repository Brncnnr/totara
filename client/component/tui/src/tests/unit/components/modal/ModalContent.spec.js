/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import ModalContent from 'tui/components/modal/ModalContent';

describe('ModalContent', () => {
  it('Confirm validation works', async () => {
    let oldError = global.console.error;
    global.console.error = jest.fn();
    let wrapper = shallowMount(ModalContent, {
      slots: {
        default: ['content'],
        buttons: ['buttons'],
        title: ['current'],
      },
      mocks: {
        $id: x => 'id-' + x,
        $str: (x, y) => `[[${x}, ${y}]]`,
      },
      propsData: {
        title: '',
      },
    });
    expect(wrapper.vm.title).toBeFalsy();
    expect(wrapper.vm.$slots.title[0].text).toBe('current');
    expect(wrapper.vm.$_isAccessibleTitle()).toBeTrue();

    // Mutation Observer does not detect changes to slot text
    wrapper.vm.$slots.title[0].text = '';
    expect(wrapper.vm.$slots.title[0].text).toBe('');
    expect(wrapper.vm.$_isAccessibleTitle()).toBeFalse();

    wrapper.vm.$slots.title[0].text = '   \n \t ';
    expect(wrapper.vm.$_isAccessibleTitle()).toBeFalse();

    // Mutation observer detects change in title property
    wrapper.vm.title = '   \n \t ';
    expect(wrapper.vm.$_isAccessibleTitle()).toBeFalse();

    wrapper.vm.title = 'Modal title';
    expect(wrapper.vm.$_isAccessibleTitle()).toBeTrue();

    // Reset global console.error handler
    global.console.error = oldError;
  });
});
