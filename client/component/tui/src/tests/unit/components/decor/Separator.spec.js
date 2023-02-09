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
import component from 'tui/components/decor/Separator';
let wrapperDefault, wrapperSlottedContent;

describe('Separator', () => {
  //
  beforeAll(() => {
    wrapperDefault = shallowMount(component, {
      propsData: {
        id: 'separator',
        thick: true,
        spread: true,
      },
    });

    wrapperSlottedContent = shallowMount(component, {
      slots: {
        default: '<span>ok</span>',
      },
    });
  });

  // test default <hr /> output
  it('thick can be set', () => {
    let propValue = wrapperDefault.find('#separator').props().thick;
    expect(propValue).toBeTruthy();
  });

  it('spread can be set', () => {
    let propValue = wrapperDefault.find('#separator').props().spread;
    expect(propValue).toBeTruthy();
  });

  it('Checks v-else output exists', () => {
    expect(wrapperDefault.findAll('span').length).toBe(0);
  });

  it('Checks v-else snapshot', () => {
    expect(wrapperDefault.element).toMatchSnapshot();
  });

  // test slot-provided output
  it('Checks v-if output exists', () => {
    let div = wrapperSlottedContent.find('div');
    expect(div.contains('span')).toBe(true);
  });

  it('Checks v-if snapshot', () => {
    expect(wrapperSlottedContent.element).toMatchSnapshot();
  });
});
