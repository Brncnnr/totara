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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @module tui
 */

import { getDateOrderFromStrftime, getFixedYearsSelectArray } from 'tui/date';

describe('getDateOrderFromStrftime', () => {
  it('Handles english', () => {
    const order = getDateOrderFromStrftime('%d/%m/%Y');
    expect(order).toEqual(['d', 'm', 'y']);
  });

  it('Handles obscure formats', () => {
    const order = getDateOrderFromStrftime('%A  -  %B   -  %Y');
    expect(order).toEqual(['d', 'm', 'y']);
  });

  it('Handles the default output as the input format', () => {
    const order = getDateOrderFromStrftime('%y%m%d');
    expect(order).toEqual(['y', 'm', 'd']);
  });

  it('Handles multiple entries for the same date part', () => {
    let order = getDateOrderFromStrftime('%d%d/%m%m%m%m/%y%y%y');
    expect(order).toEqual(['d', 'm', 'y']);

    order = getDateOrderFromStrftime('%d/%m/%y %y/%d/');
    expect(order).toEqual(['d', 'm', 'y']);
  });

  it('Uses the default for spanish (missing year)', () => {
    const order = getDateOrderFromStrftime('%d/%m/%A');
    expect(order).toEqual(['y', 'm', 'd']); // Default value.
  });

  it('Uses the default for complete rubbish (missing all parts)', () => {
    const order = getDateOrderFromStrftime('rubbish');
    expect(order).toEqual(['y', 'm', 'd']); // Default value.
  });
});

describe('getFixedYearsSelectArray', () => {
  it('It supports explicit start and end years', () => {
    const yearOptions = getFixedYearsSelectArray(2020, 2022);
    expect(yearOptions).toEqual([
      { id: 2020, label: 2020 },
      { id: 2021, label: 2021 },
      { id: 2022, label: 2022 },
    ]);
  });

  it('It supports single years', () => {
    const yearOptions = getFixedYearsSelectArray(2020, 2020);
    expect(yearOptions).toEqual([{ id: 2020, label: 2020 }]);
  });

  it('It supports zero years', () => {
    const yearOptions = getFixedYearsSelectArray(0, 0);
    expect(yearOptions).toEqual([{ id: 0, label: 0 }]);
  });

  it('It supports negative ranges by returning an empty array', () => {
    const yearOptions = getFixedYearsSelectArray(2022, 2020);
    expect(yearOptions).toEqual([]);
  });

  it('It supports implicit start years', () => {
    const currentYear = new Date().getFullYear();

    const yearOptions = getFixedYearsSelectArray(undefined, currentYear);
    expect(yearOptions.length).toEqual(51);
    expect(yearOptions[0].id).toEqual(currentYear - 50);
    expect(yearOptions[50].id).toEqual(currentYear);
  });

  it('It supports implicit end years', () => {
    const currentYear = new Date().getFullYear();

    const yearOptions = getFixedYearsSelectArray(currentYear);
    expect(yearOptions.length).toEqual(51);
    expect(yearOptions[0].id).toEqual(currentYear);
    expect(yearOptions[50].id).toEqual(currentYear + 50);
  });

  it('It supports implicit start end years', () => {
    const currentYear = new Date().getFullYear();

    const yearOptions = getFixedYearsSelectArray();
    expect(yearOptions.length).toEqual(101);
    expect(yearOptions[0].id).toEqual(currentYear - 50);
    expect(yearOptions[100].id).toEqual(currentYear + 50);
  });
});
