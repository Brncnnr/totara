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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @module editor_weka
 */

import { mount, shallowMount } from '@vue/test-utils';
import EditLinkModal from 'editor_weka/components/editing/EditLinkModal';

/**
 * @return {Wrapper<Vue>}
 * @param url {string}
 * @param save {function}
 */
const factory = (url, save) => {
  return mount(EditLinkModal, {
    propsData: {
      isNew: false,
      attrs: Object.assign({ type: 'link', text: 'link text' }, { url }),
      save,
      isMedia: () => {},
    },
    mocks: {},
  });
};

describe('EditLinkModal', () => {
  it('Should fix links if they are invalid', () => {
    const cases = [
      ['www.example.com', 'http://www.example.com'], // Incomplete url, try fix it.
      ['http://www.example.com', 'http://www.example.com'], //  Valid http url
      ['https://www.example.com', 'https://www.example.com'], // Valid https url.
      [
        'mailto:developer1@totaralearning.com',
        'mailto:developer1@totaralearning.com',
      ], // Valid mailto url
      [
        'mailto://developer1@totaralearning.com',
        'mailto://developer1@totaralearning.com',
      ], // Valid mailto url with slashes.
      ['/relative/on/purpose', '/relative/on/purpose'], // Looks relative on purpose, leave it.
      ['#hash-on-purpose', '#hash-on-purpose'], // Looks like a relative hash fragment link, leave it.
    ];

    const test = ([inLink, expectedOutLink]) => {
      const save = attrs => {
        expect(attrs.url).toEqual(expectedOutLink);
      };

      const wrapper = factory(inLink, save);

      wrapper.find('.tui-formBtn--prim').trigger('click'); // Click done to trigger save.
    };

    cases.forEach(test);
  });

  it('Form value returns expected information', () => {
    const text = 'Link to Example';
    const url = 'http://www.example.com';
    let wrapper = shallowMount(EditLinkModal, {
      propsData: {
        attrs: {
          open_in_new_window: true,
          url,
          text,
        },
        isMedia: () => false,
      },
    });

    let formValue = wrapper.vm.formValue;
    expect(formValue.open_in_new_window).toBeTrue();
    expect(formValue.text).toBe(text);
    expect(formValue.url).toBe(url);
  });

  it('Linking to new window works as expected', async () => {
    const text = 'Link to Example';
    const url = 'http://www.example.com';
    let attrs = {
      open_in_new_window: true,
      url,
      text,
    };

    let wrapper = mount(EditLinkModal, {
      propsData: {
        attrs,
        isMedia: () => false,
      },
      mocks: {
        $str(key, component, text) {
          return text + ' new';
        },
      },
    });

    expect(wrapper.vm.text).toBe(text);

    wrapper.vm.openInNewWindow = false;
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.text).toBe(text);

    wrapper.vm.openInNewWindow = true;
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.text).toBe(text + ' new');

    attrs.text = '';
    attrs.open_in_new_window = false;
    wrapper = mount(EditLinkModal, {
      propsData: {
        attrs,
        isMedia: () => false,
      },
      mocks: {
        $str(key, component, text) {
          return text + ' new';
        },
      },
    });

    wrapper.vm.openInNewWindow = true;
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.text).toBe(url + ' new');

    // Text is saved as null when no text is present
    attrs.text = null;
    attrs.open_in_new_window = false;
    wrapper = mount(EditLinkModal, {
      propsData: {
        attrs,
        isMedia: () => false,
      },
      mocks: {
        $str(key, component, text) {
          return text + ' new';
        },
      },
    });

    wrapper.vm.openInNewWindow = true;
    await wrapper.vm.$nextTick();
    expect(wrapper.vm.text).toBe(url + ' new');
  });
});
