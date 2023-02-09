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

import { comparisonResult } from './common';

/**
 * Get a flat list of all fields in schema
 *
 * @param {object} schema Form schema
 * @returns {object[]}
 */
export function getAllFields(schema) {
  if (!schema) {
    return [];
  }

  const fields = [];
  if (Array.isArray(schema.fields)) {
    fields.push(...schema.fields);
  }

  if (schema.sections) {
    schema.sections.forEach(section => {
      if (Array.isArray(section.fields)) {
        fields.push(...section.fields);
      }
    });
  }

  return fields;
}

/**
 * Get the result of a conditional "test" (key/condition/value).
 *
 * @param {object} test
 * @param {function} getValue
 * @returns {boolean}
 */
export function evalTest(test, getValue) {
  const value = getValue(test.key);
  return comparisonResult(value, test.condition, test.value);
}

/**
 * Work out if field should be visible.
 *
 * @param {object} field
 * @param {function} getValue
 * @returns {boolean}
 */
export function isVisible(field, getValue) {
  if (field.hidden) {
    return false;
  }
  if (field.conditional && !evalTest(field.conditional, getValue)) {
    return false;
  }
  return true;
}

/*
 * Get a flat list of all layout cells in the schema's print layout
 *
 * @param {object} schema Form schema
 * @returns {object[]}
 */
export function getAllLayoutCells(schema) {
  return schema && schema.print_layout && schema.print_layout.sections
    ? [].concat(
        ...schema.print_layout.sections.map(getLayoutCellsFromRowContainer)
      )
    : [];
}

function getLayoutCellsFromRowContainer(container) {
  return container && Array.isArray(container.rows)
    ? [].concat(...container.rows.map(getLayoutCellsFromRow))
    : [];
}

function getLayoutCellsFromRow(row) {
  if (!Array.isArray(row)) {
    return [];
  }

  const fields = row.slice();

  row.forEach(cell => {
    if (cell.type === 'column') {
      fields.push(...getLayoutCellsFromRowContainer(cell));
    }
  });

  return fields;
}

/**
 * Apply conditions to field.
 *
 * @param {object} field
 * @param {function} getValue
 * @returns {object}
 */
export function applyRules(field, getValue) {
  const rules = field.rules;
  if (!Array.isArray(rules)) {
    return field;
  }

  field = Object.assign({}, field);
  delete field.rules;

  // find the first passing condition
  rules.forEach(cond => {
    if (cond.test && evalTest(cond.test, getValue) && cond.set) {
      Object.entries(cond.set).forEach(([key, value]) => {
        switch (key) {
          case 'key':
          case 'type':
            // changing key and type in conditions is not supported
            break;
          case 'attrs':
            field.attrs = Object.assign({}, field.attrs, value);
            break;
          default:
            field[key] = value;
            break;
        }
      });
    }
  });

  return field;
}
