<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<script>
export default {
  props: {
    /**
     * Maximum height in pixels.
     */
    maxHeight: {
      type: Number,
      required: true,
    },

    /**
     * Minimum font size.
     */
    minFontSize: {
      type: Number,
      default: 6,
    },
  },

  data() {
    return {
      defaultFontSize: null,
    };
  },

  mounted() {
    this.defaultFontSize = Math.round(
      parseFloat(getComputedStyle(this.$el).fontSize)
    );

    this.$_resize();
  },

  methods: {
    $_resize() {
      if (!this.minFontSize) {
        return;
      }

      this.$_setSize(null);

      if (this.$_exceedsHeight()) {
        const fontSize = this.$_search();
        this.$_setSize(fontSize);
      }
    },

    /**
     * Binary search to find the largest font size that does not exceed our sizing.
     *
     * @returns {Number} font size in px
     */
    $_search() {
      let low = this.minFontSize;
      let high = this.defaultFontSize + 1;
      while (low < high) {
        const mid = Math.floor((low + high) / 2);
        this.$_setSize(mid);
        if (this.$_exceedsHeight()) {
          high = mid;
        } else {
          low = mid + 1;
        }
      }
      return Math.max(high - 1, this.minFontSize);
    },

    /**
     * Check if height exceeds allowed space.
     *
     * @returns {boolean}
     */
    $_exceedsHeight() {
      return this.$el.offsetHeight > this.maxHeight;
    },

    $_setSize(size) {
      this.$el.style.fontSize = size != null ? size + 'px' : null;
    },
  },

  render(h) {
    return h('div', this.$scopedSlots.default());
  },
};
</script>
