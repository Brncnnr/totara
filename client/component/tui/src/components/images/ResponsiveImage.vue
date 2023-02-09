<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module totara_core
-->

<template>
  <img
    class="tui-responsiveImage"
    :class="{
      'tui-responsiveImage--grow': grow,
    }"
    :src="src"
    :alt="alt"
  />
</template>

<script>
export default {
  props: {
    src: {
      type: String,
      required: true,
    },
    alt: String,
    grow: Boolean,
  },
};
</script>

<style lang="scss">
.tui-responsiveImage {
  display: block;
  max-width: 100%;
  height: auto;

  &--grow {
    width: 100%;
  }
}
</style>
