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
import component from 'tui/components/datatable/Table.vue';
let wrapper;

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
  data: [
    {
      ready: true,
      title: 'aaa',
    },
    {
      ready: true,
      title: 'bbb',
    },
    {
      ready: false,
      title: 'ccc',
    },
    {
      ready: true,
      title: 'ddd',
    },
  ],
  'expandable-rows': true,
};

describe('Table', () => {
  it('Checks snapshot', () => {
    wrapper = shallowMount(component, {
      propsData,
      mocks: {
        $str() {
          return 'No items to display';
        },
      },
      stubs,
    });

    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot with no row data supplied', () => {
    wrapper = shallowMount(component, {
      propsData: { 'expandable-rows': true, data: [] },
      mocks: {
        $str() {
          return 'No items to display';
        },
      },
      stubs,
    });

    expect(wrapper.element).toMatchSnapshot();
  });
});
