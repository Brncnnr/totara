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
  <div
    class="tui-dataTableRowHeader"
    :class="{
      'tui-dataTableRowHeader--empty': empty,
      'tui-dataTableRowHeader--indented': indented,
      'tui-dataTableRowHeader--stacked': isStacked,
      'tui-dataTableRowHeader--stackedGap': isStacked && stackedGap,
    }"
    role="row"
  >
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    empty: Boolean,
    indented: Boolean,
    isStacked: Boolean,
    stackedGap: Boolean,
  },
};
</script>

<style lang="scss">
.tui-dataTableRowHeader {
  position: relative;
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: var(--gap-3) var(--gap-1);

  &--stacked {
    display: block;
    padding: 0;
  }

  &--stackedGap {
    margin-top: var(--gap-2);
  }

  &--empty {
    display: none;
  }

  & > * + * {
    margin-left: var(--gap-4);
  }

  &--indented {
    padding-left: var(--gap-11);
  }
}
</style>
