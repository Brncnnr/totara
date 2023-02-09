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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module tui
-->

<template>
  <ButtonAria>
    <div
      v-if="disabled"
      class="tui-inputGroupButton"
      disabled
      @click="$emit('click', $event)"
    >
      {{ text }}
    </div>
    <div v-else class="tui-inputGroupButton" @click="$emit('click', $event)">
      {{ text }}
    </div>
  </ButtonAria>
</template>

<script>
import ButtonAria from 'tui/components/buttons/ButtonAria';

export default {
  components: {
    ButtonAria,
  },

  props: {
    text: String,
    disabled: Boolean,
  },
};
</script>

<style lang="scss">
.tui-inputGroupButton {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  padding: 0 var(--gap-2);
  color: var(--color-state);
  font-size: var(--form-input-font-size);
  cursor: pointer;
  user-select: none;

  &:focus {
    color: var(--color-state-focus);
    @include tui-focus();
  }

  &:hover {
    color: var(--color-state-hover);
  }

  &:active {
    color: var(--color-state-active);
  }

  &[disabled] {
    color: var(--color-state-disabled);
    cursor: default;
  }
}
</style>
