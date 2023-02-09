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
 * @module tui
 */

import { langString } from './i18n';
import { isExists } from 'date-fns';
import { isIsoAfter, isIsoBefore, getValuesFromIso } from 'tui/date';

// match browser email validation regex.
const EMAIL_REGEX = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;

export const isEmpty = val =>
  val === false ||
  val === null ||
  val === undefined ||
  (typeof val === 'number' && isNaN(val)) ||
  (typeof val === 'string' && val.trim().length === 0) ||
  (typeof val === 'object' &&
    typeof val.__isEmpty === 'function' &&
    val.__isEmpty()) ||
  (Array.isArray(val) && val.length === 0);

// built-in validations
export const v = {
  required: () => ({
    allowEmpty: false,
    validate: val => !isEmpty(val),
    message: () => langString('required', 'core'),
  }),

  date: () => ({
    validate: val => {
      const date = getValuesFromIso(val.iso);
      return isExists(date.year, date.month, date.day);
    },
    message: () => langString('not_a_valid_date', 'totara_form'),
  }),

  /**
   * Check date is not greater than max limit
   *
   * @param {isoDate} limit
   * @param {isoDate} customErrorString
   */
  dateMaxLimit: (limit, customErrorString) => ({
    validate: val => {
      return isIsoBefore(val.iso, limit);
    },
    message: () => {
      if (customErrorString) {
        return customErrorString;
      }
      return langString('date_after_limit', 'totara_form', limit);
    },
  }),

  /**
   * Check date is greater than min limit
   *
   * @param {isoDate} limit
   * @param {isoDate} customErrorString
   */
  dateMinLimit: (limit, customErrorString) => ({
    validate: val => {
      return isIsoAfter(val.iso, limit);
    },
    message: () => {
      if (customErrorString) {
        return customErrorString;
      }
      return langString('date_before_limit', 'totara_form', limit);
    },
  }),

  email: () => ({
    validate: val => EMAIL_REGEX.test(val),
    message: () => langString('emailvalidationerror', 'totara_form'),
  }),

  number: () => ({
    validate: val => !isNaN(Number(val)),
    message: () => langString('validation_invalid_number', 'totara_core'),
  }),

  integer: () => ({
    validate: val => {
      // If val is a string then we can check for .0 and therefore fail the validation in those cases
      const hasDecimal =
        typeof val === 'string' ? val.toString().indexOf('.') != -1 : false;
      const num = Number(val);
      // not NaN and is an integer
      return !hasDecimal && !isNaN(num) && (num | 0) === num;
    },
    message: () => langString('validation_invalid_integer', 'totara_core'),
  }),

  minLength: len => ({
    validate: val => val.length >= len,
    message: () =>
      langString('validation_invalid_min_length', 'totara_core', { len }),
  }),

  maxLength: len => ({
    validate: val => val.length <= len,
    message: () =>
      langString('validation_invalid_max_length', 'totara_core', { len }),
  }),

  min: min => ({
    validate: val => Number(val) >= min,
    message: () => langString('validation_invalid_min', 'totara_core', { min }),
  }),

  /**
   * @param {number|null} rangeStartVal
   * @param {number|null} rangeEndVal
   * @param {number} absoluteMin
   * @param {number} absoluteMax
   * @param {string} endBeforeStartMessage
   * @return {object}
   */
  minForRangeEnd: (
    rangeStartVal,
    rangeEndVal,
    absoluteMin,
    absoluteMax,
    endBeforeStartMessage
  ) => {
    let min;

    if (typeof rangeStartVal !== 'number') {
      min = absoluteMin;
    } else if (rangeStartVal > absoluteMax) {
      min = absoluteMax;
    } else {
      min = rangeStartVal < absoluteMin ? absoluteMin : rangeStartVal;
    }

    return {
      validate: val => Number(val) >= min,
      message: () => {
        if (
          typeof rangeStartVal === 'number' &&
          typeof rangeEndVal === 'number' &&
          rangeStartVal > rangeEndVal
        ) {
          return endBeforeStartMessage;
        }

        return langString('validation_invalid_min', 'totara_core', { min });
      },
    };
  },

  max: max => ({
    validate: val => Number(val) <= max,
    message: () => langString('validation_invalid_max', 'totara_core', { max }),
  }),

  /**
   * @param {number|null} rangeStartVal
   * @param {number|null} rangeEndVal
   * @param {number} absoluteMin
   * @param {number} absoluteMax
   * @param {string} startAfterEndMessage
   * @return {object}
   */
  maxForRangeStart: (
    rangeStartVal,
    rangeEndVal,
    absoluteMin,
    absoluteMax,
    startAfterEndMessage
  ) => {
    let max;

    if (typeof rangeEndVal !== 'number') {
      max = absoluteMax;
    } else if (rangeEndVal < absoluteMin) {
      max = absoluteMin;
    } else {
      max = rangeEndVal < absoluteMax ? rangeEndVal : absoluteMax;
    }

    return {
      validate: val => Number(val) <= max,
      message: () => {
        if (
          typeof rangeStartVal === 'number' &&
          typeof rangeEndVal === 'number' &&
          rangeStartVal > rangeEndVal
        ) {
          return startAfterEndMessage;
        }

        return langString('validation_invalid_max', 'totara_core', { max });
      },
    };
  },

  colorValueHex: () => ({
    validate: val => /^#[0-9A-F]{6}$/i.test(val),
    message: () =>
      langString('validation_invalid_color_value_hex', 'totara_core'),
  }),

  // for testing async validation
  delay: (t = 1000) => ({
    validate: () => new Promise(r => setTimeout(() => r(true), t)),
    message: () => {},
  }),
};

/**
 * Make validator function from a validator spec.
 *
 * @param {*} validator
 */
function makeValidator(validator) {
  if (typeof validator == 'function') {
    return validator;
  }
  return value => {
    // if allowEmpty is true and it is empty skip validation
    if (
      (validator.allowEmpty == null || validator.allowEmpty) &&
      isEmpty(value)
    ) {
      return;
    }
    let result = false;
    try {
      result = validator.validate(value);
    } catch (e) {
      result = false;
    }
    if (result && result.then) {
      return result.then(() => {
        if (!result) {
          return validator.message();
        }
      });
    }
    if (!result) {
      return validator.message();
    }
  };
}

/**
 * Make validator function for a single field.
 *
 * @param {(function|array)} validators
 * @returns {function}
 */
export function fieldValidator(validators) {
  // ensure validators are in the correct format
  if (typeof validators == 'function') {
    validators = validators(v);
  }
  if (!Array.isArray(validators)) {
    validators = [validators];
  }

  validators = validators.map(makeValidator);

  return value => {
    let error = null;
    for (var i = 0; i < validators.length; i++) {
      error = validators[i](value);
      if (error) {
        break;
      }
    }
    return error;
  };
}
