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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */

import fields from '../fields';
import { config } from 'tui/config';

// Node 13 and below only supports English locales
config.locale.language = 'en';
config.locale.country = 'NZ';

describe('select_one', () => {
  const def = fields['select_one'];
  const exampleField = {
    attrs: {
      choices: [
        { key: 'a', label: '1' },
        { key: 'b', label: '2' },
      ],
    },
  };
  it('formats displayed text', () => {
    expect(def.displayText('b', exampleField, { values: {} })).toBe('2');
    expect(def.displayText(null, exampleField, { values: {} })).toBe('');
  });
});

describe('currency', () => {
  const def = fields['currency'];

  it('formats display text as USD', () => {
    const exampleField = {
      attrs: { currency: 'USD' },
    };
    expect(def.displayText(123456789, exampleField)).toBe('$123,456,789.00');
    expect(def.displayText(1234567.89, exampleField)).toBe('$1,234,567.89');
  });

  it('formats display text as JPY', () => {
    const exampleField = {
      attrs: { currency: 'JPY' },
    };
    expect(def.displayText(123456789, exampleField)).toBe('¥123,456,789');
    expect(def.displayText(1234567.89, exampleField)).toBe('¥1,234,568');
  });

  it('formats display text as EUR', () => {
    const exampleField = {
      attrs: { currency: { currency: 'EUR', symbol: ' €', side: 'end' } },
    };
    expect(def.displayText(123456789, exampleField)).toBe('123,456,789.00 €');
    expect(def.displayText(1234567.89, exampleField)).toBe('1,234,567.89 €');
  });
});

describe('currency_total', () => {
  const def = fields['currency_total'];
  const exampleContextInt = {
    values: {
      kia: 24691357,
      ora: 98765432,
    },
  };
  const exampleContextFloat = {
    values: {
      kia: 246913.57,
      ora: 987654.32,
    },
  };

  it('formats display text as USD', () => {
    const exampleField = {
      attrs: {
        currency: 'USD',
        sources: ['kia', 'ora'],
      },
    };
    expect(def.displayText(42, exampleField, exampleContextInt)).toBe(
      '$123,456,789.00'
    );
    expect(def.displayText(42, exampleField, exampleContextFloat)).toBe(
      '$1,234,567.89'
    );
  });

  it('formats display text as JPY', () => {
    const exampleField = {
      attrs: {
        currency: 'JPY',
        sources: ['kia', 'ora'],
      },
    };
    expect(def.displayText(42, exampleField, exampleContextInt)).toBe(
      '¥123,456,789'
    );
    expect(def.displayText(42, exampleField, exampleContextFloat)).toBe(
      '¥1,234,568'
    );
  });

  it('formats display text as EUR', () => {
    const exampleField = {
      attrs: {
        currency: { currency: 'EUR', symbol: ' €', side: 'end' },
        sources: ['kia', 'ora'],
      },
    };
    expect(def.displayText(42, exampleField, exampleContextInt)).toBe(
      '123,456,789.00 €'
    );
    expect(def.displayText(42, exampleField, exampleContextFloat)).toBe(
      '1,234,567.89 €'
    );
  });
});
