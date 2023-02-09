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
 * @module samples
 */

function trimNewlines(str) {
  return str.replace(/^(?:\r?\n)+/, '').replace(/(?:\r?\n)+$/, '');
}

function generateSampleCodeAssignment(name, source) {
  const js = JSON.stringify;
  source = trimNewlines(source);
  return `export default function (component) {
  if (!component.options.__sampleCode) {
    component.options.__sampleCode = {};
  }
  component.options.__sampleCode[${js(name)}] = ${js(source)};
  if (!component.options.computed) component.options.computed = {};
  if (!component.options.computed.sampleCode) {
    component.options.computed.sampleCode = function() {
      return this.$options.__sampleCode;
    };
  }
}`;
}

module.exports = {
  generateSampleCodeAssignment,
};
