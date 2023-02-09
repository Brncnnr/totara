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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @module totara_engage
 */

import ResourceNavigationBar from 'totara_engage/components/header/ResourceNavigationBar';
import { shallowMount } from '@vue/test-utils';

describe('Engage ResourceNavigationBar', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(ResourceNavigationBar, {
      propsData: {
        backButton: {
          url: 'http://example.com/back-button',
          label: 'Back Button Label',
        },
        navigationButtons: {
          previous: 'http://example.com/previous',
          next: 'http://example.com/next',
        },
      },
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },

        $id(random) {
          return `some-${random}`;
        },
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper).toMatchSnapshot();
  });
});
