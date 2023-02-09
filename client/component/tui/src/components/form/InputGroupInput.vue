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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module tui
-->

<template>
  <input
    class="tui-inputGroupInput"
    :class="[
      charLength ? 'tui-formInput--charLength-' + charLength : null,
      charLength ? 'tui-input--customSize' : null,
      monospace ? 'tui-inputGroupInput--monospaceFont' : null,
    ]"
    :disabled="disabled"
    :value="value"
    @input="$emit('input', $event.target.value)"
    @blur="$emit('input-blur')"
    @focus="$emit('input-focus')"
    @keydown.enter="$emit('submit', $event.target.value)"
  />
</template>

<script>
import { charLengthProp } from './form_common';

export default {
  props: {
    charLength: charLengthProp,
    monospace: {
      type: Boolean,
      default: false,
    },
    disabled: Boolean,
    autofocus: Boolean,
    value: [Number, String],
  },

  mounted() {
    if (this.autofocus && this.$el) {
      // Make the input element to be focused, when the prop autofocus is set.
      // We are moving away from the native attribute for element, because
      // different browser will treat autofocus different. Furthermore,
      // the slow performing browser will not make the element focused due
      // to the element is not rendered on time.
      this.$el.focus();
    }
  },
};
</script>

<style lang="scss">
// Reset
input[type].tui-inputGroupInput {
  display: inline-block;
  width: auto;
  max-width: none;
  height: auto;
  max-height: none;
  margin: 0;
  padding: 1px;
  color: rgb(0, 0, 0);
  font-size: inherit;
  line-height: inherit;
  letter-spacing: normal;
  text-align: start;
  text-transform: none;
  text-indent: 0;
  text-shadow: none;
  word-spacing: normal;
  background-color: rgb(255, 255, 255);
  border-color: rgb(218, 218, 218);
  border-style: inset;
  border-width: 2px;
  border-radius: 0;
  border-image-source: none;
  border-image-slice: 100%;
  border-image-width: 1;
  border-image-outset: 0;
  border-image-repeat: stretch;
  border-spacing: 0;
  box-shadow: none;
  cursor: text;
  transition-delay: 0s;
  transition-timing-function: ease;
  transition-duration: 0s;
  transition-property: all;
  text-rendering: auto;

  &[disabled] {
    color: rgb(61, 68, 75);
    background: rgb(218, 218, 218);
    cursor: default;
  }

  &:focus {
    border-color: rgb(218, 218, 218);
    outline-width: 3px;
    outline-style: auto;
    outline-color: Highlight;
    outline-color: -webkit-focus-ring-color;
    outline-offset: -2px;
    box-shadow: none;
    -moz-user-focus: normal;
  }

  &::placeholder {
    color: #a9a9a9;
    opacity: 1;
  }
}

input[type].tui-inputGroupInput {
  display: block;
  flex-grow: 1;
  box-sizing: border-box;
  min-width: 0;
  padding: 0 var(--gap-2);
  color: var(--form-input-text-color);
  font-size: var(--form-input-font-size);
  line-height: 1;
  background: transparent;
  border: none;

  @include tui-char-length-classes();

  &::placeholder {
    color: var(--form-input-text-placeholder-color);
  }

  &:focus {
    background-color: transparent;
    border: none;
    outline: none;
    box-shadow: none;
  }

  &[disabled] {
    color: var(--form-input-text-color-disabled);
    background: transparent;
    &::placeholder {
      color: var(--form-input-text-color-disabled);
    }
  }

  &[readonly] {
    background: transparent;
  }

  &--monospaceFont {
    @include tui-font-monospace();
  }
}
</style>
