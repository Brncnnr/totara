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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <ButtonAria>
    <div
      class="tui-editor_weka-nodeMenuButton"
      :class="{
        'tui-editor_weka-nodeMenuButton--iconOnly': iconOnly,
      }"
      @click="handleClick"
    >
      <div
        v-if="$scopedSlots.icon"
        class="tui-editor_weka-nodeMenuButton__icon"
      >
        <slot name="icon" />
      </div>
      {{ text }}
      <Caret v-if="caret" class="tui-editor_weka-nodeMenuButton__caret" />
    </div>
  </ButtonAria>
</template>

<script>
import ButtonAria from 'tui/components/buttons/ButtonAria';
import Caret from 'tui/components/decor/Caret';

export default {
  components: {
    ButtonAria,
    Caret,
  },

  props: {
    text: String,
    caret: Boolean,
  },

  computed: {
    iconOnly() {
      return this.$scopedSlots.icon && !this.text && !this.caret;
    },
  },

  methods: {
    handleClick(e) {
      this.$emit('click', e);
    },
  },
};
</script>

<style lang="scss">
.tui-editor_weka-nodeMenuButton {
  display: inline-flex;
  align-items: center;
  height: var(--weka-node-menu-button-height);
  padding: var(--gap-1) var(--gap-2);
  color: var(--color-neutral-7);
  background: var(--color-neutral-1);
  border-radius: 2px;
  cursor: pointer;
  transition: background-color var(--transition-button-duration)
    var(--transition-button-function);
  user-select: none;

  &:hover,
  &:active {
    background: var(--color-neutral-4);
  }

  &[aria-disabled='true'] {
    background: var(--color-neutral-1);
    cursor: default;
    opacity: 0.8;
  }

  &--iconOnly {
    width: var(--weka-node-menu-button-height);
  }

  &__icon {
    display: flex;
    margin-right: var(--gap-2);
  }

  &__caret {
    margin-left: var(--gap-2);
  }
}
</style>
