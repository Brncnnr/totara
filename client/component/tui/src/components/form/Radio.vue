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
  <div class="tui-radio">
    <input
      :id="id"
      class="tui-radio__input"
      type="radio"
      :aria-describedby="ariaDescribedby"
      :aria-label="ariaLabel"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :checked="checked"
      :disabled="disabled"
      :name="name"
      :readonly="readonly"
      :required="required"
      :value="value"
      @change="handleChange"
      @blur="$emit('blur', $event)"
    />
    <label class="tui-radio__label" :for="id">
      <slot />
    </label>
  </div>
</template>

<script>
export default {
  props: {
    autocomplete: Boolean,
    autofocus: Boolean,
    ariaDescribedby: String,
    ariaLabel: String,
    checked: Boolean,
    disabled: Boolean,
    id: {
      type: String,
      default() {
        return this.uid;
      },
    },
    name: {
      type: String,
      required: true,
    },
    readonly: Boolean,
    required: Boolean,
    // eslint-disable-next-line vue/require-prop-types
    value: {
      required: true,
    },
  },

  methods: {
    handleChange() {
      this.$emit('select', this.value);
    },
  },
};
</script>

<style lang="scss">
:root {
  // Size of radio circle
  --form-radio-size: var(--font-size-16);
  // Size of radio inner dot
  --form-radio-dot-size: calc(var(--form-radio-size) / 2);
  // Offset to center dot
  --radio-dot-offset: calc(var(--form-radio-size) / 4);
}

.tui-radio {
  position: relative;
  display: inline-flex;

  &__input {
    position: absolute;
    opacity: 0;
  }

  &__label {
    position: relative;
    margin: 0;
    padding-left: var(--radio-label-offset);
    font-weight: normal;
    font-size: var(--form-input-font-size);
    line-height: 1;

    &::before {
      position: absolute;
      top: calc(50% - var(--form-radio-size) / 2);
      left: 0;
      display: block;
      width: var(--form-radio-size);
      height: var(--form-radio-size);
      background: var(--form-radio-bg-color);
      border: var(--form-input-border-size) solid;
      border-color: var(--form-radio-border-color);
      border-radius: 50%;
      transition: border var(--transition-form-function)
          var(--transition-form-duration),
        box-shadow var(--transition-form-function)
          var(--transition-form-duration);
      content: '';
      pointer-events: none;

      .tui-contextInvalid & {
        border-color: var(--form-input-border-color-invalid);
        box-shadow: var(--shadow-none), var(--form-input-shadow-invalid);
      }
    }
  }

  &__input:disabled ~ &__label {
    color: var(--form-input-text-color-disabled);
  }

  &__input:hover ~ &__label::before {
    background: var(--form-radio-bg-color-hover);
  }

  &__input:focus ~ &__label::before {
    background: var(--form-radio-bg-color-focus);
  }

  &__input:hover ~ &__label::before,
  &__input:focus ~ &__label::before {
    border: var(--form-input-border-size) solid;
    border-color: var(--form-radio-border-color-focus);
    box-shadow: var(--form-input-shadow-focus);

    .tui-contextInvalid & {
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--shadow-none), var(--form-input-shadow-invalid-focus);
    }
  }

  &__input:active:focus ~ &__label::before,
  &__input:active:hover ~ &__label::before,
  &__input:active ~ &__label::before {
    background: var(--form-radio-bg-color-active);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-radio-border-color-active);
    box-shadow: var(--form-input-shadow-focus);

    .tui-contextInvalid & {
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--shadow-none), var(--form-input-shadow-invalid-focus);
    }
  }

  &__input:disabled:active ~ &__label::before,
  &__input:disabled ~ &__label::before {
    background: var(--form-radio-bg-color-disabled);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-radio-border-color-disabled);
    box-shadow: none;
  }

  &__input ~ &__label::after {
    position: absolute;
    top: calc(50% - var(--form-radio-dot-size) / 2);
    left: var(--radio-dot-offset);
    display: block;
    width: var(--form-radio-dot-size);
    height: var(--form-radio-dot-size);
    background-color: var(--form-radio-dot-color);
    border-radius: 50%;
    opacity: 0;
    transition: opacity var(--transition-form-function)
      var(--transition-form-duration);
    content: '';
    pointer-events: none;
  }

  &__input:disabled ~ &__label::after {
    background-color: var(--form-input-text-color-disabled);
  }

  &__input:checked ~ &__label::after {
    opacity: 1;
  }
}

@media print {
  .tui-radio {
    &__input ~ &__label::after {
      -webkit-print-color-adjust: exact;
      color-adjust: exact;
    }
  }

  // IE11 & Edge support
  .ie,
  .msedge {
    .tui-radio {
      &__input ~ .tui-radio__label::after {
        border: 4px solid var(--form-radio-dot-color);
      }
    }
  }
}
</style>
