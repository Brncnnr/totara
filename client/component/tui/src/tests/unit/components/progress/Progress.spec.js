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

import { mount } from '@vue/test-utils';
import Progress from 'tui/components/progress/Progress';

const factory = propsData => {
  return mount(Progress, {
    propsData: {
      ...propsData,
    },
    mocks: {
      $str: x => x,
    },
  });
};

describe('Progress', () => {
  it('render correctly', () => {
    const wrapper = factory({
      small: false,
      value: 500,
      min: 100,
      max: 1000,
      format: 'percent',
      completedText: true,
      hideBackground: true,
      showEmptyState: true,
    });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('render completed context correctly', () => {
    const wrapper = factory({ value: 100, completedText: true });
    expect(wrapper.find('span').text()).toBe('completed');
  });
});
