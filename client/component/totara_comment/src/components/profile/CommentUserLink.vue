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
  @module totara_comment
-->

<template>
  <a
    v-if="profileUrl"
    :href="profileUrl"
    class="tui-commentUserLink"
    :class="{
      'tui-commentUserLink--smallLink': isSmall,
      'tui-commentUserLink--largeLink': isLarge,
    }"
  >
    {{ userFullName }}
  </a>

  <span
    v-else
    class="tui-commentUserLink"
    :class="{
      'tui-commentUserLink--smallText': isSmall,
      'tui-commentUserLink--largeText': isLarge,
    }"
  >
    {{ userFullName }}
  </span>
</template>

<script>
import { isValid, SIZE_SMALL, SIZE_LARGE } from 'totara_comment/size';

export default {
  props: {
    userFullName: {
      type: String,
      required: true,
    },

    size: {
      type: String,
      default() {
        return SIZE_SMALL;
      },

      /**
       *
       * @param {String} prop
       * @return {boolean}
       */
      validator(prop) {
        return isValid(prop);
      },
    },

    profileUrl: String,
  },

  computed: {
    isSmall() {
      return SIZE_SMALL === this.size;
    },

    isLarge() {
      return SIZE_LARGE === this.size;
    },
  },
};
</script>

<style lang="scss">
.tui-commentUserLink {
  &--smallLink {
    @include tui-font-link-small();
    @include tui-font-heavy();
  }

  &--largeLink {
    @include tui-font-link();
    @include tui-font-heavy();
  }

  &--smallText {
    @include tui-font-body-small();
    @include tui-font-heavy();
  }

  &--largeText {
    @include tui-font-body();
    @include tui-font-heavy();
  }
}
</style>
