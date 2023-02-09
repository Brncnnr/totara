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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-skeletonContent tui-skeletonContent--shimmer"
    :class="[
      charLength && 'tui-skeletonContent--charLength-' + charLength,
      hasOverlay && 'tui-skeletonContent--hasOverlay',
    ]"
  >
    <div
      v-for="line in lines"
      :key="line"
      aria-hidden="true"
      class="tui-skeletonContent__line"
    >
      &nbsp;
    </div>
  </div>
</template>

<script>
import { charLengthProp } from 'tui/components/form/form_common';

export default {
  props: {
    // Width of element
    charLength: charLengthProp,
    // Has a loading overlay above this element (make it more visible)
    hasOverlay: Boolean,
    lines: {
      type: Number,
      default: 1,
    },
  },
};
</script>

<style lang="scss">
:root {
  // Background colour of skeleton area
  // Should have at least 3:1 contrast ratio
  // https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html
  // Future option https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-contrast
  --skeleton-content-background: #949494;
  // Background colour of skeleton area when under a loading overlay
  --skeleton-content-overlay-background: var(--color-neutral-5);
  // Colour of shimmer
  --skeleton-content-shimmer-color: #aaa;
  // Colour of shimmer when under a loading overlay
  --skeleton-content-shimmer-overlay-color: #e6e5e5;
  // Border radius for skeleton content
  --skeleton-content-border-radius: var(--border-radius-small);
}

.tui-skeletonContent {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: var(--skeleton-content-background);
  border-radius: var(--skeleton-content-border-radius);

  @include tui-char-length-classes();

  &--hasOverlay {
    background: var(--skeleton-content-overlay-background);
  }

  &--shimmer {
    background: linear-gradient(
      -55deg,
      var(--skeleton-content-background) 0%,
      var(--skeleton-content-background) 35%,
      var(--skeleton-content-shimmer-color) 50%,
      var(--skeleton-content-background) 58%,
      var(--skeleton-content-background) 100%
    );
    background-size: 1000px 100%;
    background-attachment: fixed;
    animation: tui-skeletonShimmer 3s linear infinite;

    @media (prefers-reduced-motion) {
      animation: none;
    }
  }

  @keyframes tui-skeletonShimmer {
    0% {
      /*!rtl:ignore*/
      background-position: 0 0;
    }
    100% {
      /*!rtl:ignore*/
      background-position: 1000px 0;
    }
  }
}

.tui-skeletonContent--hasOverlay.tui-skeletonContent--shimmer {
  background: linear-gradient(
    -55deg,
    var(--skeleton-content-overlay-background) 0%,
    var(--skeleton-content-overlay-background) 35%,
    var(--skeleton-content-shimmer-overlay-color) 50%,
    var(--skeleton-content-overlay-background) 58%,
    var(--skeleton-content-overlay-background) 100%
  );
  background-size: 1000px 100%;
  background-attachment: fixed;
}
</style>
