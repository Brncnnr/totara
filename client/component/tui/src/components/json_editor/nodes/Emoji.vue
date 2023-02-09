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
  <span class="tui-emoji">
    {{ text }}
  </span>
</template>

<script>
export default {
  inheritAttrs: false,

  props: {
    shortCode: {
      type: [String, Number],
      required: true,
    },
  },

  computed: {
    text() {
      return String.fromCodePoint('0x' + this.shortCode);
    },
  },
};
</script>

<style lang="scss">
.tui-emoji {
  display: inline-block;
  font-family: 'Noto Color Emoji', 'Apple Color Emoji', 'Segoe UI Emoji',
    'Segoe UI Symbol', Times, Symbola, Aegyptus, Code2000, Code2001, Code2002,
    Musica, serif, LastResort;
  white-space: normal;
  text-decoration: none;
  font-variant-emoji: emoji;
}
</style>
