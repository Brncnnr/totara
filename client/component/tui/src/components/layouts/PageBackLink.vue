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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <a class="tui-pageBackLink" :href="link">
    <BackArrow class="tui-pageBackLink__icon" />
    <span>{{ text }}</span>
  </a>
</template>

<script>
import BackArrow from 'tui/components/icons/BackArrow';

export default {
  components: {
    BackArrow,
  },

  props: {
    link: {
      required: true,
      type: String,
    },
    text: {
      required: true,
      type: String,
    },
  },
};
</script>

<style lang="scss">
.tui-pageBackLink {
  display: inline-flex;
  align-items: center;

  &__icon {
    margin-right: var(--gap-1);
    // Negative Offset to left align arrow with page content
    margin-left: -0.19em;
  }
}
</style>
