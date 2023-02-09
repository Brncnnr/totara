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
  <div class="tui-andBox">
    <p class="tui-andBox__text">
      {{ $str('and_separator', 'totara_core') }}
    </p>
  </div>
</template>

<lang-strings>
  {
    "totara_core" : [
      "and_separator"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-andBox {
  // stylelint-disable-next-line tui/at-extend-only-placeholders
  @extend .tui-orBox;

  &__text {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-orBox__text;
  }
}
</style>
