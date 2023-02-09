<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module tui
-->

<script>
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import { throttle } from 'tui/util';

export default {
  props: {
    /**
     * Unique identifier of each slot when handleDataLoop is enabled
     **/
    uniqueKey: {
      type: [String, Number],
    },
  },

  data() {
    return {
      hasInitial: true,
      resizeObserver: null,
    };
  },

  mounted() {
    this.dispatchSizeChange();

    if (typeof ResizeObserver !== 'undefined') {
      this.resizeObserver = new ResizeObserver(
        throttle(this.dispatchSizeChange, 150)
      );
      this.resizeObserver.observe(this.$el);
    }
  },

  beforeDestroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
      this.resizeObserver = null;
    }
  },

  methods: {
    /**
     * Get item offset height
     * @returns {Number}
     */
    $_getHeightSize() {
      return this.$el ? this.$el.offsetHeight : 0;
    },

    /**
     * Emit size change event
     */
    dispatchSizeChange() {
      this.$emit(
        'resize',
        this.uniqueKey,
        this.$_getHeightSize(),
        this.hasInitial
      );
      this.hasInitial = false;
    },
  },

  render() {
    return this.$scopedSlots.default();
  },
};
</script>
