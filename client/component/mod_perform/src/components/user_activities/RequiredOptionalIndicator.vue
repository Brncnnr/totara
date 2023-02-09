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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <span class="tui-performRequiredOptionalIndicator">
    <span
      v-if="isRequired"
      class="tui-performRequiredOptionalIndicator--required"
      :title="$str('required', 'core')"
    >
      <span aria-hidden="true">*</span>
      <span class="sr-only">{{ $str('required', 'core') }}</span>
    </span>

    <span
      v-if="!isRequired"
      class="tui-performRequiredOptionalIndicator--optional"
    >
      ({{ $str('section_element_response_optional', 'mod_perform') }})
    </span>
  </span>
</template>
<script>
export default {
  props: {
    isRequired: Boolean,
  },
};
</script>

<lang-strings>
{
  "core": [
    "required"
  ],
  "mod_perform": [
    "section_element_response_optional"
  ]
}
</lang-strings>

<style lang="scss">
.tui-performRequiredOptionalIndicator {
  &--required {
    @include tui-font-heading-label();
    color: var(--color-prompt-alert);
  }
  &--optional {
    @include tui-font-body();
    @include tui-font-hint();
  }
}
</style>
