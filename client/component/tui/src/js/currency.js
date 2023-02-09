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

import { config } from 'tui/config';

/**
 * @typedef {Object} CurrencyDisplay
 * @property {string} currency Currency code
 * @property {string} symbol Currency symbol
 * @property {string=} side Currency side - 'start' or 'end' or empty
 * @property {number=} fractions Number of fraction digits
 */

/**
 * Calculate Math.pow(10, -n) with mitigating floating point error
 * @param {number} n
 * @returns {number}
 */
function pow10n(n) {
  return parseFloat(`1e-${n}`);
}

/**
 * Convert xx_yy to xx-yy.
 * @param {string} locale
 * @returns {string|undefined}
 */
function fixUpLocale(locale) {
  if (!locale) {
    return undefined;
  }
  return locale.replace(/^(\w\w)_(\w\w)$/, '$1-$2');
}

class Strategy {
  format() {
    return undefined;
  }
}

class NumberStrategy extends Strategy {
  /**
   * @param {string} locale
   */
  constructor(locale) {
    super();
    this.type = 'number';
    this.currency = '';
    this.symbol = '';
    // setting side to empty will result in the symbol not being rendered
    this.side = '';
    this.fractions = 0;
    // setting step to undefined will result in the step attribute not being set
    this.step = undefined;
    this.numberFormat = new Intl.NumberFormat(locale);
  }
  /**
   * @param {number} value
   * @returns {string}
   */
  format(value) {
    return this.numberFormat.format(value);
  }
}

class SimpleStrategy extends Strategy {
  /**
   * @param {string} currency
   * @param {string} locale
   */
  constructor(currency, locale) {
    super();
    let result, options;
    try {
      // get a string like '$0' or '0 €'
      result = (0).toLocaleString(locale, {
        style: 'currency',
        currency,
        // force to latin numbers so we can tell which part is the number
        numberingSystem: 'latn',
        // omit decimal part to make things easier
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      });
      // get the number of fraction digits
      // e.g. 2 for USD, 0 for JPY
      options = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
      }).resolvedOptions();
    } catch (e) {
      result = '0 ' + currency;
      options = { maximumFractionDigits: 2 };
    }
    this.type = 'simple';
    this.currency = currency;
    this.symbol = result.replace('0', '').trim();
    this.side = result[0] !== '0' ? 'start' : 'end';
    this.fractions = options.maximumFractionDigits || 0;
    this._initStepNumFormat(locale, { style: 'currency', currency });
  }
  /**
   * @param {string} locale
   * @param {object} formatOptions
   */
  _initStepNumFormat(locale, formatOptions = {}) {
    this.step = pow10n(this.fractions);
    this.numberFormat = new Intl.NumberFormat(
      locale,
      Object.assign(
        {
          // hopefully minimum is always identical to maximum for currency, so
          // we need to take care of only maximum
          minimumFractionDigits: this.fractions,
          maximumFractionDigits: this.fractions,
        },
        formatOptions
      )
    );
  }
  /**
   * @param {number} value
   * @returns {string}
   */
  format(value) {
    return this.numberFormat.format(value);
  }
}

class CustomStrategy extends SimpleStrategy {
  /**
   * @param {CurrencyDisplay} currencyDisplay
   * @param {string} locale
   */
  constructor(currencyDisplay, locale) {
    currencyDisplay = CustomStrategy._validateCurrencyDisplay(currencyDisplay);
    super(currencyDisplay.currency, locale);
    ['currency', 'symbol', 'side', 'fractions'].forEach(key => {
      if (key in currencyDisplay) {
        this[key] = currencyDisplay[key];
      }
    });
    this.type = 'custom';
    this._initStepNumFormat(locale);
  }
  /**
   * @param {number} value
   * @returns {string}
   */
  format(value) {
    const formattedValue = this.numberFormat.format(value);
    if (this.side === 'start') {
      return this.symbol + formattedValue;
    }
    if (this.side === 'end') {
      return formattedValue + this.symbol;
    }
    return formattedValue;
  }
  /**
   * @param {CurrencyDisplay} currencyDisplay
   * @returns {CurrencyDisplay}
   */
  static _validateCurrencyDisplay(currencyDisplay) {
    if (typeof currencyDisplay.currency !== 'string') {
      throw new Error('currency must be set as string');
    }
    if (typeof currencyDisplay.symbol !== 'string') {
      throw new Error('symbol must be set as string');
    }
    if (
      currencyDisplay.side != null &&
      !['start', 'end', ''].includes(currencyDisplay.side)
    ) {
      throw new Error('side must be start, end or empty string');
    }
    if (
      currencyDisplay.fractions != null &&
      !(
        typeof currencyDisplay.fractions === 'number' &&
        currencyDisplay.fractions >= 0 &&
        currencyDisplay.fractions < 20
      )
    ) {
      throw new Error('fractions must be from 0 to 20');
    }
    const result = {};
    ['currency', 'symbol', 'side', 'fractions'].forEach(key => {
      if (currencyDisplay[key] != null) {
        result[key] = currencyDisplay[key];
      }
    });
    return result;
  }
}

export class CurrencyFormat {
  /**
   * @param {?(string|CurrencyDisplay)} currency e.g. 'USD' or CurrencyDisplay object
   * @param {string=} locale e.g. 'en-US'
   */
  constructor(currency, locale) {
    locale = fixUpLocale(locale || config.locale.language);
    if (!currency) {
      this._strategy = new NumberStrategy(locale);
    } else if (typeof currency === 'object') {
      if (!currency.currency) {
        this._strategy = new NumberStrategy(locale);
      } else {
        this._strategy = new CustomStrategy(currency, locale);
      }
    } else {
      this._strategy = new SimpleStrategy(currency, locale);
    }
  }
  /**
   * @returns {string}
   */
  get type() {
    return this._strategy.type;
  }
  /**
   * @returns {string} Currency code
   */
  get currency() {
    return this._strategy.currency;
  }
  /**
   * @returns {string} Currency symbol
   */
  get symbol() {
    return this._strategy.symbol;
  }
  /**
   * @returns {string} Currency side - 'start' or 'end' or empty
   */
  get side() {
    return this._strategy.side;
  }
  /**
   * @returns {number} Number of fraction digits
   */
  get fractions() {
    return this._strategy.fractions;
  }
  /**
   * @returns {number} Input step based on the number of fraction digits
   */
  get step() {
    return this._strategy.step;
  }
  /**
   * Format a number to a currency string
   * @param {number|string} value
   * @returns {string}
   */
  format(value) {
    return this._strategy.format(Number(value));
  }
}
