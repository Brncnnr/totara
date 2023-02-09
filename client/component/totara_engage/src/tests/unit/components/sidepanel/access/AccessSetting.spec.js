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

import AccessSetting from 'totara_engage/components/sidepanel/access/AccessSetting';
import { shallowMount } from '@vue/test-utils';
import { AccessManager } from 'totara_engage/index';

describe('Engage AccessSetting', () => {
  let wrapper;

  beforeAll(() => {
    wrapper = shallowMount(AccessSetting, {
      propsData: {
        itemId: 1,
        component: 'test_component',
        accessValue: AccessManager.PUBLIC,
        topics: [],
        shares: [],
        timeView: null,
      },

      stubs: {
        ModalPresenter: { render: () => null },
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
