/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import { axe } from 'jest-axe';
import Paging from 'tui/components/paging/Paging';

describe('Paging', () => {
  /** @type {Paging} */
  let component = {};
  let mocks = {
    $str: function(str, compoent) {
      return '[' + str + ',', compoent + ']';
    },
    $id: function(id) {
      return 'id-' + id;
    },
  };

  beforeEach(() => {
    component = shallowMount(Paging, {
      propsData: {
        page: 5,
        totalItems: 1000,
      },
      mocks,
    });
  });

  it('matches snapshot', async () => {
    expect(component.element).toMatchSnapshot();
  });

  it('should not have any accessibility violations', async () => {
    const results = await axe(component.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });

  it('Total pages behaves as expected', () => {
    component.setProps({ itemsPerPage: 20 });
    expect(component.vm.totalPages).toBe(50);

    // just below a page boundary
    component.setProps({ totalItems: 99 });
    expect(component.vm.totalPages).toBe(5);

    // just above a page boundary
    component.setProps({ totalItems: 102 });
    expect(component.vm.totalPages).toBe(6);

    // Change the items per page
    component.setProps({ itemsPerPage: 10 });
    expect(component.vm.totalPages).toBe(11);
  });

  it('Change page method works as expected', () => {
    component.vm.changePage(5);
    expect(component.emitted()['page-change'][0][0]).toBe(5);

    // Too low - Set to lower bound
    component.vm.changePage(-3);
    expect(component.emitted()['page-change'][1][0]).toBe(1);

    // Too high - set to upper bound
    component.vm.changePage(500);
    expect(component.emitted()['page-change'][2][0]).toBe(100);

    // use text box to change page
    component.vm.newPage = 5;
    component.vm.changePage();
    expect(component.emitted()['page-change'][3][0]).toBe(5);
  });

  it('Changing items per page works as expected', () => {
    component.vm.changeItemsPerPage(10);
    expect(component.emitted()['count-change'][0][0]).toBe(10);

    component.vm.changeItemsPerPage(100);
    expect(component.emitted()['count-change'][1][0]).toBe(100);
  });

  it('Check all pages when they should be', () => {
    let wrapper = shallowMount(Paging, {
      propsData: {
        page: 1,
        totalItems: 5,
      },
    });

    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([1]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1]);

    wrapper.setProps({ totalItems: 45 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);

    wrapper.setProps({ totalItems: 65 });
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7]);

    wrapper.setProps({ totalItems: 85 });
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9]);
    wrapper.setProps({ page: 8 });
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9]);
  });

  it('Large amounts display correctly', () => {
    let wrapper = shallowMount(Paging, {
      propsData: {
        page: 1,
        itemsPerPage: 10,
        totalItems: 505,
      },
    });

    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([1, 2, 3]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7]);

    wrapper.setProps({ page: 2 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([1, 2, 3]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7]);

    wrapper.setProps({ page: 3 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([3]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7]);

    wrapper.setProps({ page: 4 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([4]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([1, 2, 3, 4, 5, 6, 7]);

    wrapper.setProps({ page: 20 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([20]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([19, 20, 21]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([18, 19, 20, 21, 22]);

    wrapper.setProps({ page: 49 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([49]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([47, 48, 49, 50, 51]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([45, 46, 47, 48, 49, 50, 51]);

    wrapper.setProps({ page: 51 });
    wrapper.vm.displayCount = 1;
    expect(wrapper.vm.display).toEqual([49, 50, 51]);
    wrapper.vm.displayCount = 3;
    expect(wrapper.vm.display).toEqual([47, 48, 49, 50, 51]);
    wrapper.vm.displayCount = 5;
    expect(wrapper.vm.display).toEqual([45, 46, 47, 48, 49, 50, 51]);
  });
});
