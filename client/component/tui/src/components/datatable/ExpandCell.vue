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
    class="tui-dataTableExpandCell"
    :class="{
      'tui-dataTableExpandCell--header': header,
      'tui-dataTableExpandCell--stacked ': isStacked,
    }"
    role="cell"
  >
    <template v-if="!header && !empty">
      <SkeletonContent
        v-if="loadingPreview"
        :has-overlay="loadingOverlayActive"
      />

      <ButtonIcon
        v-else
        :aria-expanded="expandState.toString()"
        :aria-label="$str('a11y_row_details', 'totara_core', ariaLabel)"
        :styleclass="{
          transparent: true,
        }"
        :text="isStacked ? text : null"
        @click="$emit('click', $event)"
      >
        <CollapseIcon v-if="expandState" size="100" />
        <ExpandIcon v-else size="100" />
      </ButtonIcon>
    </template>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import CollapseIcon from 'tui/components/icons/Collapse';
import ExpandIcon from 'tui/components/icons/Expand';
import SkeletonContent from 'tui/components/loading/SkeletonContent';

export default {
  components: {
    ButtonIcon,
    CollapseIcon,
    ExpandIcon,
    SkeletonContent,
  },

  props: {
    ariaLabel: String,
    text: {
      required: false,
      type: String,
      default() {
        return this.$str('details', 'totara_core');
      },
    },
    empty: Boolean,
    expandState: Boolean,
    header: Boolean,
    isStacked: Boolean,
    loadingOverlayActive: Boolean,
    loadingPreview: Boolean,
  },

  mounted() {
    if (
      !this.header &&
      !this.loadingPreview &&
      (this.ariaLabel == null || this.ariaLabel.length === 0)
    ) {
      console.error(
        '[ExpandCell] You must pass either aria-label or set hidden to true.'
      );
    }
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "a11y_row_details",
    "details"
  ]
}
</lang-strings>

<style lang="scss">
.tui-dataTableExpandCell {
  display: flex;
  order: 0;
  width: var(--gap-9);
  margin: 0;

  .tui-iconBtn {
    margin: 0 auto;
  }

  &.tui-dataTableExpandCell--header {
    margin-left: 0;
  }

  &--stacked {
    order: 1;
    width: 100%;
    margin: var(--gap-2) 0;
  }
}
</style>
