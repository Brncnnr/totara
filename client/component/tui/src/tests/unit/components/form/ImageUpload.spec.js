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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import component from 'tui/components/form/ImageUpload.vue';
import Vue from 'vue';

describe('ImageUpload', () => {
  let defaults;

  beforeAll(() => {
    defaults = {
      mocks: {
        $str: function(key, component) {
          return key + ',' + component;
        },
      },
      propsData: {
        href: 'www.example.com',
        itemId: 123,
        repositoryId: 543,
      },
    };
  });

  it('Checks snapshot', () => {
    let wrapper = mount(component, defaults);
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Error functionality loads as expected', async () => {
    let wrapper = mount(component, defaults);
    let error = 'you fail';

    wrapper.vm.handleError({ error: error });
    expect(wrapper.vm.errorMessage).toBe(error);
    expect(wrapper.vm.isError).toBeTrue();
    await Vue.nextTick();
    expect(wrapper.element).toMatchSnapshot();

    wrapper.vm.handleFileLoaded({ file: { url: 'www.example.com' } });
    expect(wrapper.vm.errorMessage).toBe('');
    expect(wrapper.vm.isError).toBeFalse();
  });

  it('File errors have valid aria attributes', async () => {
    let wrapper = mount(component, defaults);
    let error = 'Bad file';

    // neither ariaDescribedBy nor error state
    expect(wrapper.vm.ariaDescribedbyId).toBe('');

    // the ID is aet
    wrapper.setProps({ ariaDescribedby: 'ariaid' });
    await Vue.nextTick();
    expect(
      wrapper
        .find('[aria-describedby="' + wrapper.vm.ariaDescribedbyId + '"]')
        .exists()
    ).toBeTrue();

    // both ariaid & there is an error
    wrapper.vm.handleError({ error: error });
    await Vue.nextTick();

    expect(
      wrapper
        .find('[aria-describedby="' + wrapper.vm.ariaDescribedbyId + '"]')
        .exists()
    ).toBeTrue();
    expect(wrapper.find('#' + wrapper.vm.errorId).exists()).toBeTrue();

    wrapper.setProps({ ariaDescribedby: undefined });
    expect(wrapper.vm.ariaDescribedbyId).toEqual(wrapper.vm.errorId);
    await Vue.nextTick();
    expect(
      wrapper.find('[aria-describedby="' + wrapper.vm.errorId + '"]').exists()
    ).toBeTrue();
    expect(wrapper.find('#' + wrapper.vm.errorId).exists()).toBeTrue();
  });
});
