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
 * @author Dave Wallace <dave.wallace@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import Responsive from 'tui/components/responsive/Responsive';
let wrapperDefault, wrapperSlotted;
global.ResizeObserver = class {
  observe() {
    return;
  }
  unobserve() {
    return;
  }
};

describe('Responsive', () => {
  beforeAll(() => {
    wrapperDefault = shallowMount(Responsive, {
      propsData: {
        id: 'responsive',
        breakpoints: [
          { name: 'small', boundaries: [0, 320] },
          { name: 'medium', boundaries: [321, 768] },
          { name: 'large', boundaries: [767, 960] },
        ],
        resizeThrottleTime: 300,
      },
    });

    wrapperSlotted = shallowMount(Responsive, {
      data() {
        return { currentBoundaryName: 'small' };
      },
      propsData: {
        id: 'responsive',
      },
      scopedSlots: {
        default: function(props) {
          return this.$createElement('div', [props.currentBoundaryName]);
        },
      },
    });
  });

  it('breakpoints can be set', () => {
    let propValue = wrapperDefault.find('#responsive').props().breakpoints;
    expect(propValue).toHaveLength(3);
  });

  it('resizeThrottleTime can be set', () => {
    let propValue = wrapperDefault.find('#responsive').props()
      .resizeThrottleTime;
    expect(propValue).toBeTruthy();
  });

  it('largestBreakpoint returns a breakpoint Object', () => {
    let computedValue = wrapperDefault.find('#responsive').vm.largestBreakpoint;
    let propValue = wrapperDefault.find('#responsive').props().breakpoints[2];
    expect(computedValue).toMatchObject(propValue);
  });

  it('slot prop passed to a scoped slot', () => {
    let text = wrapperSlotted.find('div').text();
    expect(text).toBe('small');
  });
});
