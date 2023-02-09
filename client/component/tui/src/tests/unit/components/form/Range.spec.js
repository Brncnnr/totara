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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import component from 'tui/components/form/Range.vue';

let wrapper;
const selectHandler = jest.fn();

const passthroughProps = {
  id: 'a',
  autocomplete: true,
  autofocus: true,
  checked: false,
  disabled: true,
  name: 'n',
  readonly: true,
  required: true,
  value: '5',
  min: 0,
  max: 10,
  showLabels: true,
  showMinMax: true,
};

describe('Range', () => {
  beforeAll(() => {
    wrapper = mount(component, {
      propsData: Object.assign({ label: 'Some range' }, passthroughProps),
      listeners: {
        select: selectHandler,
      },
    });
    selectHandler.mockClear();
  });

  it('checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
