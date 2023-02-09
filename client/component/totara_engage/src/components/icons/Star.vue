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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->
<template>
  <svg
    :ref="svgId"
    class="tui-engageStarIcon"
    :focusable="focusable"
    :tabindex="tabIndex"
    :role="role"
    :height="getSize"
    :width="getSize"
    :viewBox="viewBox"
    :aria-label="ariaLabel"
    @mousemove="mouseMoving"
    @click="selected"
    @keyup.enter="selected"
    @keyup.space="selected"
  >
    <linearGradient :id="grad" x1="0" x2="100%" y1="0" y2="0">
      <stop :class="getStopClass(!rtl)" :offset="getFill" />
      <stop :class="getStopClass(rtl)" :offset="getFill" />
    </linearGradient>

    <polygon
      :points="starPointsToString"
      :fill="getGradId"
      :stroke-width="border"
      class="tui-engageStarIcon__polygon"
    />
    <polygon :points="starPointsToString" :fill="getGradId" />
  </svg>
</template>

<script>
const defaultStarPoints = [45, 0, 15, 90, 90, 34, 0, 34, 75, 90];
export default {
  inheritAttrs: false,

  props: {
    fill: {
      type: Number,
      default: 0,
    },
    points: {
      type: Array,
      default() {
        return [];
      },
    },
    rtl: {
      type: Boolean,
      default: false,
    },
    starIndex: {
      type: Number,
      required: true,
    },
    defaultSize: {
      type: Number,
      default: 22,
    },
    borderWidth: {
      type: Number,
      default: 0,
    },
    size: {
      type: Number,
      default: 15,
    },
    readOnly: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      // Declare starPoints to be use going forward instead of the points prop as we will be getting an error when we try
      // to change the prop's values in calculatePoints method. The error that we are trying to avoid is:
      //    Avoid mutating a prop directly since the value will be overwritten whenever the parent component re-renders.
      //    Instead, use a data or computed property based on the prop's value. Prop being mutated: "points"
      starPoints: defaultStarPoints,
      grad: this.$id('engageStarComp'),
      starSize: 0,
    };
  },

  computed: {
    getSize() {
      // Adjust star size
      const size =
        this.borderWidth <= 0
          ? parseInt(this.size, 10) - parseInt(this.border, 10)
          : this.size;
      return parseInt(size, 10) + parseInt(this.border, 10);
    },
    border() {
      return this.borderWidth <= 0 ? 1 : this.borderWidth;
    },
    starPointsToString() {
      return this.starPoints.join(',');
    },
    getGradId() {
      return 'url(#' + this.grad + ')';
    },
    getFill() {
      return `${this.rtl ? 100 - this.fill : this.fill}%`;
    },
    maxStarSize() {
      return Math.max(...this.starPoints);
    },
    viewBox() {
      // Viewport x-offset y-offset width height. We want a square placed in the
      // top left corner of the viewport, width and height will be the same value.
      return '0 0 ' + this.maxStarSize + ' ' + this.maxStarSize;
    },
    svgId() {
      return this.$id('engageStarSVG');
    },
    focusable() {
      return this.readOnly ? 'false' : null;
    },
    tabIndex() {
      return this.readOnly ? null : 0;
    },
    role() {
      return this.readOnly ? null : 'button';
    },
    ariaLabel() {
      return this.starIndex === 1
        ? this.$str('rate_one_star', 'totara_engage')
        : this.$str('rate_x_stars', 'totara_engage', this.starIndex);
    },
  },

  mounted() {
    this.starPoints = this.points.length ? this.points : this.starPoints;
    this.starSize = this.$refs[this.svgId].clientWidth || this.defaultSize;
    this.calculatePoints();
  },

  methods: {
    mouseMoving(event) {
      this.$emit('star-mouse-move', {
        event: event,
        index: this.starIndex,
        position: this.getPosition(event),
      });
    },
    getPosition(event) {
      if (!event.offsetX) {
        return false;
      }

      // Calculate position in percentage.
      const offset = this.rtl
        ? Math.min(event.offsetX, this.starSize)
        : Math.max(event.offsetX, 1);
      const position = Math.round((100 / this.starSize) * offset);

      return Math.min(position, 100);
    },
    selected(event) {
      this.$emit('star-selected', {
        index: this.starIndex,
        position: this.getPosition(event),
      });
    },
    calculatePoints() {
      this.starPoints = this.starPoints.map(point => {
        return (this.starSize / this.maxStarSize) * point;
      });
    },
    getStopClass(filled) {
      return {
        'tui-engageStarIcon__filled': filled,
        'tui-engageStarIcon__unfilled': !filled,
      };
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "rate_one_star",
      "rate_x_stars"    
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageStarIcon {
  display: block;
  margin-right: var(--gap-1);

  &__polygon {
    stroke: var(--color-neutral-6);
  }
}
</style>
