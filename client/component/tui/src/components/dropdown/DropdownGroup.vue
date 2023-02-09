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
  @module totara_core
-->

<template>
  <div
    role="group"
    class="tui-dropdownGroup"
    :class="{
      'tui-dropdownGroup--separator': separator,
    }"
    :aria-labelledby="$id('grouping')"
  >
    <div :id="$id('grouping')" class="tui-dropdownGroup__header">
      {{ text }}
    </div>
    <div class="tui-dropdownGroup__items">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    separator: Boolean,
    text: String,
  },
};
</script>

<style lang="scss">
.tui-dropdownGroup {
  &__header {
    padding: var(--gap-2) var(--gap-4);
    @include tui-font-heavy();
  }

  &__items {
    display: flex;
    flex-direction: column;

    & > * {
      padding-left: var(--gap-7);
    }
  }

  &--separator &__items {
    & > * {
      border-top: var(--border-width-thin) solid
        var(--dropdown-menu-border-color);
    }
  }
}
</style>
