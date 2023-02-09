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
 * @module editor_weka
 */

export function getJsonAttrs(dom) {
  try {
    return JSON.parse(dom.getAttribute('data-attrs'));
  } catch (e) {
    return {};
  }
}

export function attrGetter(names) {
  return dom => {
    const attrs = {};
    names.forEach(x => {
      const value = dom.getAttribute(x);
      attrs[x] = value == null ? undefined : value;
    });
    return attrs;
  };
}
