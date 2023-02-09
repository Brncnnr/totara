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
  @module tui
-->

<template>
  <a
    :href="href || '#'"
    tabindex="-1"
    class="tui-dropdownItem"
    :class="{
      'tui-dropdownItem--disabled': disabled,
      'tui-dropdownItem--noPadding': noPadding,
      'tui-dropdownItem--selected': selected,
    }"
    :aria-disabled="disabled"
    :role="role"
    @click="clickItem"
  >
    <slot />
  </a>
</template>

<script>
export default {
  props: {
    role: {
      type: String,
      default: 'menuitem',
    },
    disabled: Boolean,
    href: String,
    noPadding: Boolean,
    selected: Boolean,
  },

  methods: {
    clickItem(e) {
      if (!this.href) {
        e.preventDefault();
        this.$emit('click', e);
        return;
      }

      if (this.disabled) {
        e.stopPropagation();
        e.preventDefault();
        return;
      }

      this.$emit('click', e);
    },
  },
};
</script>

<style lang="scss">
.tui-dropdownItem {
  @include tui-font-body();
  display: block;
  width: 100%;
  padding: var(--gap-2) var(--gap-4);
  overflow: hidden;
  color: var(--dropdown-item-text-color);
  line-height: 1.4;
  white-space: nowrap;
  text-overflow: ellipsis;
  cursor: pointer;

  &:hover,
  &:focus {
    color: var(--dropdown-item-text-color-hover);
    text-decoration: none;
    background-color: var(--dropdown-item-bg-color-hover);
  }

  &:focus {
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

  &--disabled:focus {
    background-color: var(--dropdown-item-bg-color-disabled-focus);
  }

  &--disabled:active {
    pointer-events: none;
  }

  &--noPadding {
    padding: 0;
  }

  &--selected {
    color: var(--color-neutral-1);
    background: var(--color-state-active);

    &:focus,
    &:hover,
    &:active {
      color: var(--color-neutral-1);
      background: var(--color-state-active);
    }

    &:focus {
      outline-color: var(--color-neutral-1);
    }
  }
}
</style>
