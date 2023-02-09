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

  @author Steve Barnett <steve.barnett@totaralearning.com>
  @module tui
-->

<template>
  <div
    :class="{
      'tui-toggleSwitch': true,
      'tui-toggleSwitch--left': toggleFirst,
    }"
  >
    <button
      :id="id"
      type="button"
      class="tui-toggleSwitch__btn"
      :aria-describedby="ariaDescribedby"
      :aria-label="ariaLabel"
      :aria-pressed="value"
      :disabled="disabled"
      @click="togglePressed"
      @blur="$emit('blur', $event)"
    >
      <span class="tui-toggleSwitch__btn-text">
        {{ text }}
      </span>
    </button>

    <div class="tui-toggleSwitch__icon">
      <slot name="icon" />
    </div>

    <span
      class="tui-toggleSwitch__ui"
      :data-disabled="disabled"
      :class="toggleOnly && 'tui-toggleSwitch__ui--toggleOnly'"
      aria-hidden="true"
      @click="togglePressed"
    />
  </div>
</template>

<script>
export default {
  props: {
    ariaDescribedby: String,
    ariaLabel: String,
    id: {
      type: String,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    text: {
      type: String,
    },
    toggleFirst: {
      type: Boolean,
      default: false,
    },
    toggleOnly: Boolean,
    value: {
      type: Boolean,
    },
  },

  mounted() {
    if (!this.ariaLabel && !this.text) {
      console.error('[ToggleSwitch] You must pass either aria-label or text.');
    }
  },

  methods: {
    togglePressed() {
      if (this.disabled) return;
      // Propagate value change to parent
      this.$emit('input', !this.value);
    },
  },
};
</script>

<style lang="scss">
:root {
  --form-toggle-color: var(--color-neutral-7);
  --form-toggle-dot-size: 1.6rem;
  --form-toggle-container-width: 4rem;
  --form-toggle-container-height: 2rem;
  --form-toggle-container-radius: 1rem;
  --form-toggle-text-offset: var(--gap-2);
  --form-toggle-dot-offset: 0.2rem;
  --form-toggle-bottom: 1.8rem;
  --form-toggle-focus-border: var(--border-width-normal);
  --form-toggle-focus-gap: 0.1rem;
}

.tui-toggleSwitch {
  display: flex;
  align-items: center;

  &__btn {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn;
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--transparent;
    color: var(--form-toggle-color);

    &:focus,
    &:active:focus {
      color: var(--form-toggle-color);
      outline: none;
    }

    &:hover {
      color: var(--form-toggle-color);
    }

    &[disabled] {
      color: initial;
      &:hover {
        color: initial;
      }
      &:hover,
      &:active:hover {
        color: initial;
      }
    }

    &-text {
      position: relative;
    }
  }

  // toggle size and shape
  &__ui {
    position: relative;
    width: var(--form-toggle-container-width);
    margin-left: var(--form-toggle-text-offset);
    // prettier-ignore
    padding: calc(var(--form-toggle-focus-gap) + var(--form-toggle-focus-border));

    &--toggleOnly {
      margin-left: calc(
        -1 * (var(--form-toggle-focus-gap) + var(--form-toggle-focus-border))
      );
    }

    // the toggle background
    &:before {
      display: block;
      height: var(--form-toggle-container-height);
      border-radius: var(--form-toggle-container-radius);
      transition: background-color var(--transition-button-duration)
          var(--transition-button-function),
        border-color var(--transition-button-duration)
          var(--transition-button-function);
      content: '';

      .tui-contextInvalid & {
        box-shadow: 0 0 0 2px var(--form-input-border-color-invalid);
      }
    }

    // the toggle dot
    &:after {
      position: absolute;
      // prettier-ignore
      top: calc(var(--form-toggle-dot-offset) + calc(var(--form-toggle-focus-gap) + var(--form-toggle-focus-border)));
      // prettier-ignore
      left: calc(var(--form-toggle-dot-offset) + calc(var(--form-toggle-focus-gap) + var(--form-toggle-focus-border)));
      display: block;
      width: var(--form-toggle-dot-size);
      height: var(--form-toggle-dot-size);
      border-radius: 50%;
      box-shadow: var(--shadow-2);
      content: '';
    }

    &:hover,
    &:focus {
      cursor: pointer;
    }

    // toggled off

    // the toggle background
    &:before {
      background-color: var(--form-toggle-off-bg-color);
      border: var(--form-input-border-size) solid;
      border-color: var(--form-toggle-border-color);
    }

    // the toggle dot
    &:after {
      background-color: var(--form-toggle-dot-color);
    }

    &:hover,
    &:focus {
      &:before {
        background-color: var(--form-toggle-off-bg-color-hover-focus);
      }
    }

    &[data-disabled] {
      cursor: default;
      &:before {
        background-color: var(--form-toggle-off-bg-color-disabled);
        border-color: var(--form-toggle-border-color-disabled);
      }
    }
  }

  // toggled off, via the button
  &__btn {
    &:hover,
    &:focus {
      ~ .tui-toggleSwitch__ui {
        padding: var(--form-toggle-focus-gap);
        border: var(--form-toggle-focus-border) solid transparent;
        // prettier-ignore
        border-radius: calc(calc(var(--form-toggle-container-radius) + var(--form-toggle-focus-gap)) + var(--form-toggle-focus-border));

        &:after {
          // prettier-ignore
          top: calc(var(--form-toggle-dot-offset) + var(--form-toggle-focus-gap));
          // prettier-ignore
          left: calc(var(--form-toggle-dot-offset) + var(--form-toggle-focus-gap));
        }
      }
    }

    &:focus ~ .tui-toggleSwitch__ui {
      border: var(--form-toggle-focus-border) solid var(--color-state);
    }
  }

  // toggled on
  &__btn[aria-pressed] ~ &__ui {
    // the dot
    &:after {
      // prettier-ignore
      right: calc(var(--form-toggle-dot-offset) + calc(var(--form-toggle-focus-gap) + var(--form-toggle-focus-border)));
      left: auto;
    }

    // the toggle background
    &:before {
      background-color: var(--form-toggle-on-bg-color);
      border-color: var(--form-toggle-on-border-color);
    }

    &:hover,
    &:focus {
      &:before {
        background-color: var(--form-toggle-on-bg-color-hover-focus);
      }
    }
    &[data-disabled] {
      &:before {
        background-color: var(--form-toggle-on-bg-color-disabled);
        border-color: var(--form-toggle-border-color-disabled);
      }
    }
  }

  // toggled on, via the button
  &__btn[aria-pressed] {
    &:hover,
    &:focus {
      ~ .tui-toggleSwitch__ui {
        &:after {
          // prettier-ignore
          right: calc(var(--form-toggle-dot-offset) + var(--form-toggle-focus-gap));
        }
      }
    }
  }

  // toggle on the left, text on the right
  &--left {
    .tui-toggleSwitch__ui {
      order: 1;
      margin-right: var(--form-toggle-text-offset);
      margin-left: 0;
    }

    .tui-toggleSwitch__btn {
      order: 2;
    }

    .tui-toggleSwitch__icon {
      order: 3;
    }
  }
}
</style>
