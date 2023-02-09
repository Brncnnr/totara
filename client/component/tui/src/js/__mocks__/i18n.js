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

const strings = {};

class LangString {
  constructor(...bits) {
    this.bits = bits;
  }

  loaded() {
    return hasString(...this.bits);
  }

  toString() {
    return getString(...this.bits);
  }
}

export const getString = jest.fn((k, c, a) =>
  replacePlaceholders(strings[`${c}/${k}`] || `[[${k}, ${c}]]`, a)
);
export const hasString = jest.fn((k, c) => !!strings[`${c}/${k}`]);
export const unloadedStrings = jest.fn(() => []);
export const loadStrings = jest.fn(requests => {
  requests.forEach(x => {
    strings[`${x.component}/${x.key}`] = `str:${x.component}/${x.key}`;
  });
});
export const isRtl = jest.fn(() => false);
export const langSide = jest.fn(side => side);
export const langString = (...args) => new LangString(...args);
export const isLangString = str => str instanceof LangString;
export const loadLangStrings = jest.fn(() => Promise.resolve());
export const toVueRequirements = jest.fn(() => ({}));

export const __setString = (k, c, v) => {
  strings[`${c}/${k}`] = v;
};

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
