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
  <span class="tui-mention">
    <a :href="profileUrl" class="tui-mention__displayName">
      {{ displayName }}
    </a>

    <!-- extra space for mini profile -->
  </span>
</template>

<script>
export default {
  props: {
    userId: {
      type: [Number, String],
    },

    fullname: {
      type: String,
      required: true,
    },
  },

  computed: {
    displayName() {
      if (!this.fullname) {
        return this.$str('unknownuser', 'core');
      }

      return this.fullname;
    },
    profileUrl() {
      if (!this.userId) {
        return '#';
      }

      return this.$url('/user/profile.php', { id: this.userId });
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "unknownuser"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-mention {
  display: inline-block;
  white-space: normal;

  &__displayName {
    color: var(--color-state);

    &:hover {
      // Hover state, for now we keep the same color.
      color: var(--color-state);
    }
  }
}
</style>
