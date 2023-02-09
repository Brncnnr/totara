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

// Legacy CSS and JS are tied together.
const isLegacy = !!process.env.LEGACY_BUNDLE;

let rootStyle = null;
let inited = false;

function ensureSetup() {
  if (inited) {
    return;
  }
  inited = true;
  if (typeof window !== 'undefined') {
    let rootNode = document.documentElement;
    if (isLegacy) {
      rootNode = document.getElementById('cssVarCompatRoot');
      if (!rootNode) {
        rootNode = document.createElement('div');
        rootNode.setAttribute('id', 'cssVarCompatRoot');
        document.body.appendChild(rootNode);
      }
    }
    rootStyle = window.getComputedStyle(rootNode);
  }
}

const varCache = new Map();
const arrayVarCache = new Map();

export default {
  /**
   * Get the value of a root-level custom property.
   * Omit the leading '--'.
   *
   * @param {string} name
   * @return {?string}
   */
  getVar(name) {
    ensureSetup();
    if (!rootStyle) {
      return null;
    }
    if (varCache.has(name)) {
      return varCache.get(name);
    }
    // this code won't execute until styles in head have loaded so it's okay.
    // HTML5 specifies that JS that comes after CSS waits for the CSS to load
    // before executing
    let value = rootStyle.getPropertyValue('--' + name);
    value = value == null || value === '' ? null : value.trim();
    if (value === null && isLegacy) {
      // can't read custom properties in IE, so they are transformed to have a
      // different name, which we can read by indexing into style
      value = rootStyle['-var--' + name];
      value = value == null || value === '' ? null : value.trim();
    }
    varCache.set(name, value);
    return value;
  },

  /**
   * Get the supported variable usage of a given variable name
   *
   * @param {string} name
   * @return {string}
   */
  getVarUsage(name) {
    return isLegacy ? this.getVar(name) : `var(--${name})`;
  },

  /**
   * Get the values of a set of sequential root-level custom properties.
   * The properties must be formatted as `--{name}-{index}` where index starts from 1
   * and the rest of the properties must be sequential (no holes).
   *
   * The maximum size of an array is 100 entries.
   *
   * @param {string} name
   * @return {Array<string>}
   */
  getArrayVar(name) {
    ensureSetup();
    if (!rootStyle) {
      return [];
    }
    if (arrayVarCache.has(name)) {
      return arrayVarCache.get(name);
    }
    const values = [];
    for (var i = 1; i < 101; i++) {
      const value = this.getVar(name + '-' + i);
      if (value === null) break;
      values.push(value);
    }
    arrayVarCache.set(name, values);
    return values;
  },

  /**
   * Takes a Hex colour value, # symbol optional, and an adjustment value
   * between 0 and 255 and returns a lighter or darker Hex colour value.
   *
   * @param {string} hexColorValue
   * @param {number} adjustmentValue
   * @return {string}
   **/
  adjustHexValueBrightness(hexColorValue, adjustmentValue) {
    let usePound = false;

    if (hexColorValue[0] == '#') {
      hexColorValue = hexColorValue.slice(1);
      usePound = true;
    }

    let R = parseInt(hexColorValue.substring(0, 2), 16);
    let G = parseInt(hexColorValue.substring(2, 4), 16);
    let B = parseInt(hexColorValue.substring(4, 6), 16);

    R = R + adjustmentValue;
    G = G + adjustmentValue;
    B = B + adjustmentValue;

    if (R > 255) R = 255;
    else if (R < 0) R = 0;

    if (G > 255) G = 255;
    else if (G < 0) G = 0;

    if (B > 255) B = 255;
    else if (B < 0) B = 0;

    let RR = R.toString(16).length == 1 ? '0' + R.toString(16) : R.toString(16);
    let GG = G.toString(16).length == 1 ? '0' + G.toString(16) : G.toString(16);
    let BB = B.toString(16).length == 1 ? '0' + B.toString(16) : B.toString(16);

    return (usePound ? '#' : '') + RR + GG + BB;
  },
};
