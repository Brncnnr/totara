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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_core
-->

<template>
  <button
    tabindex="-1"
    class="tui-dropdownButton"
    type="button"
    :class="{
      'tui-dropdownButton--disabled': disabled,
      'tui-dropdownItem--noPadding': noPadding,
    }"
    :disabled="disabled"
    :role="role"
    @click="clickDropdownButton"
  >
    <slot />
  </button>
</template>

<script>
export default {
  props: {
    disabled: Boolean,
    noPadding: Boolean,
    role: {
      type: String,
      default: 'menuitem',
    },
  },

  methods: {
    clickDropdownButton(e) {
      if (this.disabled) {
        e.stopPropagation();
        return;
      }

      this.$emit('click', e);
    },
  },
};
</script>

<style lang="scss">
// override <button> styles
.tui-dropdownButton {
  color: var(--dropdown-item-text-color);
  text-align: left;
  border: none;
  border-radius: 0;
  &:hover,
  &:focus,
  &:active,
  &:focus:active {
    border-color: transparent;
    box-shadow: none;
  }
}

// tui-dropdownButton styles
.tui-dropdownButton {
  @include tui-font-body();
  padding: var(--gap-2) var(--gap-4);
  overflow: hidden;
  line-height: 1.4;
  text-overflow: ellipsis;

  &:hover,
  &:focus,
  &:active,
  &:focus:active {
    color: var(--dropdown-item-text-color-hover);
    text-decoration: none;
    background-color: var(--dropdown-item-bg-color-hover);
    outline: 0;
  }

  &:focus,
  &:active,
  &:focus:active {
    color: var(--dropdown-item-text-color-focus);
    background-color: var(--dropdown-item-bg-color-focus);

    outline: 2px solid var(--dropdown-item-focus-ring-color);
    outline-offset: -3px;
  }

  &--disabled,
  &--disabled:hover,
  &--disabled:focus {
    color: var(--dropdown-item-text-color-disabled);
    background-color: transparent;
    cursor: not-allowed;
  }

  &--disabled:active {
    pointer-events: none;
  }

  &--noPadding {
    padding: 0;
  }
}
</style>
