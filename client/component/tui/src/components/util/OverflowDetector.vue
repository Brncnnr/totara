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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<script>
import { throttle } from 'tui/util';
import { getOffsetRect } from 'tui/dom/position';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import { isRtl } from 'tui/i18n';

const THROTTLE_UPDATE = 150;

export default {
  data() {
    return {
      measuring: false,
      measured: false,
      overflowing: false,
      visible: Infinity,
    };
  },

  created() {
    // for detecting infinite loops
    this.iters = 0;
    this.failed = false;
  },

  mounted() {
    this.resizeObserver = new ResizeObserver(
      throttle(this.$_update, THROTTLE_UPDATE)
    );
    this.resizeObserver.observe(this.$el);
    this.$_update();
  },

  destroyed() {
    this.resizeObserver.disconnect();
  },

  updated() {
    this.$_update();
  },

  methods: {
    $_update() {
      if (!this.$el || this.failed) {
        return;
      }

      // infinite loop failsafe
      if (this.iters == 0) {
        setTimeout(() => (this.iters = 0), 10);
      }
      this.iters++;
      if (this.iters > 100) {
        console.error(
          '[OverflowDetector] Too many updates. Possible infinite loop, disabling.'
        );
        this.failed = true;
        this.measuring = true;
        return;
      }

      if (this.measuring) {
        // stage 2: measure
        this.$_measure();
        this.measuring = false;
        // mark as measured so we don't get in to an infinite loop with updated()
        this.measured = true;
      } else if (this.measured) {
        // stage 3: unset measured
        this.measured = false;
      } else {
        // stage 1: mark as measuring so we get all items shown
        this.measuring = true;
      }
    },

    /**
     * Check which child elements are overflowing and emit event if that has changed.
     */
    $_measure() {
      const el = this.$el;
      const pos = getOffsetRect(el);

      let overflowing = false;
      let visible = el.children.length;

      // figure out how many children are visible
      for (let i = 0; i < el.children.length; i++) {
        const child = el.children[i];
        const childPos = getOffsetRect(child);
        const overflowRTL = isRtl() ? childPos.left < pos.left - 1 : false;
        const overflowLTR = !isRtl() ? childPos.right > pos.right + 1 : false;
        if (
          overflowLTR ||
          overflowRTL ||
          childPos.bottom - pos.top - 1 > pos.height
        ) {
          // we hit our first non-visible child
          // i starts at 0, so it is equal to the number of visible children
          visible = i;
          overflowing = true;
          break;
        }
      }

      // emit event if state has changed
      if (this.overflowing != overflowing || this.visible != visible) {
        this.overflowing = overflowing;
        this.visible = visible;
        this.$emit('change', { overflowing, visible });
      }
    },
  },

  render() {
    return this.$scopedSlots.default({
      measuring: this.measuring,
    });
  },
};
</script>
