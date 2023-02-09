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
  @module totara_engage
-->

<template>
  <div
    v-focus-within
    class="tui-inlineEditing"
    :class="{
      'tui-inlineEditing--updateAble': updateAble,
      'tui-inlineEditing--fullWidth': fullWidth,
    }"
    @click="handleClick"
  >
    <slot name="content" />

    <ButtonIcon
      v-show="updateAble"
      ref="inlineEditingBtn"
      :aria-label="buttonAriaLabel"
      :autofocus="focusButton"
      :styleclass="{ transparent: true, small: true }"
      class="tui-inlineEditing__btn"
    >
      <EditIcon size="200" />
    </ButtonIcon>
  </div>
</template>

<script>
import EditIcon from 'tui/components/icons/Edit';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    EditIcon,
    ButtonIcon,
  },

  props: {
    buttonAriaLabel: {
      type: String,
      default() {
        return this.$str('edit', 'core');
      },
    },
    fullWidth: Boolean,
    restrictedMode: Boolean,
    /**
     * A flag to tell whether this component should turn the auto focus on the button or not.
     */
    focusButton: Boolean,
    updateAble: {
      type: Boolean,
      required: true,
    },
  },

  methods: {
    handleClick(event) {
      if (!this.updateAble) {
        return;
      }

      if (this.restrictedMode) {
        // No event triggering when click inside elements of InlineEditing, unless it's the edit button

        if (
          event.target !== event.currentTarget &&
          event.target !== this.$refs['inlineEditingBtn'].$el
        ) {
          return;
        }
      }

      this.$emit('click');
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "edit"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-inlineEditing {
  position: relative;
  display: inline-flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: var(--gap-1) 0;
  border: 2px solid transparent;

  &--fullWidth {
    width: 100%;
  }

  &--updateAble {
    padding-right: var(--gap-5);
    padding-left: var(--gap-1);

    &:after {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      content: '';
    }

    &:hover,
    &:focus-within,
    &.tui-focusWithin {
      border: 2px solid var(--color-secondary);
      cursor: pointer;
      & .tui-inlineEditing__btn {
        flex-grow: 0;
        flex-shrink: 0;
        width: 1.5rem;
        outline: none;
      }
    }
  }

  &:not(:hover) {
    .tui-inlineEditing__btn:not(:focus) {
      @include sr-only();
    }
  }
}
</style>
