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
 * @author Dave Wallace <dave.wallace@totaralearning.com>
 * @module tui
 */

import theme from 'tui/theme';
import { config } from 'tui/config';
import { EditorContent } from 'tui/editor';

export default {
  /**
   * Given a data structure and a key, try and find a matched Object within
   * the structure and return that matched Object's value property.
   *
   * @param {array} defaultData
   * @param {string} key
   * @return {string}
   **/
  getCSSVarDefault(defaultData, key) {
    for (let i = 0; i < defaultData.length; i++) {
      if (defaultData[i].name === key) {
        return defaultData[i].value;
      }
    }
  },

  /**
   * Takes an Array and returns an Object whose keys are the `name` property of
   * each Array entry, and whose value is either a custom or default value.
   *
   * @param {array} data
   * @return {object}
   **/
  getResolvedInitialValues(data) {
    let result = {};

    for (let i = 0; i < data.length; i++) {
      let value = data[i].custom || data[i].default;
      result[data[i].name] = {
        value:
          data[i].type === 'boolean'
            ? this.convertToBoolean(value)
            : data[i].custom || data[i].default,
        type: data[i].type,
        selectors: data[i].selectors || null,
      };
    }

    return result;
  },

  /**
   * Given an Object and an Array of data Arrays, iterate each supplied Array
   * for key matches, when a match is found the supplied Array's matching Object
   * overwrites the matched existing key's value. This continues until all
   * supplied Array structures are iterated through in order, resulting in an
   * Array containing each Form field's initial state.
   *
   * @param {object} initialFormData
   * @param {array} dataArrays
   * @return {array}
   **/
  mergeFormData(initialFormData, dataArrays) {
    let resolvedFormData = [];

    Object.keys(initialFormData).forEach(function(field) {
      let fieldData = {
        name: field,
        type: initialFormData[field].type,
        default: initialFormData[field].value,
        custom: null,
        selectors: initialFormData[field].selectors || null,
      };

      // iterate supplied Arrays, overriding fieldData values with each
      // discovered matching key
      for (let i = 0; i < dataArrays.length; i++) {
        let currentArray = dataArrays[i];
        for (let j = 0; j < currentArray.length; j++) {
          if (currentArray[j].name === field) {
            if (typeof currentArray[j].type !== 'undefined') {
              fieldData.type = currentArray[j].type;
            }
            if (typeof currentArray[j].value !== 'undefined') {
              fieldData.default = currentArray[j].value;
            }
            if (typeof currentArray[j].custom !== 'undefined') {
              fieldData.custom = currentArray[j].custom;
            }
            if (typeof currentArray[j].selectors !== 'undefined') {
              fieldData.selectors = currentArray[j].selectors;
            }
          }
        }
      }

      // this our most up-to-date Object representation of a Form field's
      // initial state
      resolvedFormData.push(fieldData);
    });

    return resolvedFormData;
  },

  /**
   * Convert Editor fields' value property to EditorContent instances.
   *
   * @param {object} formFields
   * @param {object} editorFields Array of fields that are classified as Editor instances.
   *
   * @return {array}
   */
  resolveEditorContentFields(formFields, editorFields) {
    let resolvedFormFields = {};
    Object.keys(formFields).forEach(function(field) {
      if (
        Object.keys(editorFields).find(editorField => editorField === field)
      ) {
        resolvedFormFields[field] = Object.assign({}, formFields[field], {
          value: new EditorContent({
            format: editorFields[field].format,
            content: formFields[field].value,
          }),
        });
      } else {
        resolvedFormFields[field] = formFields[field];
      }
    });
    return resolvedFormFields;
  },

  /**
   * Iterates provided Arrays of Theme variable data, in a reliable order, and
   * returns a single Array of merged data. While iterating, duplicate Object
   * data gets overwritten based on the name key inside each Object. This is so
   * later inheriting theme values take precedence.
   *
   * @param {array} themeDataArrays
   * @return {object}
   **/
  mergeCSSVariableData(themeDataArrays) {
    if (!themeDataArrays.length) {
      console.warn('No Theme CSS Variable data available.');
      return;
    }

    // start by making a copy of the first Theme data Object passed in,
    // note that any methods won't be copied using this deep copy technique
    let result = JSON.parse(JSON.stringify(themeDataArrays[0]));

    // iterate the remaining Theme Arrays, add new keys found in additional
    // Theme Array, or replace existing key if it already exists (considered a
    // Theme variable override)
    themeDataArrays.forEach((item, index) => {
      if (index >= 1) {
        Object.entries(item).map(entry => {
          const key = entry[0];
          const value = entry[1];
          result[key] = value;
        });
      }
    });

    return result;
  },

  /**
   * Iterate all keys in supplied Theme data, which contain nested data
   * describing how we should resolve to a single value, and resolve to a
   * single value for each key. The Object keys are converted into an Array
   * whose indices are Objects containing resolved data
   *
   * @param {object} themeData
   * @return {array}
   **/
  processCSSVariableData(themeData) {
    let flattenedData = [];

    Object.entries(themeData).map(entry => {
      const key = entry[0];
      const value = entry[1];
      const resolvedValue = this.resolveCSSVariableValue(value, themeData);
      flattenedData.push({
        name: key,
        default: resolvedValue,
        value: resolvedValue,
        type: 'value', // should always be 'value' instead of supplied 'var' once resolved
      });
    });

    return flattenedData;
  },

  /**
   * Takes an Object describing how to return a single value and returns that
   * single value
   *
   * @param {object} variableData
   * @param {object} variableValues Current values of other variables
   * @return {string}
   **/
  resolveCSSVariableValue(variableData, variableValues) {
    const getVar = name => {
      const varInfo = variableValues[name];
      if (varInfo.type === 'var') {
        return getVar(varInfo.value);
      } else {
        return varInfo.value;
      }
    };

    // simple value to return
    if (variableData.type === 'value' && !variableData.transform) {
      return variableData.value;
    }

    // simple CSS Variable-based value to return
    if (variableData.type === 'var' && !variableData.transform) {
      return getVar(variableData.value);
    }

    // return a processed colour based on transform data provided
    if (variableData.transform) {
      let baseColor =
        variableData.transform.type === 'value'
          ? variableData.transform.source
          : getVar(variableData.transform.source);
      let transform;
      switch (variableData.transform.call) {
        case 'adjust-hex-value-brightness':
          transform = theme.adjustHexValueBrightness(
            baseColor,
            variableData.transform.args[0]
          );
          return transform;
        default:
          console.warn(
            `ThemeSettings CSS Variable processing for [theme_${config.theme.name}] was passed a transform Object but no function to call.`
          );
          return;
      }
    }
  },

  /**
   * Returns a Boolean value from a String input.
   *
   * @param {string} str
   * @return {boolean}
   **/
  convertToBoolean: function(str) {
    // received a Boolean value already
    if (typeof str === 'boolean') {
      return str;
    }

    // try and convert remaining value
    switch (str.toLowerCase().trim()) {
      case 'false':
      case 'no':
      case '0':
        return false;
      default:
        return Boolean(str);
    }
  },
};
