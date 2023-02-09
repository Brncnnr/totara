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
  <button
    ref="button"
    class="tui-toggleBtn"
    :class="{
      'tui-toggleBtn--selected': selected,
      'tui-toggleBtn--hasText': text && !large,
      'tui-toggleBtn--large': large,
    }"
    :aria-checked="selected.toString()"
    :aria-label="ariaLabel"
    :disabled="disabled"
    :name="name"
    role="radio"
    :tabindex="selected ? null : -1"
    type="button"
    :value="value"
    @click="toggleState"
    @keydown="$emit('keydown', $event)"
  >
    <div class="tui-toggleBtn__content" aria-hidden="true">
      <span v-if="$scopedSlots.default" class="tui-toggleBtn__icon">
        <slot />
      </span>
      <span v-if="text" class="tui-toggleBtn__text">
        {{ text }}
      </span>
    </div>
  </button>
</template>

<script>
export default {
  props: {
    ariaLabel: {
      type: String,
      required: true,
    },
    disabled: Boolean,
    large: Boolean,
    name: String,
    selected: {
      type: Boolean,
      default: false,
    },
    text: String,
    title: String,
    value: [Boolean, String],
  },

  methods: {
    /**
     * Set focus to the button on click (doesn't happen on Firefox on OSX)
     */
    setFocus() {
      this.$refs.button.focus();
    },

    /**
     * Emit button click event
     */
    toggleState() {
      // If already selected, don't emit clicked event
      if (this.selected) {
        return;
      }
      this.setFocus();
      this.$emit('clicked', this.value);
    },
  },
};
</script>

<style lang="scss">
.tui-toggleBtn {
  position: relative;
  display: inline-block;
  flex-shrink: 0;
  min-width: var(--gap-12);
  max-width: 100%;
  height: var(--form-input-height);
  padding: 0 var(--gap-2);
  color: var(--toggle-btn-text-color);
  font-size: var(--form-input-font-size);
  line-height: 1.2;
  background: var(--toggle-btn-bg-color);
  border: none;
  border-radius: calc(var(--btn-radius) - 1px);

  &:focus,
  &:hover {
    color: var(--toggle-btn-text-color-focus);
    text-decoration: none;
    background: var(--toggle-btn-bg-color-focus);
    box-shadow: none;
  }

  &:active,
  &:active:focus,
  &:active:hover {
    color: var(--toggle-btn-text-color-active);
    text-decoration: none;
    background: var(--toggle-btn-bg-color-active);
    border: none;
    box-shadow: none;
  }

  &:active:focus,
  &:focus {
    outline: 1px dashed var(--toggle-btn-text-color-active);
    outline-offset: -3px;
  }

  &[disabled] {
    color: var(--toggle-btn-text-color-disabled);
    background: var(--toggle-btn-bg-color-disabled);
    cursor: default;

    &:focus,
    &:hover,
    &:active,
    &:active:focus,
    &:active:hover {
      color: var(--toggle-btn-text-color-disabled);
      background: var(--toggle-btn-bg-color-disabled);
    }
  }

  &--selected {
    color: var(--toggle-btn-text-color-selected);
    background: var(--toggle-btn-bg-color-selected);
    cursor: default;

    &:focus,
    &:hover,
    &:active,
    &:active:focus,
    &:active:hover {
      color: var(--toggle-btn-text-color-selected);
      background: var(--toggle-btn-bg-color-selected);
    }

    &:active:focus,
    &:focus {
      outline: 1px dashed var(--toggle-btn-text-color-selected);
      outline-offset: -3px;
    }
  }

  &[disabled]&--selected {
    color: var(--toggle-btn-text-color-selected);
    background: var(--toggle-btn-bg-color-selected);

    &:focus,
    &:hover,
    &:active,
    &:active:focus,
    &:active:hover {
      color: var(--toggle-btn-text-color-selected);
      background: var(--toggle-btn-bg-color-selected);
    }
  }

  &--hasText {
    min-width: 70px;

    .tui-toggleBtn__icon {
      font-size: var(--font-size-14);
    }
  }

  &--large {
    min-width: 10rem;
    min-height: 4.6rem;

    .tui-toggleBtn__content {
      flex-direction: column;

      & > * + * {
        margin-top: var(--gap-1);
      }
    }
  }

  &__content {
    display: inline-flex;
    align-items: center;
  }

  &__icon {
    position: relative;
    top: 1px;
    display: flex;
    flex-shrink: 0;
    font-size: var(--font-size-15);
  }

  &__text {
    padding: 0 var(--gap-1);
  }
}
</style>
