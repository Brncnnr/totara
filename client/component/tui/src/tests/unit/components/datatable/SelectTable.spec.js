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

import { mount } from '@vue/test-utils';
import component from 'tui/components/datatable/SelectTable.vue';
import Vue from 'vue';
let wrapper;

Vue.directive('focus-within', {});

const stubs = {
  passthrough: {
    functional: true,
    render(h, { scopedSlots }) {
      return scopedSlots.default && scopedSlots.default();
    },
  },
  render: {
    functional: true,
    props: ['vnode'],
    render: (h, { props }) => props.vnode,
  },
};

const propsData = {
  value: [],
  data: [
    {
      ready: true,
      the_name_of_the_name_will_vary: 'one',
    },
    {
      ready: true,
      the_name_of_the_name_will_vary: 'two',
    },
    {
      ready: false,
      the_name_of_the_name_will_vary: 'three',
    },
    {
      ready: true,
      the_name_of_the_name_will_vary: 'four',
    },
  ],
  'expandable-rows': true,
  rowLabelKey: 'the_name_of_the_name_will_vary',
};

let i = 0;

describe('SelectTable', () => {
  beforeAll(() => {
    wrapper = mount(component, {
      Vue,
      propsData,
      mocks: {
        $str: function() {
          return `lang string ${i++}`;
        },
      },
      stubs,
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.html()).toMatchSnapshot();
  });
});
