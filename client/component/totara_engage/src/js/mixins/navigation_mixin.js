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

export default {
  props: {
    /**
     * String identifying the selected navigation option.
     */
    selectedId: {
      type: String,
      required: true,
    },

    /**
     * Any specific values you want to pass to the navigation component.
     */
    values: {
      type: Object,
      default: () => ({}),
    },

    showContribute: Boolean,
    canShare: Boolean,
  },

  methods: {
    /**
     *
     * @param {String} name
     * @param {Number} i
     */
    getNavigationLinkClass(name, i) {
      return {
        'tui-engageNavigationPanel__link': true,
        'tui-engageNavigationPanel__link--inactive': this.selectedId !== name,
        'tui-engageNavigationPanel__link--active': this.selectedId === name,
        'tui-engageNavigationPanel__link--first': i === 0,
      };
    },
  },
};
