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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-layoutPrintRow">
    <div
      v-for="(entry, colIndex) in computedItems"
      :key="colIndex"
      class="tui-mod_approval-layoutPrintRow__col"
      :style="{ flexGrow: entry.units }"
    >
      <div
        class="tui-mod_approval-layoutPrintRow__col-inner"
        :class="{
          'tui-mod_approval-layoutPrintRow__col-inner--disabled':
            entry.disabled,
        }"
      >
        <component :is="entry.component" v-bind="entry.props" />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    items: {
      type: Array,
      required: true,
    },
  },

  computed: {
    computedItems() {
      return this.items.map(item => {
        return Object.assign({ units: item.units }, item.resolved);
      });
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-layoutPrintRow {
  display: flex;
  flex-grow: 1;
  break-inside: avoid;

  &__col {
    display: flex;
    flex-basis: 0%;
    flex-grow: 1;
    flex-shrink: 0;
    min-width: 0;

    &-inner {
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      min-width: 0;
      margin: 0 0 calc(var(--border-width-thin) * -1)
        calc(var(--border-width-thin) * -1);
      overflow: hidden; /* Required for correct layout in IE */
      overflow-wrap: break-word;
      overflow-wrap: anywhere;
      border: var(--border-width-thin) solid black;

      &--disabled {
        background-color: var(--color-neutral-4);
      }
    }
  }
}
</style>
