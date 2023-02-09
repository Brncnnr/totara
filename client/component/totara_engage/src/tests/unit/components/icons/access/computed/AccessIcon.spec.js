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

import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import { shallowMount } from '@vue/test-utils';
import { AccessManager } from 'totara_engage/index';

describe('Engage AccessIcon', function() {
  it('Checks snapshot for access public', function() {
    let wrapper = shallowMount(AccessIcon, {
      propsData: {
        access: AccessManager.PUBLIC,
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot for access restricted', function() {
    let wrapper = shallowMount(AccessIcon, {
      propsData: {
        access: AccessManager.RESTRICTED,
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot for access private', function() {
    let wrapper = shallowMount(AccessIcon, {
      propsData: {
        access: AccessManager.PRIVATE,
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });
});
