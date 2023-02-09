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

import Filter from 'totara_engage/components/contribution/Filter';
import { shallowMount } from '@vue/test-utils';

describe('Engage contribution Filter', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(Filter, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },

        $apollo: {
          loading: false,
        },
      },

      propsData: {
        component: 'component',
        area: 'area',
        value: {
          type: '',
          access: '',
          topic: '',
          sort: '',
        },
      },

      data() {
        return {
          filter: {
            access: {
              label: 'Access',
              options: [
                {
                  id: 15,
                  label: 'Hello world',
                },
              ],
            },

            type: {
              label: 'Type',
              options: [
                {
                  id: 42,
                  label: 'Answer',
                },
              ],
            },

            topic: {
              label: 'Topic',
              options: [
                {
                  id: 1,
                  label: 'Topic1',
                },
              ],
            },

            sort: {
              label: 'Sort',
              options: [
                {
                  id: 55,
                  label: 'Sort55',
                },
              ],
            },
          },
        };
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });
});
