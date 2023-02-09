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
  <form
    class="tui-form"
    :class="[
      !vertical && 'tui-form--horizontal',
      vertical && 'tui-form--vertical',
      'tui-form--inputWidth-' + inputWidth,
    ]"
    :autocomplete="autocomplete"
    @submit="handleSubmit"
  >
    <slot />
  </form>
</template>

<script>
export default {
  props: {
    vertical: Boolean,

    // default input size
    // full - take up entire available width
    // limited - by default, equivalent to char-length="20"
    inputWidth: {
      type: String,
      validator: x => ['full', 'limited'].includes(x),
      default: 'limited',
    },

    nativeSubmit: Boolean,

    autocomplete: {
      type: String,
      default: 'off',
    },
  },

  methods: {
    handleSubmit(e) {
      // prevent default action unless nativeSubmit prop is passed,
      // or method/action attributes are set
      if (!this.nativeSubmit && !this.$attrs.method && !this.$attrs.action) {
        e.preventDefault();
      }

      this.$emit('submit', e);
    },
  },
};
</script>

<style lang="scss">
.tui-form {
  @include tui-stack-vertical(var(--gap-4));
}
</style>
