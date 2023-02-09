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
    class="tui-checkbox"
    :class="{
      'tui-checkbox--large': large,
    }"
  >
    <input
      :id="id"
      class="tui-checkbox__input"
      type="checkbox"
      :aria-describedby="ariaDescribedby"
      :aria-label="ariaLabel"
      :aria-labelledby="ariaLabelledby"
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
    <label
      class="tui-checkbox__label"
      :class="{
        'tui-checkbox__label--noOffset': noLabelOffset,
      }"
      :for="id"
    >
      <slot />
    </label>
  </div>
</template>

<script>
export default {
  model: {
    prop: 'checked',
    event: 'change',
  },

  props: {
    ariaDescribedby: String,
    ariaLabel: String,
    ariaLabelledby: String,
    autocomplete: Boolean,
    autofocus: Boolean,
    checked: Boolean,
    disabled: Boolean,
    id: {
      type: String,
      default() {
        return this.uid;
      },
    },
    large: Boolean,
    name: String,
    noLabelOffset: Boolean,
    readonly: Boolean,
    required: Boolean,
    value: String,
  },

  methods: {
    handleChange(e) {
      this.$emit('change', e.target.checked);
    },
  },
};
</script>

<style lang="scss">
:root {
  // Size of checkbox
  --form-checkbox-size: var(--form-input-font-size);
  --form-checkbox-size-large: calc(var(--form-input-font-size) * 1.333);
  --checkbox-check-width: 0.2rem;
}

.tui-checkbox {
  position: relative;
  display: flex;
  min-height: calc(var(--form-checkbox-size) + 2px);

  &--large {
    min-height: calc(var(--form-checkbox-size-large) + 2px);
  }

  &__input {
    position: absolute;
    opacity: 0;
  }

  &__label {
    position: relative;
    margin: 0;
    padding-left: calc(var(--form-checkbox-size) * 1.5);
    font-weight: normal;
    font-size: var(--form-input-font-size);
    line-height: 1;

    .tui-checkbox--large & {
      padding-left: calc(var(--form-checkbox-size-large) * 1.5);
    }

    &--noOffset {
      padding-left: var(--form-checkbox-size);
      .tui-checkbox--large & {
        padding-left: var(--form-checkbox-size-large);
      }
    }

    &::before {
      position: absolute;
      top: 0;
      left: 0;
      display: block;
      width: var(--form-checkbox-size);
      height: var(--form-checkbox-size);
      margin-top: 1px;
      background: var(--form-checkbox-bg-color);
      border: var(--form-input-border-size) solid;
      border-color: var(--form-checkbox-border-color);
      transition: border var(--transition-form-function)
          var(--transition-form-duration),
        box-shadow var(--transition-form-function)
          var(--transition-form-duration);
      content: '';
      pointer-events: none;

      .tui-checkbox--large & {
        width: var(--form-checkbox-size-large);
        height: var(--form-checkbox-size-large);
      }

      .tui-contextInvalid & {
        border-color: var(--form-input-border-color-invalid);
        box-shadow: var(--shadow-none), var(--form-input-shadow-invalid);
      }
    }
  }

  &__input:disabled ~ &__label {
    color: var(--form-input-text-color-disabled);
  }

  &__input:checked:hover ~ &__label::before,
  &__input:hover ~ &__label::before,
  &__input:focus ~ &__label::before {
    border: var(--form-input-border-size) solid;
    border-color: var(--form-checkbox-border-color-focus);
    box-shadow: var(--form-input-shadow-focus);

    .tui-contextInvalid & {
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--shadow-none), var(--form-input-shadow-invalid-focus);
    }
  }

  &__input:focus ~ &__label::before {
    background: var(--form-checkbox-bg-color-focus);
  }

  &__input:disabled:checked ~ &__label::before,
  &__input:disabled ~ &__label::before {
    background: var(--form-checkbox-bg-color-disabled);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-checkbox-border-color-disabled);
    box-shadow: none;
  }

  &__input ~ &__label::after {
    // construct a check mark out of two sides of a rotated box
    position: absolute;
    top: calc(0.267 * var(--form-checkbox-size));
    left: calc(0.133 * var(--form-checkbox-size));
    display: block;
    width: calc(0.733 * var(--form-checkbox-size));
    height: calc(0.4 * var(--form-checkbox-size));
    border-color: var(--form-checkbox-check-color);
    border-style: solid;
    /*!rtl:ignore*/
    border-width: 0 0 var(--checkbox-check-width) var(--checkbox-check-width);
    transform: rotate(-45deg);
    opacity: 0;
    transition: opacity var(--transition-form-function)
      var(--transition-form-duration);
    content: '';
    pointer-events: none;

    .tui-checkbox--large & {
      top: calc(0.267 * var(--form-checkbox-size-large));
      left: calc(0.133 * var(--form-checkbox-size-large));
      width: calc(0.733 * var(--form-checkbox-size-large));
      height: calc(0.4 * var(--form-checkbox-size-large));
    }
  }

  &__input:disabled:checked ~ &__label::after,
  &__input:disabled ~ &__label::after {
    border-color: var(--form-checkbox-check-color-disabled);
  }

  &__input:checked ~ &__label::before {
    background: var(--form-checkbox-bg-color-active);
  }

  &__input:checked ~ &__label::after {
    opacity: 1;
  }
}

@media print {
  .tui-checkbox {
    &__input:checked ~ &__label::before {
      -webkit-print-color-adjust: exact;
      color-adjust: exact;
    }
  }

  // IE11 & Edge support
  .ie,
  .msedge {
    .tui-checkbox {
      &__input:checked ~ .tui-checkbox__label::before {
        border: solid var(--form-checkbox-bg-color-active);
        border-width: 0 var(--form-checkbox-size) 0 0;
      }
    }
  }
}
</style>
