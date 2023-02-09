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

import {
  ensureEntriesLoaded,
  getFieldDef,
  getPrintLayoutCellDef,
} from './defs';
import { prepareApprovers } from './fields/approvals';
import { applyRules, getAllFields, getAllLayoutCells } from './schema';

async function getCommonViewData(schema, values) {
  // make a map of all fields
  const allFields = getAllFields(schema).reduce((acc, field) => {
    acc[field.key] = field;
    return acc;
  }, {});

  const fieldTypes = getAllFields(schema).map(x => x.type);
  const cellTypes = getAllLayoutCells(schema).map(x => x.type);

  // ensure everything we need has been loaded
  await ensureEntriesLoaded(fieldTypes.concat(cellTypes));

  // make a map of all values
  const allValues = {};
  Object.entries(allFields).forEach(([key, field]) => {
    allValues[key] = field.value || field.default;
  });
  Object.assign(allValues, values);

  return { allFields, allValues };
}

export async function getBasicViewData({
  schema: originalSchema,
  values,
  extraData = {},
}) {
  const schema = JSON.parse(JSON.stringify(originalSchema));

  const { allValues } = await getCommonViewData(schema, values);

  schema.fields = processFields(schema.fields, allValues);
  if (schema.sections) {
    schema.sections.forEach(x => {
      x.fields = processFields(x.fields, allValues);
    });
  }

  return {
    schema,
    allValues,
    approvers: prepareApprovers(extraData.approvers || []),
  };
}

export async function getLayoutViewData({
  schema: originalSchema,
  values,
  extraData = {},
}) {
  const schema = JSON.parse(JSON.stringify(originalSchema));

  const { allFields, allValues } = await getCommonViewData(schema, values);

  const context = prepareLayoutContext({
    schema,
    allFields,
    values: allValues,
    extraData,
  });

  // eslint-disable-next-line require-atomic-updates
  schema.fields = processFields(schema.fields, allValues);
  if (schema.sections) {
    schema.sections.forEach(x => {
      x.fields = processFields(x.fields, allValues);
    });
  }

  // add info to sections
  if (schema.print_layout && schema.print_layout.sections) {
    schema.print_layout.sections.forEach(section => {
      section.resolved = {
        noBreak: context.getEntryOption('section', section, 'no_break'),
        breakAfter: context.getEntryOption('section', section, 'break_after'),
        exists:
          !section.section ||
          !!(
            schema.sections &&
            schema.sections.find(x => x.key === section.section)
          ),
      };
    });
  }

  // add info to layout cells
  const allCells = getAllLayoutCells(schema);
  allCells.forEach(item => {
    const def = getPrintLayoutCellDef(item.type);
    item.resolved = def && def.getComponent(item, context);
  });

  return {
    schema,
    allValues,
  };
}

function prepareLayoutContext({ schema, allFields, values, extraData }) {
  const layout = schema.print_layout || {};

  function getOption(name) {
    return layout.options && layout.options[name];
  }

  function getEntryOption(type, entry, name) {
    if (name in entry) {
      return entry[name];
    }
    if (layout.options && layout.options[type]) {
      return layout.options[type][name];
    }
  }

  return {
    allFields,
    values,
    schema,
    style: schema.print_layout && schema.print_layout.style,
    getOption,
    getEntryOption,
    getField: key => allFields[key],
    getValueText: (item, field) =>
      getValueText(values, field, { showKey: item.show_key }),
    getExtraData: key => extraData[key],
  };
}

/**
 * Get text to display as value for field.
 *
 * @param {object} values Map of all form values
 * @param {object} field Field definition from form schema
 * @param {object} [options]
 * @param
 * @returns {string}
 */
function getValueText(values, field, options) {
  const value = values[field.key];
  const def = getFieldDef(field.type);
  if (def && def.displayText) {
    return (
      (options && options.showKey ? value + ' - ' : '') +
      def.displayText(value, field, {
        values: values,
      })
    );
  } else {
    return value == null ? '' : String(value);
  }
}

/**
 * Populate "resolved" property on each field.
 *
 * Also filters out fields that can't appear on the print view.
 *
 * @param {object[]} fields
 * @param {object} allValues
 */
function processFields(fields, allValues) {
  if (!fields) return null;
  return fields.reduce((acc, field) => {
    const value = allValues[field.key];
    const def = getFieldDef(field.type);
    Object.assign(
      field,
      applyRules(field, x => allValues[x])
    );
    delete field.rules;
    // skip if doesn't apply to printing
    if (def && def.visibility && def.visibility.print === false) {
      return acc;
    }
    field.resolved = {
      value,
      valueText: getValueText(allValues, field),
      component: def && def.printFieldComponent,
      props: def && { value },
    };
    acc.push(field);
    return acc;
  }, []);
}
