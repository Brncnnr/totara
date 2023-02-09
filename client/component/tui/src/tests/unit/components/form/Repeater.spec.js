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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import Repeater from 'tui/components/form/Repeater';

describe('Repeater', () => {
  let wrapper;

  beforeAll(() => {
    wrapper = shallowMount(Repeater, {
      mocks: {
        $str: function() {
          return 'Add';
        },
        uid: 'uid-9',
      },
      propsData: {
        ariaLabel: 'Repeater btn',
        rows: [
          {
            value: 'first value',
            disabled: false,
            label: 'first label',
          },
          {
            value: '',
            disabled: false,
            label: 'second label',
          },
          {
            value: 'third value',
            disabled: false,
            label: 'third label',
          },
        ],
        minRows: 2,
        disabled: false,
        deleteIcon: true,
      },
    });
  });

  it('matches snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
