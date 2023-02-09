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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-range">
    <!-- Labels -->
    <div class="tui-range__labels">
      <div class="tui-range__lowLabel">{{ rangeLowLabel }}</div>
      <div class="tui-range__highLabel">{{ rangeHighLabel }}</div>
    </div>

    <!-- Slider -->
    <input
      :id="id"
      class="tui-range__input"
      :class="{
        'tui-range__input--selected': value,
        'tui-range__input--noThumb': noThumb,
      }"
      type="range"
      :aria-label="ariaLabel"
      :aria-labelledby="ariaLabelledby"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :disabled="disabled"
      :name="name"
      :readonly="readonly"
      :required="required"
      :value="value || defaultValue"
      :min="min"
      :max="max"
      :step="step"
      @input="handleChange"
      @change="handleChange"
      @click="handleChange"
    />
  </div>
</template>

<script>
export default {
  model: {
    prop: 'value',
    event: 'change',
  },

  props: {
    id: {
      type: String,
      default() {
        return this.uid;
      },
    },
    ariaLabel: String,
    ariaLabelledby: String,
    autocomplete: Boolean,
    autofocus: Boolean,
    disabled: Boolean,
    name: String,
    readonly: Boolean,
    required: Boolean,
    value: [Number, String],
    defaultValue: [Number, String],
    min: [Number, String],
    max: [Number, String],
    step: [Number, String],
    showLabels: Boolean,
    lowLabel: String,
    highLabel: String,
    noThumb: Boolean,
  },

  computed: {
    rangeLowLabel() {
      return this.showLabels ? this.lowLabel : this.min;
    },
    rangeHighLabel() {
      return this.showLabels ? this.highLabel : this.max;
    },
  },

  methods: {
    /**
     * Trigger an event notifying the parent of a change in the range's value.
     * Also caters for initial click events selecting the default value as this
     * does not execute the input/change events. Dragging the thumb around will
     * only emit the @input event.
     *
     * @param e
     */
    handleChange(e) {
      const value = e.target.value;
      if (value !== this.value) {
        this.$emit('change', value);
      }
    },
  },
};
</script>

<style lang="scss">
.tui-range {
  flex: auto;
  flex-direction: column;

  &__labels {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: var(--gap-1);
  }

  &__lowLabel,
  &__highLabel {
    @include tui-font-body-small();
    flex-basis: 40%;
    color: var(--color-neutral-6);
  }

  &__lowLabel {
    text-align: left;
  }

  &__highLabel {
    text-align: right;
  }

  &__input {
    height: var(--form-range-height);
    padding: 0;
    outline: none;
    -webkit-appearance: none;

    &:disabled {
      background-color: transparent;
    }

    &:focus {
      @include tui-focus();
    }
    &::-moz-focus-outer {
      border: 0;
    }

    /* Track styles */
    &::-webkit-slider-runnable-track {
      @include tui-range-track();
    }
    &:focus::-webkit-slider-runnable-track {
      background: var(--color-neutral-4);
      -webkit-print-color-adjust: exact;
      color-adjust: exact;
    }
    &::-moz-range-track {
      @include tui-range-track();
    }
    &::-ms-track {
      @include tui-range-track();
      color: transparent; /* Remove default tick marks */
      background: transparent; /* Replace bg colour from the track with ms-fill-lower and ms-fill-upper */
      border-color: transparent; /* Thumb can not overlay track so we add invisible border */
    }
    &::-ms-fill-upper,
    &::-ms-fill-lower {
      background: var(--color-neutral-4);
      border-radius: var(--border-radius-small);
    }
    &:focus::-ms-fill-upper,
    &:focus::-ms-fill-lower {
      background: var(--color-neutral-4);
    }

    /* Thumb styles */
    &::-webkit-slider-thumb {
      @include tui-range-thumb();
      margin-top: var(--form-range-thumb-margin-top);
      -webkit-appearance: none;
    }
    &::-moz-range-thumb {
      @include tui-range-thumb();
    }
    &::-ms-thumb {
      @include tui-range-thumb();
    }

    &.tui-range__input--selected {
      &::-webkit-slider-thumb {
        background: var(--color-state);
      }
      &::-moz-range-thumb {
        background: var(--color-state);
      }
      &::-ms-thumb {
        background: var(--color-state);
      }
    }

    &.tui-range__input--noThumb {
      &::-webkit-slider-thumb {
        display: none;
      }
      &::-moz-range-thumb {
        // Display none does not work.
        opacity: 0;
      }
      &::-ms-thumb {
        display: none;
      }
    }
  }
}
</style>
