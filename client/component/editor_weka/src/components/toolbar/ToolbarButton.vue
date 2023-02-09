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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <Button
    class="tui-wekaToolbarButton"
    :class="{
      'tui-wekaToolbarButton--selected': selected,
    }"
    :aria-label="text"
    :aria-pressed="ariaPressed"
    :text="text"
    :caret="caret"
    :disabled="disabled"
    @click="$emit('click', $event)"
  />
</template>

<script>
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Button,
  },

  props: {
    text: {
      type: String,
      required: true,
    },

    // null = not selectable
    selected: {
      type: Boolean,
      default: null,
    },

    caret: Boolean,

    disabled: Boolean,
  },

  computed: {
    ariaPressed() {
      if (this.selected == null) {
        return null; // unset - not pressable
      }
      return this.selected ? 'true' : 'false';
    },
  },
};
</script>

<style lang="scss">
.tui-wekaToolbarButton {
  min-width: 0;
  height: var(--gap-8);
  min-height: var(--gap-6);
  padding: 0 var(--gap-2);
  color: var(--color-text);
  font-size: var(--font-size-14);
  line-height: 1;
  background: transparent;
  border: none;
  border-radius: 0;
  transition: none;

  &:focus,
  &:hover,
  &:active,
  &:active:hover,
  &:active:focus {
    color: var(--color-text);
    background: var(--color-neutral-4);
    border: none;
    box-shadow: none;
  }

  &:focus {
    @include tui-focus();
  }

  &:disabled {
    color: var(--color-state-disabled);
    background: transparent;
    opacity: 1;

    &:active,
    &:focus,
    &:active:focus,
    &:active:hover,
    &:hover {
      color: var(--color-state-disabled);
      background: transparent;
      box-shadow: none;
    }
  }

  &--selected {
    color: var(--color-neutral-1);
    background: var(--color-state-active);

    &:focus,
    &:hover,
    &:active,
    &:active:hover,
    &:active:focus {
      color: var(--color-neutral-1);
      background: var(--color-state-active);
    }
  }
}
</style>
