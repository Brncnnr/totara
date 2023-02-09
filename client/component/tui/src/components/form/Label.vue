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
  <component
    :is="legend ? 'legend' : 'label'"
    :id="id"
    :for="forId"
    class="tui-formLabel"
    :class="[
      hidden && 'tui-formLabel--hidden',
      inline && 'tui-formLabel--inline',
      subfield && 'tui-formLabel--subfield',
      charLength ? 'tui-inputLabel--charLength-' + charLength : null,
      charLength ? 'tui-input--customSize' : null,
    ]"
  >
    <span v-if="accessibleLabel" class="tui-formLabel--hidden">
      {{ accessibleLabel }}
      <span v-if="required">{{ $str('required', 'core') }}</span>
      <span v-if="optional">{{ $str('optional', 'core') }}</span>
    </span>

    {{ label }}
    <span
      v-if="required && !accessibleLabel"
      class="tui-formLabel__required"
      :title="$str('required', 'core')"
    >
      <span aria-hidden="true">*</span>
      <span class="sr-only">{{ $str('required', 'core') }}</span>
    </span>
  </component>
</template>

<script>
import { charLengthProp } from './form_common';

export default {
  props: {
    id: String,
    forId: String,
    legend: Boolean,
    hidden: Boolean,
    label: {
      required: true,
      type: String,
    },
    required: Boolean,
    optional: Boolean,
    subfield: Boolean,
    inline: Boolean,
    charLength: charLengthProp,
    accessibleLabel: String,
  },
};
</script>

<lang-strings>
{
  "core": [
    "optional",
    "required"
  ]
}
</lang-strings>

<style lang="scss">
.tui-formLabel {
  @include tui-font-heading-label();
  min-width: 0;
  margin: 0;
  padding: 0 var(--gap-1) 0 0;

  legend& {
    width: auto;
    margin: 0;
    padding: 0 var(--gap-1) 0 0;
    color: inherit;
    border: none;
  }

  &--inline {
    display: inline;
  }

  &--hidden {
    @include sr-only();
  }

  &--subfield {
    @include tui-font-body();
  }

  &__required {
    color: var(--color-prompt-alert);
  }
}
</style>
