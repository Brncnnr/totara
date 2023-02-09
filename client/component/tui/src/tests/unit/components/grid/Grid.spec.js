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
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
let wrapper;
let propsData;
let scopedSlots;

global.ResizeObserver = class {
  observe() {
    return;
  }
  unobserve() {
    return;
  }
};

describe('Grid', () => {
  propsData = {
    id: 'grid',
    direction: 'vertical',
    maxUnits: '16',
    stackAt: 960,
  };

  scopedSlots = {
    default: function() {
      return this.$createElement(GridItem, {
        props: {
          units: 8,
          order: 2,
          grows: true,
          shrinks: false,
          overflows: true,
          hyphens: false,
          sizeData: {
            gutterSize: '12px',
            maxGridUnits: 16,
            numberOfSuppliedGridItems: 8,
          },
        },
      });
    },
  };

  describe('Grid with default gridTag', () => {
    beforeAll(() => {
      wrapper = shallowMount(Grid, {
        propsData,
        scopedSlots,
      });
    });

    it('direction can be set', () => {
      let propValue = wrapper.find('#grid').props().direction;
      expect(propValue).toBeTruthy();
    });

    it('maxUnits can be set', () => {
      let propValue = wrapper.find('#grid').props().maxUnits;
      expect(propValue).toBeTruthy();
    });

    it('stackAt can be set', () => {
      let propValue = wrapper.find('#grid').props().stackAt;
      expect(propValue).toBeTruthy();
    });

    it('gridClasses method returns Array of default classes', () => {
      expect(Array.isArray(wrapper.vm.gridClasses())).toBe(true);
    });

    it('gridClasses method returns Array of default and additional classes', () => {
      let defaultCount = wrapper.vm.gridClasses().length,
        additionalClasses = ['customClass', 'anotherClass'];
      expect(wrapper.vm.gridClasses(additionalClasses).length).toBe(
        defaultCount + additionalClasses.length
      );
    });

    it('Checks snapshot', () => {
      expect(wrapper.element).toMatchSnapshot();
    });
  });

  describe('Grid with a set gridTag', () => {
    beforeAll(() => {
      propsData = { ...propsData, gridTag: 'ul' };

      scopedSlots = {
        default: function() {
          return this.$createElement(GridItem, {
            props: {
              gridItemTag: 'li',
              order: 1,
            },
          });
        },
      };

      wrapper = shallowMount(Grid, {
        propsData,
        scopedSlots,
      });
    });

    it('Checks snapshot', () => {
      wrapper.setProps({ gridTag: 'ul' });
      expect(wrapper.element).toMatchSnapshot();
    });
  });
});
