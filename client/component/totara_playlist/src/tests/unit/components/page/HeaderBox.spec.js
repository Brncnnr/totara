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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module totara_playlist
 */

import HeaderBox from 'totara_playlist/components/page/HeaderBox';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import InputText from 'tui/components/form/InputText';
import { mount } from '@vue/test-utils';
import Vue from 'vue';

Vue.directive('focus-within', {});
describe('HeaderBox', () => {
  let headerBox = null;

  beforeEach(() => {
    headerBox = mount(HeaderBox, {
      propsData: {
        title: 'Hello world',
        updateAble: false,
        playlistId: 15,
      },
      attachToDocument: true,
      mocks: {
        $str(a, b) {
          return `${a},${b}`;
        },
      },
    });
  });

  it('matches snapshot', () => {
    expect(headerBox.element).toMatchSnapshot();
  });

  it('checks the edit button should be rendered but hidden by default', () => {
    let button = headerBox.find(ButtonIcon);
    expect(button.exists()).toBeTrue();
    expect(button.isVisible()).toBeFalse();
  });

  it('checks the edit button should be visible when prop is changed', async () => {
    headerBox.setProps({ updateAble: true });
    expect(headerBox.props('updateAble')).toBeTrue();

    await headerBox.vm.$nextTick();
    let button = headerBox.find(ButtonIcon);

    expect(button.exists()).toBeTrue();
    expect(button.isVisible()).toBeTrue();
  });

  it('checks the rendering of input element', async () => {
    expect(headerBox.contains(InputText)).toBeFalse();
    expect(headerBox.contains(ButtonIcon)).toBeTrue();
    headerBox.setData({ editing: true });

    await headerBox.vm.$nextTick();
    expect(headerBox.contains(ButtonIcon)).toBeFalse();
    expect(headerBox.contains(InputText)).toBeTrue();
  });

  it('checks the auto focus of edit button and input element', async () => {
    // We have to make sure that the update-able to be true, so that the click
    // on button is able to trigger.
    headerBox.setProps({ updateAble: true });
    await headerBox.vm.$nextTick();

    expect(headerBox.vm.editing).toBeFalse();
    let button = headerBox.find(ButtonIcon);
    expect(button.element).not.toBe(document.activeElement);

    button.trigger('click');

    // Editing is now on true.
    expect(headerBox.vm.editing).toBeTrue();
    await headerBox.vm.$nextTick();

    expect(headerBox.contains(InputText)).toBeTrue();
    expect(headerBox.contains(ButtonIcon)).toBeFalse();

    let input = headerBox.find(InputText);
    expect(input.element).toBe(document.activeElement);

    input.trigger('keydown.esc');

    await headerBox.vm.$nextTick();

    expect(headerBox.contains(InputText)).toBeFalse();
    expect(headerBox.contains(ButtonIcon)).toBeTrue();

    // Reload the button, as it has been cleared out from the wrapper.
    button = headerBox.find(ButtonIcon);
    expect(button.element).toBe(document.activeElement);
  });

  it('checks the button is disrepect when not update able', async () => {
    let button = headerBox.find(ButtonIcon);
    expect(headerBox.contains(InputText)).toBeFalse();

    button.trigger('click');
    expect(headerBox.contains(InputText)).toBeFalse();
  });
});
