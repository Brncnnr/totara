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

import tui from 'tui/tui';
import { unique } from 'tui/util';
import * as coreDefs from './core_defs';

/**
 * Defines each field type.
 *
 * @typedef {Object} FieldDef
 * @property {?import('vue').Component} fieldComponent
 *   Component to render for the field. Will be placed inside a row.
 * @property {?import('vue').Component} fieldViewComponent
 *   Component to render for the field in view mode.
 * @property {?import('vue').Component} fieldPrintComponent
 *   Component to render for the field in print mode.
 * @property {?import('vue').Component} rowComponent
 *   Custom component for the entire row.
 * @property {?(value: any, field: any, context: FieldContext) => any} calculatedValue
 *   Value to use in the emitted JSON.
 * @property {?(value: any, field: any, context: FieldContext) => any} displayText
 *   Text to display in view only mode.
 * @property {?(value: any, field: any) => any} prepareForView
 * @property {?(value: any, field: any) => any} prepareForEdit
 * @property {?(value: any, field: any) => any} prepareForSave
 * @property {?FieldSupportsDef} supports
 *   Features supported by this field.
 */

/**
 * Defines validator types.
 *
 * @typedef {Object} ValidatorDef
 * @property {?(val: any, opts: object) => boolean} validate
 * @property {?(val: any, opts: object) => any} message
 */

/**
 * Defines how to render each cell type in the print view.
 *
 * @typedef {Object} PrintLayoutCellDef
 * @property {?(item: object, context: object) => { component: import('vue').Component, props: object }} component
 */

/**
 * @typedef {Object} FieldContext
 * @property {object} values
 */

/**
 * @typedef {Object} FieldSupportsDef
 * @property {?boolean} edit Can this field be edited? This would be false for things like buttons, labels, etc. Defaults to true.
 * @property {?boolean} disable Can this field be disabled? Defaults to true.
 */

/**
 * @typedef {Object} FieldSupports Resolved version of {@see FieldSupportsDef}
 * @property {boolean} edit
 * @property {boolean} disable
 */

const db = {
  /** @type {Object<string, FieldDef>} */
  fields: Object.assign({}, coreDefs.fields),
  /** @type {Object<string, ValidatorDef>} */
  validators: Object.assign({}, coreDefs.validators),
  /** @type {Object<string, PrintLayoutCellDef>} */
  printLayoutCells: Object.assign({}, coreDefs.printLayoutCells),
};

const loadedComponents = {};

/**
 * @param {string} type
 * @returns {FieldDef}
 */
export function getFieldDef(type) {
  return db.fields[type] || null;
}

/**
 * @param {string} key
 * @returns {ValidatorDef}
 */
export function getValidatorDef(key) {
  return db.validators[key] || null;
}

/**
 * @param {string} key
 * @returns {PrintLayoutCellDef}
 */
export function getPrintLayoutCellDef(key) {
  return db.printLayoutCells[key];
}

/**
 * Ensure the specified entries (fields, validators, or layout cells) are loaded.
 *
 * @param {string[]} keys
 */
export async function ensureEntriesLoaded(keys) {
  await loadComponents(extractComponents(keys));
}

/**
 * Extract tui component from entry keys - i.e. a_b/c -> a_b
 *
 * @param {string[]} keys
 * @returns {string[]}
 */
function extractComponents(keys) {
  const comps = [];
  keys.forEach(key => {
    const parts = key.split('/');
    if (parts[1]) {
      comps.push(parts[0]);
    }
  });
  return unique(comps);
}

/**
 * Load fields/validators from specified tui components.
 *
 * @param {string[]} components
 */
async function loadComponents(components) {
  const results = await Promise.all(
    components
      .filter(x => !loadedComponents[x])
      .map(async component => {
        const module = await tui.import(component + '/approvalform');
        return { module, component };
      })
  );

  results.forEach(entry => {
    if (loadedComponents[entry.component]) return;
    loadedComponents[entry.component] = entry.module;
    loadEntries(entry, 'fields');
    loadEntries(entry, 'validators');
    loadEntries(entry, 'printLayoutCells');
  });
}

function loadEntries(entry, type) {
  const { module, component } = entry;

  if (!module[type]) {
    return;
  }
  Object.entries(module[type]).forEach(([key, value]) => {
    db[type][component + '/' + key] = value;
  });
}

/**
 * @param {FieldDef} def
 * @returns {FieldSupports}
 */
export function getFieldDefSupports(def) {
  return Object.assign({ edit: true, disable: true }, def && def.supports);
}
