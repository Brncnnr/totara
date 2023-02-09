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
  <div class="tui-orBox">
    <h6 class="tui-orBox__text">
      {{ $str('or_separator', 'totara_core') }}
    </h6>
  </div>
</template>

<lang-strings>
  {
    "totara_core" : [
      "or_separator"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-orBox {
  display: inline-flex;
  margin: 0 auto;
  border: var(--border-width-thin) solid var(--color-neutral-6);
  border-radius: 6px;

  &__text {
    margin: 0;
    padding: var(--gap-2) var(--gap-4);
    text-transform: uppercase;
    @include tui-font-heading-label();
  }
}
</style>
