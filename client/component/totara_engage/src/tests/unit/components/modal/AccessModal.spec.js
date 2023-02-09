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
 * @module totara_engage
 */

import AccessModal from 'totara_engage/components/modal/AccessModal';
import { shallowMount } from '@vue/test-utils';

describe('Engage AccessModal', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(AccessModal, {
      propsData: {
        itemId: 1,
        component: 'test_component',
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
