<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Rodney Cruden-Powell <rodney.cruden-powell@totaralearning.com>
  @module tui
-->

<template>
  <Button
    class="tui-progressTrackerButton"
    :class="{
      'tui-progressTrackerButton--selected': selected,
    }"
    :aria-current="selected ? 'location' : null"
    :aria-label="text"
    :text="text"
    :styleclass="{
      transparent: true,
    }"
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
    disabled: Boolean,

    selected: Boolean,

    text: String,
  },
};
</script>

<style lang="scss">
.tui-progressTrackerButton {
  width: 100%;
  padding: var(--gap-2);
  color: var(--color-state);
  line-height: var(--font-body-line-height);
  text-align: left;
  @include tui-wordbreak--hard();

  &:hover,
  &:focus {
    color: var(--color-state);
    background: var(--color-state-highlight-neutral);
  }

  &--selected {
    color: var(--color-neutral-1);
    background: var(--color-state-active);
    border-color: var(--color-neutral-3);

    &:hover,
    &:focus,
    &:focus:hover {
      color: var(--color-neutral-1);
      background: var(--color-state-active);
      border-color: var(--color-neutral-3);
    }
  }
}
</style>
