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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @module tui
 */

import { mount } from '@vue/test-utils';
import DateSelector from 'tui/components/form/DateSelector';
import * as i18n from 'tui/i18n';
import { axe } from 'jest-axe';

let wrapper;

const props = {
  initialCustomDate: '2004-08-23',
  hasTimezone: false,
  type: 'date',
  yearsMidrange: 2020,
};

function createWrapper() {
  return mount(DateSelector, {
    propsData: props,
    methods: {
      getMonths() {
        this.optionsMonths = ['a', 'b', 'c'];
      },

      getTimeZones() {
        this.optionsTimezones = ['1', '2', '3'];
      },
    },
  });
}

describe('DateSelector', () => {
  it('switches order with locale', () => {
    i18n.__setString('strftimedatefulllong', 'langconfig', '%m/%d/%Y');
    wrapper = createWrapper();
    const items = wrapper.findAll('.tui-dateSelector__date > div');
    expect(items.length).toBe(3);
    expect(items.at(0).classes()).toContain('tui-dateSelector__date-month');
    expect(items.at(1).classes()).toContain('tui-dateSelector__date-day');
    expect(items.at(2).classes()).toContain('tui-dateSelector__date-year');
  });

  it('defaults to y-m-d if no locale setting', () => {
    i18n.__setString('strftimedatefulllong', 'langconfig', null);
    wrapper = createWrapper();
    const items = wrapper.findAll('.tui-dateSelector__date > div');
    expect(items.length).toBe(3);
    expect(items.at(0).classes()).toContain('tui-dateSelector__date-year');
    expect(items.at(1).classes()).toContain('tui-dateSelector__date-month');
    expect(items.at(2).classes()).toContain('tui-dateSelector__date-day');
  });

  it('matches snapshot', async () => {
    i18n.__setString('strftimedatefulllong', 'langconfig', '%d/%m/%Y');
    wrapper = createWrapper();
    await wrapper.vm.$nextTick();
    expect(wrapper.element).toMatchSnapshot();
  });

  it('should not have any accessibility violations', async () => {
    wrapper = createWrapper();
    const results = await axe(wrapper.element, {
      rules: {
        region: { enabled: false },
      },
    });
    expect(results).toHaveNoViolations();
  });
});
