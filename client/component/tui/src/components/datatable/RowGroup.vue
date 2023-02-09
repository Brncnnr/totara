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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div
    v-if="wrap"
    v-focus-within
    class="tui-dataTableRowGroup"
    :class="{
      'tui-dataTableRowGroup--selected': selected,
      'tui-dataTableRowGroup--stacked': isStacked,
    }"
    role="rowgroup"
  >
    <slot />
  </div>
  <div v-else class="tui-dataTableRows">
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    selected: Boolean,
    wrap: Boolean,
    isStacked: Boolean,
  },
};
</script>

<style lang="scss">
.tui-dataTableRowGroup {
  padding: 0;
  border-top: 2px solid var(--datatable-row-first-border-color);

  &--stacked {
    padding: 0 var(--gap-2);
  }

  & + & {
    border-top: 1px solid var(--datatable-row-border-color);
  }

  &:last-child {
    border-bottom: 1px solid var(--datatable-row-border-color);
  }

  &.tui-focusWithin,
  &:active,
  &:hover {
    background: var(--datatable-row-bg-color-focus);
  }

  &--selected {
    background: var(--datatable-row-bg-color-active);

    &:hover {
      background: var(--datatable-row-bg-color-focus);
    }
  }
}
</style>
