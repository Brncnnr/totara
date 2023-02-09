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
  <input
    :id="id"
    class="tui-formInput"
    :aria-describedby="ariaDescribedby"
    :aria-invalid="ariaInvalid"
    :aria-label="ariaLabel"
    :aria-labelledby="ariaLabelledby"
    :autocomplete="autocomplete"
    :class="[
      styleclass.preIcon ? 'tui-formInput--preIcon' : null,
      styleclass.postIcon ? 'tui-formInput--postIcon' : null,
      styleclass.transparent ? 'tui-formInput--transparent' : null,
      charLength ? 'tui-formInput--charLength-' + charLength : null,
      charLength ? 'tui-input--customSize' : null,
    ]"
    :dir="dir"
    :disabled="disabled"
    :list="list"
    :max="max"
    :maxlength="maxlength"
    :min="min"
    :minlength="minlength"
    :multiple="multiple"
    :name="name"
    :pattern="pattern"
    :placeholder="placeholder"
    :readonly="readonly"
    :required="required"
    :size="size"
    :spellcheck="spellcheck"
    :step="step"
    :type="type"
    :value="value"
    @input="$emit('input', $event.target.value)"
    @blur="$emit('blur')"
    @keydown.enter="$emit('submit', $event.target.value)"
  />
</template>

<script>
import { charLengthProp } from './form_common';

export default {
  props: {
    ariaDescribedby: [String, Boolean],
    ariaInvalid: [String, Boolean],
    ariaLabel: [String, Boolean],
    ariaLabelledby: [String, Boolean],
    autocomplete: String,
    autofocus: Boolean,
    dir: {
      type: String,
      validator: x => ['auto', 'ltr', 'rtl', null].includes(x),
    },
    disabled: Boolean,
    id: String,
    charLength: charLengthProp,
    list: String,
    max: Number,
    maxlength: Number,
    min: Number,
    minlength: Number,
    multiple: String,
    name: String,
    pattern: String,
    placeholder: String,
    readonly: Boolean,
    required: Boolean,
    size: Number,
    spellcheck: Boolean,
    step: Number,
    styleclass: {
      default: () => ({
        preIcon: false,
        postIcon: false,
        transparent: false,
      }),
      type: Object,
    },
    type: {
      required: true,
      type: String,
      validator: function(value) {
        const allowedOptions = [
          'email',
          'hidden',
          'number',
          'password',
          'search',
          'tel',
          'text',
          'time',
          'url',
          'week',
          'color',
        ];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
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
input[type].tui-formInput {
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

input[type].tui-formInput {
  display: block;
  flex-grow: 1;
  box-sizing: border-box;
  width: 100%;
  min-width: 0;
  height: var(--form-input-height);
  padding: var(--form-input-v-padding) var(--gap-2);
  color: var(--form-input-text-color);
  font-size: var(--form-input-font-size);
  line-height: 1;
  background: var(--form-input-bg-color);
  border: var(--form-input-border-size) solid;
  border-color: var(--form-input-border-color);

  @include tui-char-length-classes();

  &::placeholder {
    color: var(--form-input-text-placeholder-color);
  }

  .tui-contextInvalid & {
    border-color: var(--form-input-border-color-invalid);
    box-shadow: var(--form-input-shadow-invalid);
  }

  &:focus {
    background: var(--form-input-bg-color-focus);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-input-border-color-focus);
    outline: none;
    box-shadow: var(--form-input-shadow-focus);

    .tui-contextInvalid & {
      background: var(--form-input-bg-color-invalid-focus);
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--form-input-shadow-invalid-focus);
    }
  }

  &--preIcon {
    padding-left: var(--gap-6);
  }

  &--postIcon {
    padding-right: var(--gap-6);
  }

  &[readonly] {
    color: var(--form-input-text-color);
    background: var(--form-input-bg-color);
    border-color: var(--form-input-border-color);

    &::placeholder {
      color: var(--form-input-text-placeholder-color);
    }
  }

  &[disabled] {
    color: var(--form-input-text-color-disabled);
    background: var(--form-input-bg-color-disabled);
    border-color: var(--form-input-border-color-disabled);

    &::placeholder {
      color: var(--form-input-text-color-disabled);
    }
  }

  &--transparent,
  &--transparent:focus,
  &--transparent[readonly],
  &--transparent[disabled],
  .tui-contextInvalid &--transparent,
  .tui-contextInvalid &--transparent:focus {
    background-color: transparent;
    border: none;
    box-shadow: none;
  }
}
</style>
