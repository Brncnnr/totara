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
import component from 'tui/components/grid/GridItem';
let wrapper;
let props;

describe('presentation/grid/GridItem.vue', () => {
  props = {
    units: 2,
    order: 1,
    grows: true,
    shrinks: false,
    overflows: true,
    hyphens: false,
    sizeData: {
      gutterSize: '12px',
      maxGridUnits: '16',
      numberOfSuppliedGridItems: 8,
    },
  };

  describe('with default gridItemTag', () => {
    beforeAll(() => {
      wrapper = shallowMount(component, {
        propsData: props,
      });
    });

    it('Props can be set', () => {
      expect(wrapper.props()).toMatchObject(props);
    });

    it('Checks snapshot', () => {
      expect(wrapper.element).toMatchSnapshot();
    });
  });

  describe('with a set gridItemTag', () => {
    it('Checks snapshot', () => {
      props = {
        gridItemTag: 'li',
        order: 1,
      };

      wrapper = shallowMount(component, {
        propsData: props,
      });
      expect(wrapper.element).toMatchSnapshot();
    });
  });
});
