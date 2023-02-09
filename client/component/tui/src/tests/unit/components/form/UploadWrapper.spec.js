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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @module totara_core
 */

import { shallowMount } from '@vue/test-utils';

import Upload from 'tui/components/form/UploadWrapper';

describe('UploadWrapper', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(Upload, {
      mocks: {
        $str(identifier, component, param) {
          return `[${identifier}, ${component} - ${param}]`;
        },
      },

      propsData: {
        href: 'upload.php',
        itemId: 42,
        repositoryId: 8,
        contextid: 3,
        acceptedTypes: ['image/*'],
        overwrite: true,
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
