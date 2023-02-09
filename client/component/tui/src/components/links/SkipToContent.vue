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
  <a class="tui-skipToContent" :href="'#' + contentId">
    {{ text || $str('a11y_skip_to_main_content', 'totara_core') }}
  </a>
</template>

<script>
export default {
  props: {
    // Skip content target ID
    contentId: String,
    // Skip content custom text
    text: String,
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "a11y_skip_to_main_content"
  ]
}
</lang-strings>

<style lang="scss">
.tui-skipToContent {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  border: 0;
  clip: rect(0, 0, 0, 0);

  &:focus {
    position: static;
    display: block;
    width: auto;
    height: auto;
  }
}
</style>
