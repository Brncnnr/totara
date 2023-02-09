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
import FileCard from 'tui/components/file/FileCard';

describe('FilterSidePanel', () => {
  let wrapper;

  beforeAll(() => {
    wrapper = shallowMount(FileCard, {
      propsData: {
        fileSize: 124,
        filename: 'something.png',
      },
      mocks: {
        $str: function(_key, _component, extension) {
          return extension;
        },
      },
    });
  });

  it('fileExtension is working correctly', () => {
    expect(wrapper.vm.fileExtension).toBe('png');

    wrapper.setProps({ filename: 'something' });
    expect(wrapper.vm.fileExtension).toBeNull();

    wrapper.setProps({ filename: '' });
    expect(wrapper.vm.fileExtension).toBeNull();

    wrapper.setProps({ filename: '.png' });
    expect(wrapper.vm.fileExtension).toBe('png');

    wrapper.setProps({ filename: 'something.spec.js' });
    expect(wrapper.vm.fileExtension).toBe('js');
  });
});
