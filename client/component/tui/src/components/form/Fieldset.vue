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
  <fieldset class="tui-fieldset">
    <legend
      class="tui-fieldset__legend"
      :class="{ 'tui-fieldset--hidden': hidden }"
    >
      {{ legend }}
    </legend>
    <slot />
  </fieldset>
</template>

<script>
export default {
  props: {
    hidden: Boolean,
    legend: {
      type: String,
    },
  },
};
</script>

<style lang="scss">
.tui-fieldset {
  min-width: 0;
  margin: 0;
  padding: 0;
  border: 0;

  &__legend {
    display: block;
    width: 100%;
    margin: 0;
    padding: 0;
    color: var(--form-text-color);
    font-size: var(--font-size-18);
    line-height: inherit;
    border: none;
  }

  &--hidden {
    @include sr-only();
  }
}
</style>
