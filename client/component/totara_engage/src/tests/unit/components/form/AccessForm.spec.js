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

import AccessForm from 'totara_engage/components/form/AccessForm';
import { shallowMount } from '@vue/test-utils';
import { AccessManager } from 'totara_engage/index';

describe('Engage AccessForm', function() {
  let refetch;
  let mocks = {};

  beforeEach(() => {
    refetch = jest.fn();
    mocks = {
      $str(id, component) {
        return `${id}, ${component}`;
      },
      $apollo: {
        queries: {
          sharedTo: {
            refetch,
          },
        },
      },
      sharedTo: ['Alvin Smith', 'Steve John', 'Oleg Lang'],
    };
  });

  it('Checks snapshot for public access', () => {
    let wrapper = shallowMount(AccessForm, {
      mocks,
      propsData: {
        itemId: 1,
        component: 'test_component',
        selectedAccess: AccessManager.PUBLIC,
        articleName: 'public access',
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot for private access', () => {
    let wrapper = shallowMount(AccessForm, {
      mocks,
      propsData: {
        itemId: 1,
        component: 'test_component',
        selectedAccess: AccessManager.PRIVATE,
        articleName: 'private access',
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot for restricted access', () => {
    let wrapper = shallowMount(AccessForm, {
      mocks,
      propsData: {
        itemId: 1,
        component: 'test_component',
        selectedAccess: AccessManager.RESTRICTED,
        articleName: 'restricted access',
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });
});
