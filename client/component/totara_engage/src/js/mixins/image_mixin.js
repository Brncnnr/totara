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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @module totara_engage
 */

export default {
  props: {
    instanceId: {
      required: true,
      type: [String, Number],
    },

    /**
     * Card image or null if this component has no image.
     */
    image: {
      type: String,
    },

    /**
     * Name used for the alt-text
     */
    name: {
      type: String,
    },
  },
};
