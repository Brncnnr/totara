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
  <span class="tui-hashtag">
    <a :href="url" class="tui-hashtag__text">
      {{ text }}
    </a>
  </span>
</template>

<script>
export default {
  props: {
    url: {
      type: String,
      required: true,
    },

    text: {
      type: String,
      required: true,
    },
  },

  computed: {
    /** @deprecated since 13.3 */
    attributes: () => null,
  },
};
</script>

<style lang="scss">
.tui-hashtag {
  display: inline-block;
  white-space: normal;

  &__text {
    color: var(--color-state);

    &:hover {
      // Hover state, for now we keep the same color.
      color: var(--color-state);
    }
  }
}
</style>
