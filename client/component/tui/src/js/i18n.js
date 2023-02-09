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

import {
  loadStrings as rawLoadStrings,
  getString as rawGetString,
  hasString as rawHasString,
} from './internal/lang_string_store';

/**
 * Normalize component name for i18n API.
 *
 * @param {string} component
 * @returns {string}
 */
const normalizeComponent = component => {
  if (!component || component === 'moodle') {
    return 'core';
  } else if (component.startsWith('core_')) {
    return component.slice(5);
  } else if (component.startsWith('mod_')) {
    return component.slice(4);
  }
  return component;
};

/**
 * Get and format a language string.
 *
 * @param {string} key Name of string, e.g. 'cancel'.
 * @param {string} [component] Name of Totara component, e.g. 'core'.
 * @param {*=} [param] Optional variable to populate placeholder with.
 * @return {string}
 */
export function getString(key, component, param) {
  component = normalizeComponent(component);
  let str = rawGetString(key, component);
  if (!str) {
    return `[[${key}, ${component}]]`;
  }
  str = replacePlaceholders(str, param);

  return str;
}

/**
 * Check if the provided language string exists and is loaded.
 *
 * @param {string} key Name of string, e.g. 'cancel'.
 * @param {string} component Name of Totara component, e.g. 'core'.
 * @return {boolean}
 */
export function hasString(key, component) {
  component = normalizeComponent(component);
  return !!rawHasString(key, component);
}

/**
 * Filter specified language strings to those which are not loaded.
 *
 * @param {Array<{key: string, component: string}>} requests
 * @return {Array<{key: string, component: string}>}
 */
export function unloadedStrings(requests) {
  return requests.filter(req => !hasString(req.key, req.component));
}

/**
 * Load all of the specified strings so that they are available to use.
 *
 * @param {Array<{key: string, component: string}>} requests
 */
export async function loadStrings(requests) {
  if (requests.length === 0) {
    return;
  }

  return rawLoadStrings(
    requests.map(x => {
      const normalized = normalizeComponent(x.component);
      if (normalized !== x.component) {
        return Object.assign({}, x, { component: normalized });
      }
      return x;
    })
  );
}

let isRtlValue = null;

/**
 * Check if the current language is right-to-left.
 *
 * @returns {boolean}
 */
export function isRtl() {
  if (isRtlValue === null) {
    isRtlValue = document.body.classList.contains('dir-rtl');
  }
  return isRtlValue;
}

/**
 * Convert left-to-right side to the correct side for the current language.
 *
 * 'left' and 'right get swapped for RTL languages, otherwise side is passed
 * through unmodified.
 *
 * @param {string} side 'left', 'right', 'top', or 'bottom'
 * @returns {string}
 */
export function langSide(side) {
  if (!isRtl()) {
    return side;
  }
  switch (side) {
    case 'left':
      return 'right';
    case 'right':
      return 'left';
    default:
      return side;
  }
}

/**
 * Represents a language string that can be loaded.
 */
class LangString {
  constructor(...bits) {
    this.bits = bits;
  }

  loaded() {
    return hasString(...this.bits);
  }

  toRequest() {
    return { component: this.bits[1], key: this.bits[0] };
  }

  toString() {
    return this.loaded()
      ? getString(...this.bits)
      : `[[${this.bits.slice(0, 2)}]]`;
  }
}

/**
 * Create a placeholder for a language string that can be loaded.
 *
 * @param {string} key Name of string, e.g. 'cancel'.
 * @param {string} component Name of Totara component, e.g. 'core'.
 * @param {*=} param Optional variable to populate placeholder with.
 * @returns {LangString}
 */
export function langString(key, component, param) {
  return new LangString(key, component, param);
}

/**
 * Checks if the provided argument is a language string placeholder.
 *
 * @param {*} str
 * @returns {boolean}
 */
export function isLangString(str) {
  return str instanceof LangString;
}

/**
 * Load lang string objects.
 *
 * @param {Array} strings
 * @returns {Promise}
 */
export async function loadLangStrings(strings) {
  return loadStrings(
    strings.filter(x => isLangString(x) && !x.loaded()).map(x => x.toRequest())
  );
}

/**
 * Convert language string request to vue requirements format.
 *
 * @param {Array<{ component: string, key: string }|LangString>} strings
 * @returns {object}
 */
export function toVueRequirements(strings) {
  const obj = {};
  strings.forEach(str => {
    if (isLangString(str)) str = str.toRequest();
    const cmp = str.component;
    if (!obj[cmp]) obj[cmp] = [];
    if (!obj[cmp].includes(str.key)) obj[cmp].push(str.key);
  });
  return obj;
}

/**
 * Replace {$a} and {$a->prop} placeholders in string.
 *
 * @param {string} str
 * @param {*} a
 * @returns {string}
 */
function replacePlaceholders(str, a) {
  if (a == null) {
    return str;
  }
  if (typeof a == 'object') {
    return str.replace(/\{\$a->(.*?)\}/g, (full, prop) =>
      a[prop] != null ? a[prop] : full
    );
  } else {
    return str.replace(/\{\$a\}/g, a);
  }
}
