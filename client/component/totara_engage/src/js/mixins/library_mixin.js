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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module totara_engage
 */

/**
 *
 * @param {Object} component
 * @returns {boolean}
 */
export function validatePageComponent(component) {
  return ['component', 'tuicomponent'].every(prop => prop in component);
}

export default {
  props: {
    /**
     * String identifying the selected page.
     */
    pageId: {
      type: String,
      required: true,
    },

    /**
     * Additional properties of the page passed to navigation, content and side panel components.
     */
    pageProps: {
      type: Object,
      default: () => ({}),
    },
  },
};
