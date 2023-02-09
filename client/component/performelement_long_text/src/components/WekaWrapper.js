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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @module performelement_long_text
 */

import WekaValue from 'editor_weka/WekaValue';

export default {
  props: {
    value: {
      type: Object,
      required: false,
    },
  },

  data() {
    return {
      content: this.value ? WekaValue.fromDoc(this.value) : WekaValue.empty(),
    };
  },

  methods: {
    /**
     * @param {WekaValue} value
     */
    update(value) {
      if (value.isEmpty) {
        this.$emit('update', null);
      }
      this.$emit('update', value.getDoc());
    },
  },

  render() {
    return this.$scopedSlots.default({
      value: this.content,
      update: this.update,
    });
  },
};
