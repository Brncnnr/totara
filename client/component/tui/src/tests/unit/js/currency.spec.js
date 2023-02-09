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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module tui
 */

import { CurrencyFormat } from 'tui/currency';
import { config } from 'tui/config';

// Node 13 and below only supports English locales
config.locale.language = 'en';
config.locale.country = 'NZ';

describe('using number formatter', () => {
  it('create with default', () => {
    const formatter = new CurrencyFormat();
    expect(formatter.type).toBe('number');
    expect(formatter.format(123456789)).toBe('123,456,789');
    expect(formatter.format(123456.789)).toBe('123,456.789');
  });

  it('create with null', () => {
    const formatter = new CurrencyFormat(null);
    expect(formatter.type).toBe('number');
  });

  it('create with empty string', () => {
    const formatter = new CurrencyFormat('');
    expect(formatter.type).toBe('number');
  });

  it('create with empty object', () => {
    const formatter = new CurrencyFormat({});
    expect(formatter.type).toBe('number');
  });

  it('create with no currency in object', () => {
    const formatter = new CurrencyFormat({ currency: '' });
    expect(formatter.type).toBe('number');
  });
});

describe('using simple formatter', () => {
  it('create with USD', () => {
    const formatter = new CurrencyFormat('USD');
    expect(formatter.type).toBe('simple');
    expect(formatter.currency).toBe('USD');
    expect(formatter.symbol).toBe('$');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(2);
    expect(formatter.step).toBe(0.01);
    expect(formatter.format(123456789)).toBe('$123,456,789.00');
    expect(formatter.format(123456.789)).toBe('$123,456.79');
  });

  it('create with JPY', () => {
    const formatter = new CurrencyFormat('JPY');
    expect(formatter.type).toBe('simple');
    expect(formatter.currency).toBe('JPY');
    expect(formatter.symbol).toBe('¥');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(0);
    expect(formatter.step).toBe(1);
    expect(formatter.format(123456789)).toBe('¥123,456,789');
    expect(formatter.format(123456.789)).toBe('¥123,457');
  });

  it.each(['en', 'en-us', 'en_us'])('Create with EUR in English', locale => {
    const formatter = new CurrencyFormat('EUR', locale);
    expect(formatter.type).toBe('simple');
    expect(formatter.currency).toBe('EUR');
    expect(formatter.symbol).toBe('€');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(2);
    expect(formatter.step).toBe(0.01);
    expect(formatter.format(123456789)).toBe('€123,456,789.00');
    expect(formatter.format(123456.789)).toBe('€123,456.79');
    const options = formatter._strategy.numberFormat.resolvedOptions();
    expect(options.locale).toStartWith('en');
  });

  it.each(['', null, undefined])('Create with USD in user locale', locale => {
    const formatter = new CurrencyFormat('USD', locale);
    expect(formatter.type).toBe('simple');
    expect(formatter.currency).toBe('USD');
  });
});

describe('using custom formatter', () => {
  it('create with custom symbol', () => {
    const formatter = new CurrencyFormat({
      currency: 'NZD',
      symbol: '🥝',
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('NZD');
    expect(formatter.symbol).toBe('🥝');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(2);
    expect(formatter.step).toBe(0.01);
    expect(formatter.format(123456789)).toBe('🥝123,456,789.00');
    expect(formatter.format(123456.789)).toBe('🥝123,456.79');
  });

  it('create with different side', () => {
    const formatter = new CurrencyFormat({
      currency: 'KRW',
      symbol: ' 대한민국 원',
      side: 'end',
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('KRW');
    expect(formatter.symbol).toBe(' 대한민국 원');
    expect(formatter.side).toBe('end');
    expect(formatter.fractions).toBe(0);
    expect(formatter.step).toBe(1);
    expect(formatter.format(123456789)).toBe('123,456,789 대한민국 원');
    expect(formatter.format(123456.789)).toBe('123,457 대한민국 원');
  });

  it('create with no side', () => {
    const formatter = new CurrencyFormat({
      currency: 'AUD',
      symbol: '🏉',
      side: '',
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('AUD');
    expect(formatter.symbol).toBe('🏉');
    expect(formatter.side).toBe('');
    expect(formatter.fractions).toBe(2);
    expect(formatter.step).toBe(0.01);
    expect(formatter.format(123456789)).toBe('123,456,789.00');
    expect(formatter.format(123456.789)).toBe('123,456.79');
  });

  it('create with different fractions', () => {
    const formatter = new CurrencyFormat({
      currency: 'USD',
      symbol: '🗽',
      fractions: 4,
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('USD');
    expect(formatter.symbol).toBe('🗽');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(4);
    expect(formatter.step).toBe(0.0001);
    expect(formatter.format(123456789)).toBe('🗽123,456,789.0000');
    expect(formatter.format(123456.789)).toBe('🗽123,456.7890');
  });

  it('create with all options', () => {
    const formatter = new CurrencyFormat({
      currency: 'USD',
      symbol: ' US$',
      side: 'end',
      fractions: 1,
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('USD');
    expect(formatter.symbol).toBe(' US$');
    expect(formatter.side).toBe('end');
    expect(formatter.fractions).toBe(1);
    expect(formatter.step).toBe(0.1);
    expect(formatter.format(123456789)).toBe('123,456,789.0 US$');
    expect(formatter.format(123456.789)).toBe('123,456.8 US$');
  });

  it('create with empty symbol', () => {
    const formatter = new CurrencyFormat({
      currency: 'USD',
      symbol: '',
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('USD');
    expect(formatter.symbol).toBe('');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(2);
    expect(formatter.step).toBe(0.01);
    expect(formatter.format(123456789)).toBe('123,456,789.00');
    expect(formatter.format(123456.789)).toBe('123,456.79');
  });

  it.each([null, undefined])('create with null side', side => {
    const formatter = new CurrencyFormat({
      currency: 'GBP',
      symbol: '££',
      side,
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('GBP');
    expect(formatter.symbol).toBe('££');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(2);
    expect(formatter.step).toBe(0.01);
    expect(formatter.format(123456789)).toBe('££123,456,789.00');
    expect(formatter.format(123456.789)).toBe('££123,456.79');
  });

  it.each([null, undefined])('create with null fractions', fractions => {
    const formatter = new CurrencyFormat({
      currency: 'VND',
      symbol: '\u20AB',
      fractions,
    });
    expect(formatter.type).toBe('custom');
    expect(formatter.currency).toBe('VND');
    expect(formatter.symbol).toBe('\u20AB');
    expect(formatter.side).toBe('start');
    expect(formatter.fractions).toBe(0);
    expect(formatter.step).toBe(1);
    expect(formatter.format(123456789)).toBe('\u20AB123,456,789');
    expect(formatter.format(123456.789)).toBe('\u20AB123,457');
  });

  it('create with invalid currency', () => {
    expect(
      () =>
        new CurrencyFormat({
          currency: { currency: 'USD' },
        })
    ).toThrow('currency must be set as string');
  });

  it('create with no symbol', () => {
    expect(
      () =>
        new CurrencyFormat({
          currency: 'USD',
        })
    ).toThrow('symbol must be set as string');
  });

  it('create with invalid symbol', () => {
    expect(
      () =>
        new CurrencyFormat({
          currency: 'USD',
          symbol: 42,
        })
    ).toThrow('symbol must be set as string');
  });

  it.each(['Start', 'up', false, 0])('create with invalid side', side => {
    expect(
      () =>
        new CurrencyFormat({
          currency: 'USD',
          symbol: '?',
          side,
        })
    ).toThrow('side must be start, end or empty string');
  });

  it.each(['ten', '', false, -1, 21])(
    'create with invalid fractions',
    fractions => {
      expect(
        () =>
          new CurrencyFormat({
            currency: 'USD',
            symbol: '?',
            fractions,
          })
      ).toThrow('fractions must be from 0 to 20');
    }
  );
});
