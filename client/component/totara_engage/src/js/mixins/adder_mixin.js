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
  methods: {
    /**
     * Create a string that will uniquely identify a specific card
     * in the adder table. It is also a JSON parsable string that
     * can easily be passed on to a graphql query.
     *
     * @param {Object} card
     * @returns {string}
     */
    createCardId(card) {
      return JSON.stringify({
        component: card.component,
        itemid: card.instanceid,
      });
    },
  },
};
