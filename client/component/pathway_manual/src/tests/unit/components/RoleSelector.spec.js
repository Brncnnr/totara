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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @module pathway_manual
 */

import { shallowMount } from '@vue/test-utils';
import component from 'pathway_manual/components/RoleSelector';
import { mocks } from './mocks';

const props = {
  userId: 2,
};

describe('RoleSelector', () => {
  it('Checks snapshot - has multiple roles', () => {
    let wrapper = shallowMount(component, {
      mocks: Object.assign(mocks, {
        roles: [
          {
            name: 'manager',
            display_name: 'Manager',
          },
          {
            name: 'appraiser',
            display_name: 'Appraiser',
          },
        ],
      }),
      propsData: props,
    });
    expect(wrapper.element).toMatchSnapshot('multipleRoles');
  });
  it('Checks snapshot - has single role', () => {
    let wrapper = shallowMount(component, {
      mocks: Object.assign(mocks, {
        roles: [
          {
            name: 'manager',
            display_name: 'Manager',
          },
        ],
      }),
      propsData: props,
    });
    expect(wrapper.element).toMatchSnapshot('singleRole');
  });
  it('Checks snapshot - has specified role', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        specifiedRole: 'appraiser',
      }),
    });
    expect(wrapper.element).toMatchSnapshot('specifiedRole');
  });
});
