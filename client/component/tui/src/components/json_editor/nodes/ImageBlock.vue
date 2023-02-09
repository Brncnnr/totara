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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_core
-->

<template>
  <div
    class="tui-imageBlock"
    :class="[displaySize && 'tui-imageBlock--displaySize-' + displaySize]"
  >
    <ResponsiveImage :src="url" :alt="altText" :grow="grow" />
  </div>
</template>

<script>
import ResponsiveImage from 'tui/components/images/ResponsiveImage';

export default {
  components: {
    ResponsiveImage,
  },

  inheritAttrs: false,

  props: {
    filename: {
      type: String,
      required: true,
    },

    url: {
      type: String,
      required: true,
    },

    altText: {
      type: String,
      default: '',
    },

    displaySize: {
      type: String,
    },
  },

  computed: {
    grow() {
      return this.displaySize != null;
    },
  },
};
</script>

<style lang="scss">
.tui-imageBlock {
  margin: 0 0 var(--paragraph-gap) 0;

  @each $name, $size in $tui-media-named-sizes {
    &--displaySize-#{$name} {
      // IE11 does not support the responsive sizes, so specify a fixed fallback
      width: map-get($size, 'fixed');
      width: map-get($size, 'responsive');
    }
  }
}

figure > .tui-imageBlock {
  margin-bottom: var(--gap-1);
}
</style>
