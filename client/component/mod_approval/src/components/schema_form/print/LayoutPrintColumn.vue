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
  <div
    class="tui-mod_approval-layoutPrintColumn"
    :class="{ 'tui-mod_approval-layoutPrintColumn--inField': inField }"
  >
    <LayoutPrintRow
      v-for="(row, rowIndex) in rows"
      :key="rowIndex"
      :items="row"
    />
  </div>
</template>

<script>
import LayoutPrintRow from 'mod_approval/components/schema_form/print/LayoutPrintRow';

export default {
  components: {
    LayoutPrintRow,
  },

  props: {
    rows: {
      type: Array,
      required: true,
    },

    inField: Boolean,
  },
};
</script>

<style lang="scss">
.tui-mod_approval-layoutPrintColumn {
  display: flex;
  flex-direction: column;
  flex-grow: 1;

  &--inField {
    margin: calc(var(--border-width-thin) * -1)
      calc(var(--border-width-thin) * -1) 0 0;
  }
}
</style>
