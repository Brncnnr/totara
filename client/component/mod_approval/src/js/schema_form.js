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

import { unique } from 'tui/util';
import { ensureEntriesLoaded, getFieldDef } from './internal/schema_form/defs';
import { getAllFields, isVisible } from './internal/schema_form/schema';

export { uniformFieldWrapper } from './internal/schema_form/wrappers';

/**
 * Ensure info required to work with Schema has been loaded.
 *
 * This function must be called before using any functions that take the schema.
 *
 * @param {object} schema
 * @returns {Promise}
 */
export async function loadSchemaData(schema) {
  const fields = getAllFields(schema);

  const fieldTypes = unique(fields.map(x => x.type));
  const validatorTypes = unique(
    fields.reduce((acc, field) => {
      if (Array.isArray(field.validations)) {
        acc = acc.concat(field.validations.map(x => x.name));
      }
      return acc;
    }, [])
  );

  await ensureEntriesLoaded(fieldTypes.concat(validatorTypes));
}

/**
 * Switch from server representation to viewing representation.
 *
 * @param {object} schema
 * @param {object} values
 * @returns {object} values
 */
export function prepareValuesForView(schema, values) {
  return processFieldValues(schema, values, (field, spec, value) => {
    if (spec.prepareForView) {
      return spec.prepareForView(value, field);
    }
    return value;
  });
}

/**
 * Switch from server representation to editing representation.
 *
 * Note: this is *not* the inverse of prepareValuesForSave.
 *
 * @param {object} schema
 * @param {object} values
 * @returns {object} values
 */
export function prepareValuesForEdit(schema, values) {
  return processFieldValues(schema, values, (field, spec, value) => {
    if (spec.prepareForEdit) {
      return spec.prepareForEdit(value, field);
    }
    return value;
  });
}

/**
 * Convert editing representation of fields to input expected by the server.
 *
 * Note: this is *not* the inverse of prepareValuesForEdit.
 *
 * @param {object} schema
 * @param {object} values
 * @returns {object}
 */
export function prepareValuesForSave(schema, values) {
  values = processFieldValues(schema, values, (field, spec, value) => {
    if (spec.prepareForSave) {
      return spec.prepareForSave(value, field);
    }
    return value;
  });
  const fields = getAllFields(schema);
  const filteredValues = {};
  fields.forEach(field => {
    if (isVisible(field, key => values[key])) {
      filteredValues[field.key] = values[field.key];
    }
  });
  return filteredValues;
}

function processFieldValues(schema, values, callback) {
  values = Object.assign({}, values);
  const fields = getAllFields(schema);
  fields.forEach(field => {
    const spec = getFieldDef(field.type);
    const value = callback(field, spec, values[field.key]);
    if (value !== undefined) {
      values[field.key] = value;
    }
  });
  return values;
}
