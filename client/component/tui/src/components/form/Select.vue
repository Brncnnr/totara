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
  @module tui
-->

<template>
  <div
    class="tui-select"
    :class="[
      disabled && 'tui-select--disabled',
      large && 'tui-select--large',
      multiple && 'tui-select--multiple',
      charLength ? 'tui-select--charLength-' + charLength : null,
      charLength ? 'tui-input--customSize' : null,
    ]"
  >
    <select
      :id="id"
      v-model="selectedValue"
      class="tui-select__input"
      :aria-describedby="ariaDescribedby"
      :aria-invalid="ariaInvalid"
      :aria-label="ariaLabel"
      :aria-labelledby="ariaLabelledby"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :disabled="disabled"
      :multiple="multiple"
      :name="name"
      :required="required"
      :size="size"
      @blur="$emit('blur', $event)"
    >
      <template v-for="(option, i) in normalizedOptions">
        <optgroup v-if="option.options" :key="i" :label="option.label">
          <option
            v-for="(suboption, j) in option.options"
            :key="j"
            :value="suboption.id"
            :disabled="suboption.disabled"
          >
            {{ suboption.label }}
          </option>
        </optgroup>
        <option v-else :key="i" :value="option.id" :disabled="option.disabled">
          {{ option.label }}
        </option>
      </template>
    </select>
  </div>
</template>

<script>
import { charLengthProp } from './form_common';

export default {
  inheritAttrs: false,

  props: {
    id: String,
    ariaDescribedby: [String, Boolean],
    ariaInvalid: [String, Boolean],
    ariaLabel: [String, Boolean],
    ariaLabelledby: [String, Boolean],
    autocomplete: String,
    autofocus: Boolean,
    disabled: Boolean,
    charLength: charLengthProp,
    large: Boolean,
    multiple: Boolean,
    name: String,
    options: {
      type: [Array, Object],
      required: true,
    },
    required: Boolean,
    size: [Number, String],
    // eslint-disable-next-line vue/require-prop-types
    value: {},
  },

  computed: {
    normalizedOptions() {
      return this.options.map(this.$_normalizeOption);
    },

    selectedValue: {
      get() {
        return this.value;
      },

      set(value) {
        this.$emit('input', value);
      },
    },
  },

  methods: {
    $_normalizeOption(option) {
      if (typeof option === 'string') {
        option = { label: option, id: option };
      }
      if (option.options) {
        option = Object.assign({}, option, {
          options: option.options.map(this.$_normalizeOption),
        });
      }
      return option;
    },
  },
};
</script>

<style lang="scss">
:root {
  --select-icon-size: var(--gap-1);
}

// Reset
.tui-select__input {
  display: inline-block;
  width: auto;
  max-width: none;
  height: auto;
  max-height: none;
  margin: 0;
  padding: 0;
  color: black;
  font: 400 13.3333px Arial;
  font-size: inherit;
  line-height: inherit;
  letter-spacing: normal;
  white-space: pre;
  text-align: start;
  text-transform: none;
  text-indent: 0;
  text-shadow: none;
  word-spacing: normal;
  background-color: white;
  border-color: rgb(169, 169, 169);
  border-style: solid;
  border-width: 1px;
  border-radius: 0;
  border-image: initial;
  box-shadow: none;
  cursor: default;
  transition-delay: 0s;
  transition-timing-function: ease;
  transition-duration: 0s;
  transition-property: all;
  text-rendering: auto;
  appearance: menulist;

  &[disabled] {
    color: rgb(61, 68, 75);
    background: rgb(218, 218, 218);
    cursor: default;
  }

  &[multiple] {
    height: auto;
    overflow-x: hidden;
    overflow-y: visible;
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
}

.tui-select {
  position: relative;
  display: flex;
  flex-grow: 1;
  width: 100%;
  min-width: 0;
  height: var(--form-input-height);

  @include tui-char-length-classes();

  &::after {
    position: absolute;
    top: calc((var(--form-input-height) - var(--select-icon-size)) / 2);
    right: calc((var(--form-input-height) - var(--select-icon-size) * 2) / 2);
    display: block;
    width: 0;
    height: 0;
    border: var(--select-icon-size) solid transparent;
    border-top-color: var(--form-input-text-color);
    content: '';
    pointer-events: none;
  }

  &--disabled::after {
    border-top-color: var(--form-input-text-color-disabled);
  }

  &--multiple::after {
    display: none;
  }

  &--large {
    height: var(--form-input-height-large);

    &::after {
      top: calc((var(--form-input-height-large) - var(--select-icon-size)) / 2);
      // prettier-ignore
      right: calc((var(--form-input-height-large) - var(--select-icon-size) * 2) / 2);
    }
  }

  &__input {
    flex-grow: 1;
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
    padding: 0 var(--gap-6) 0 var(--gap-1);
    color: var(--form-input-text-color);
    font-size: var(--form-input-font-size);
    background: var(--form-input-bg-color);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-input-border-color);
    appearance: none;

    &[multiple] {
      height: auto;
    }

    &[disabled] {
      color: var(--form-input-text-color-disabled);
      background: var(--form-input-bg-color-disabled);
      border-color: var(--form-input-border-color-disabled);
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

    // Drop select outline
    &:-moz-focusring {
      color: transparent;
      text-shadow: 0 0 0 #000;
    }

    // appearance: none; equivalent for IE
    &::-ms-expand {
      display: none;
    }

    // prevent weird styling after selecting value
    &::-ms-value {
      color: inherit;
      background-color: transparent;
    }

    .tui-contextInvalid & {
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--form-input-shadow-invalid);
    }
  }
}
</style>
