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
 * @module mod_approval
 */

import { createLocalVue, mount } from '@vue/test-utils';
import PickerCommon from '../PickerCommon';
import { flushMicrotasks } from '../../../../../tui/src/tests/unit/util';
import tuiVuePlugin from 'tui/tui_vue_plugin';
import SearchFilter from 'tui/components/filters/SearchFilter';

const localVue = createLocalVue();
localVue.use(tuiVuePlugin); // get passthrough, render, and focus-within
delete localVue.prototype.uid; // vue-test-utils needs this

const frameworks = [
  { id: 3, name: 'Default Framework' },
  { id: 9, name: 'Other Framework' },
];

const orgs = {
  3: [
    {
      id: 1,
      name: 'Accounting',
      children: [
        { id: 2, name: 'Accounts Receivable' },
        {
          id: 3,
          name: 'Accounts Payable',
          children: [{ id: 4, name: 'Tax Accounting' }],
        },
      ],
    },
    {
      id: 5,
      name: 'Services',
    },
    {
      id: 6,
      name: 'Manufacturing',
      children: [{ id: 7, name: 'Injection Moulding' }],
    },
  ],
  9: [{ id: '8', name: 'Other Org' }],
};

const flatList = nodes =>
  nodes.concat(...nodes.map(x => (x.children ? flatList(x.children) : [])));

function getItemsBase(options) {
  const filters = options.filters || {};
  const fwOrgs = filters.frameworkId
    ? orgs[filters.frameworkId] || []
    : [].concat(...Object.values(orgs));

  if (options.ids) {
    return flatList(fwOrgs).filter(x => options.ids.includes(x.id));
  }

  const parent =
    options.parentId != null
      ? options.parentId == -1
        ? { children: fwOrgs }
        : flatList(fwOrgs).find(x => x.id)
      : { children: flatList(fwOrgs) };

  let results = parent.children;

  if (filters.search) {
    results = results.filter(x =>
      x.name.toLowerCase().includes(filters.search)
    );
  }

  return {
    items: results.map(x => ({
      id: x.id,
      name: x.name,
      children: !!x.children, // hide children info
    })),
  };
}

// work around Vue weirdness
const clone = x => JSON.parse(JSON.stringify(x));

describe('PickerCommon', () => {
  it('loads data through provided props and displays it in the UI', async () => {
    let value = null;
    const getFrameworks = jest.fn(() => frameworks);
    const getItems = jest.fn(getItemsBase);

    const wrapper = mount(PickerCommon, {
      localVue,
      propsData: {
        value,
        hierarchy: true,
        filterFrameworks: true,
        filterTitle: 'filter title',
        columns: [{ id: 'name', label: 'table header', size: 11 }],
        getFrameworks,
        getItems,
      },
    });

    await flushMicrotasks();

    expect(getFrameworks).toHaveBeenCalled();
    expect(getFrameworks).toHaveBeenCalledBefore(getItems);
    expect(getItems).toHaveBeenCalledTimes(1);
    expect(getItems).toHaveBeenLastCalledWith({
      filters: { frameworkId: 3, search: null },
      parentId: -1,
    });
    expect(clone(wrapper.vm.resultsList)).toEqual([
      {
        id: 1,
        name: 'Accounting',
        children: true,
      },
      {
        id: 5,
        name: 'Services',
        children: false,
      },
      {
        id: 6,
        name: 'Manufacturing',
        children: true,
      },
    ]);

    // Do a search
    wrapper.find(SearchFilter).vm.$emit('input', 'acc');

    expect(wrapper.vm.filters.search).toBe('acc');

    await flushMicrotasks();

    expect(getItems).toHaveBeenCalledTimes(2);
    expect(getItems).toHaveBeenLastCalledWith({
      filters: { frameworkId: 3, search: 'acc' },
      parentId: null,
    });
    expect(clone(wrapper.vm.resultsList)).toEqual([
      {
        id: 1,
        name: 'Accounting',
        children: true,
      },
      {
        id: 2,
        name: 'Accounts Receivable',
        children: false,
      },
      {
        id: 3,
        name: 'Accounts Payable',
        children: true,
      },
      {
        id: 4,
        name: 'Tax Accounting',
        children: false,
      },
    ]);

    // Drill down to accounting -- this will also reset the text search
    wrapper
      .find('[aria-label="[[navigate_down, totara_core, \\"Accounting\\"]]"]')
      .trigger('click');
    await flushMicrotasks();

    expect(getItems).toHaveBeenCalledTimes(3);
    expect(getItems).toHaveBeenLastCalledWith({
      filters: { frameworkId: 3, search: null },
      parentId: 1,
    });
    expect(clone(wrapper.vm.resultsList)).toEqual([
      {
        id: 2,
        name: 'Accounts Receivable',
        children: false,
      },
      {
        id: 3,
        name: 'Accounts Payable',
        children: true,
      },
    ]);
  });
});
