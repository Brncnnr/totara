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
    class="tui-dataTableSelectVisibleRowsCell"
    :class="{
      'tui-dataTableSelectVisibleRowsCell--hidden': hidden,
      'tui-dataTableSelectVisibleRowsCell--stacked': isStacked && !hidden,
    }"
    role="cell"
  >
    <div
      v-if="loadingPreview"
      class="tui-dataTableSelectVisibleRowsCell__loader"
      :class="{
        'tui-dataTableSelectVisibleRowsCell__loader--large': largeCheckBox,
      }"
    >
      <SkeletonContent :has-overlay="loadingOverlayActive" />
    </div>
    <template v-else>
      <Checkbox
        :large="largeCheckBox"
        :no-label-offset="noLabelOffset"
        :checked="checked"
        :aria-label="$str('selectallrows', 'totara_core')"
        :disabled="disabled || hidden"
        @change="$emit('change', $event)"
      >
        <template>
          <span
            class="tui-dataTableSelectVisibleRowsCell__label"
            :class="{
              'tui-dataTableSelectVisibleRowsCell__label--large': largeCheckBox,
              'tui-dataTableSelectVisibleRowsCell__label--stacked':
                isStacked && !hidden,
            }"
          >
            {{ $str('selectallrows', 'totara_core') }}
          </span>
        </template>
      </Checkbox>
    </template>
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
    isStacked: Boolean,
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "selectallrows"
  ]
}
</lang-strings>

<style lang="scss">
.tui-dataTableSelectVisibleRowsCell {
  display: flex;

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

  &--stacked {
    margin: var(--gap-2) 0;
  }

  &__label {
    display: none;
    @include tui-font-heading-label-small();

    padding: 0 0 var(--gap-1) var(--gap-1);

    &--stacked {
      display: inline-block;
    }

    &--large {
      padding: var(--gap-1) 0 var(--gap-1) var(--gap-2);
    }
  }
}
</style>
