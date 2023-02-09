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

import Vue from 'vue';
import { shallowMount, createWrapper } from '@vue/test-utils';
import Modal from 'tui/components/modal/Modal';
import { axe } from 'jest-axe';

let wrapper;
let modalWrapper;

describe('Modal', () => {
  beforeEach(() => {
    wrapper = shallowMount(Modal, {
      slots: {
        default: ['content'],
        buttons: ['buttons'],
      },
      mocks: {
        $id: x => 'id-' + x,
        $str: (x, y) => `[[${x}, ${y}]]`,
      },
      propsData: {
        title: 'Title',
      },
    });
    modalWrapper = createWrapper(wrapper.vm.$refs.modal);
  });

  it('adds modal to body', async () => {
    expect(document.body.contains(modalWrapper.element)).toBeFalse();
    wrapper.setProps({ open: true });
    await Vue.nextTick();
    expect(document.body.contains(modalWrapper.element)).toBeTrue();
  });

  it('checks snapshot', () => {
    expect(modalWrapper.element).toMatchSnapshot();
  });

  it('should not have any accessibility violations', async () => {
    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });

  it('Escape key should dismiss modal', async () => {
    wrapper.setProps({ open: true });
    await Vue.nextTick();
    wrapper.find('.tui-modal__inner').trigger('keydown', { key: 'Escape' });
    expect(wrapper.emitted('close')).toHaveLength(1);

    wrapper.setProps({ open: true });
    await Vue.nextTick();
    // IE11 sends "Esc" instead of "Escape"
    wrapper.find('.tui-modal__inner').trigger('keydown', { key: 'Esc' });
    expect(wrapper.emitted('close')).toHaveLength(2);
  });
});
