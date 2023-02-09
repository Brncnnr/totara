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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import PopoverPositioner from 'tui/components/popover/PopoverPositioner';
import pending from 'tui/pending';

jest.mock('tui/lib/popover', () => {
  const { Point } = require('tui/geometry');
  return {
    position() {
      return {
        side: 'bottom',
        location: new Point(0, 0),
        arrowDistance: 90,
      };
    },
  };
});

describe('PopoverPositioner', () => {
  it.each([['transitionEnter'], ['transitionLeave']])(
    'calls pending during %s',
    method => {
      const wrapper = shallowMount(PopoverPositioner);

      expect(pending.__outstanding()).toBe(0);

      wrapper.vm[method]();

      expect(pending.__outstanding()).toBe(1);

      // check that pending goes away when the tab is hidden
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        value: 'hidden',
      });
      window.dispatchEvent(new Event('visibilitychange'));

      expect(pending.__outstanding()).toBe(0);

      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        value: 'visible',
      });
      window.dispatchEvent(new Event('visibilitychange'));

      expect(pending.__outstanding()).toBe(1);

      // check that it returns to 0 once the transition finishes
      wrapper.vm[method + 'End']();

      expect(pending.__outstanding()).toBe(0);
    }
  );

  it('matches snapshot', () => {
    const wrapper = shallowMount(PopoverPositioner, {
      scopedSlots: {
        trigger() {
          return this.$createElement('button');
        },
        default() {
          return this.$createElement('div', {}, ['hello']);
        },
      },
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
