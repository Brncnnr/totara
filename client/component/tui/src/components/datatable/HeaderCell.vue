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
    class="tui-dataTableHeaderCell"
    :class="[
      align && 'tui-dataTableCell--align-' + align,
      size && 'tui-dataTableCell--size_' + size,
      valign && 'tui-dataTableCell--valign-' + valign,
      isStacked && 'tui-dataTableHeaderCell--stacked',
    ]"
    role="columnheader"
  >
    <SkeletonContent
      v-if="loadingPreview"
      :has-overlay="loadingOverlayActive"
    />

    <template v-else>
      <slot />
    </template>
  </div>
</template>

<script>
import SkeletonContent from 'tui/components/loading/SkeletonContent';

export default {
  components: {
    SkeletonContent,
  },

  props: {
    align: {
      type: String,
      validator: val => ['start', 'center', 'end'].indexOf(val) !== -1,
    },
    loadingOverlayActive: Boolean,
    loadingPreview: Boolean,
    size: String,
    valign: {
      type: String,
      validator: val => ['start', 'center', 'end'].indexOf(val) !== -1,
    },
    isStacked: Boolean,
  },
};
</script>

<style lang="scss">
.tui-dataTableHeaderCell {
  // stylelint-disable-next-line tui/at-extend-only-placeholders
  @extend .tui-dataTableCell;
  @include tui-font-heading-label();
  display: flex;
  color: var(--datatable-cell-header-text-color);
  font-weight: bold;

  &--stacked {
    display: none;
  }
}
</style>
