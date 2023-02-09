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
    class="tui-dataTableSelectRowCell"
    :class="[
      hidden && 'tui-dataTableSelectRowCell--hidden',
      valign && 'tui-dataTableSelectRowCell--valign-' + valign,
      isStacked && 'tui-dataTableSelectRowCell--stacked',
    ]"
    role="cell"
  >
    <div
      v-if="loadingPreview"
      class="tui-dataTableSelectRowCell__loader"
      :class="{
        'tui-dataTableSelectRowCell__loader--large': largeCheckBox,
      }"
    >
      <SkeletonContent :has-overlay="loadingOverlayActive" />
    </div>
    <Checkbox
      v-else
      :large="largeCheckBox"
      :no-label-offset="noLabelOffset"
      :aria-label="$str('selectrow', 'totara_core') + ': ' + rowLabel"
      :checked="checked"
      :disabled="disabled || hidden"
      @change="$emit('change', $event)"
    />
  </div>
</template>

<script>
import Checkbox from 'tui/components/form/Checkbox';
import SkeletonContent from 'tui/components/loading/SkeletonContent';

export default {
  components: {
    Checkbox,
    SkeletonContent,
  },

  props: {
    checked: Boolean,
    disabled: Boolean,
    hidden: Boolean,
    largeCheckBox: Boolean,
    loadingOverlayActive: Boolean,
    loadingPreview: Boolean,
    noLabelOffset: Boolean,
    valign: String,
    rowLabel: {
      type: String,
      required: true,
    },
    isStacked: Boolean,
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "selectrow"
  ]
}
</lang-strings>

<style lang="scss">
.tui-dataTableSelectRowCell {
  display: flex;
  flex-direction: column;

  &--hidden {
    visibility: hidden;
  }

  &__loader {
    width: var(--form-checkbox-size);
    height: var(--form-checkbox-size);

    &--large {
      width: var(--form-checkbox-size-large);
      height: var(--form-checkbox-size-large);
    }
  }

  &--stacked&--hidden {
    height: 0;
  }

  &--valign {
    &-start {
      justify-content: flex-start;
    }

    &-center {
      justify-content: center;
    }

    &-end {
      justify-content: flex-end;
    }
  }
}
</style>
