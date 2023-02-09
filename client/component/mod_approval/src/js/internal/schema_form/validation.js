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

import { isEmpty } from 'tui/validation';
import { langString } from 'tui/i18n';
import { getValidatorDef } from './defs';
import { EditorContent } from 'tui/editor';

/**
 * @typedef {Object} SchemaFormValidationContext
 * @property {(path: string|array) => *} getValue
 */

/**
 * @param {(object|function)[]} validationSpecs
 * @returns {(value: any, context: SchemaFormValidationContext) => any}
 */
export function makeFieldValidator(validationSpecs) {
  const validators = validationSpecs.reduce((acc, x) => {
    if (typeof x === 'function') {
      acc.push({ fn: x });
    } else {
      const validatorDef = getValidatorDef(x.name);
      if (validatorDef) {
        acc.push({
          fn: makeValidator(validatorDef),
          spec: x,
        });
      }
    }
    return acc;
  }, []);

  return (value, context) => {
    let error = null;
    for (var i = 0; i < validators.length; i++) {
      const validator = validators[i];
      const opts = processValidatorOpts(validator.spec, context.getValue);
      error = validator.fn(value, opts);
      if (error) {
        break;
      }
    }
    return error;
  };
}

/**
 * Make validator function from a validator spec.
 *
 * @param {import('./defs').ValidatorDef} validator
 * @returns {(val: any, opts: object) => string}
 */
function makeValidator(validator) {
  return (value, opts) => {
    // if allowEmpty is true and it is empty skip validation
    const allowEmpty = validator.allowEmpty == null || validator.allowEmpty;
    if (allowEmpty && isEmpty(value)) {
      return;
    }
    let result = false;
    try {
      result = validator.validate(value, opts);
    } catch (e) {
      result = false;
    }
    if (!result) {
      return validator.message(value, opts);
    }
  };
}

/**
 * Resolve references in validator options, and remove unneccesary fields.
 *
 * @param {object} opts
 * @param {(string) => any} getValue
 */
function processValidatorOpts(opts, getValue) {
  opts = Object.assign({}, opts);
  delete opts.name;
  if (opts.value && typeof opts.value === 'object' && opts.value.ref) {
    opts.value = getValue(opts.value.ref);
  }
  return opts;
}

export function requiredValidator(value) {
  let empty = isEmpty(value);

  if (value instanceof EditorContent && value.isEmpty) {
    empty = true;
  }

  if (empty) {
    return langString('required', 'core');
  }
}
