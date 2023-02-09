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
     * Specify the container details if the new resource would be created
     * inside a container.
     */
    container: {
      type: Object,
      default: null,
      validator: container => {
        const valid = ['instanceId', 'component', 'access'].every(
          prop => prop in container
        );

        if (!container.autoShareRecipient) {
          return valid;
        }

        // Continue check the validation of the autoShareRecipient props.
        return ['area', 'name'].every(prop => prop in container);
      },
    },
  },

  computed: {
    containerValues() {
      const { instanceId, component, access, area, name } =
        this.container || {};
      return {
        instanceId,
        component,
        access,
        area,
        name,
      };
    },
  },
};
