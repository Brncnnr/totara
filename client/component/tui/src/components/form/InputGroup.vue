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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-inputGroup"
    :class="{
      'tui-inputGroup--hasFocus': hasFocus,
      'tui-inputGroup--disabled': disabled,
    }"
  >
    <PropsProvider :provide="provide">
      <slot />
    </PropsProvider>
  </div>
</template>

<script>
import PropsProvider from 'tui/components/util/PropsProvider';

export default {
  components: {
    PropsProvider,
  },
  props: {
    disabled: Boolean,
  },

  data() {
    return {
      hasFocus: false,
    };
  },

  methods: {
    handleFocus() {
      this.hasFocus = true;
    },

    handleBlur() {
      this.hasFocus = false;
    },

    provide() {
      return {
        props: {
          disabled: this.disabled,
        },
        listeners: {
          'input-focus': this.handleFocus,
          'input-blur': this.handleBlur,
        },
      };
    },
  },
};
</script>

<style lang="scss">
.tui-inputGroup {
  display: flex;
  flex-direction: row;
  box-sizing: border-box;
  width: 100%;
  min-width: 0;
  height: var(--form-input-height);
  color: var(--form-input-text-color);
  font-size: var(--form-input-font-size);
  line-height: 1;
  background: var(--form-input-bg-color);
  border: var(--form-input-border-size) solid;
  border-color: var(--form-input-border-color);

  @include tui-char-length-classes();

  .tui-contextInvalid & {
    border-color: var(--form-input-border-color-invalid);
    box-shadow: var(--form-input-shadow-invalid);
  }

  &--hasFocus {
    background: var(--form-input-bg-color-focus);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-input-border-color-focus);
    box-shadow: var(--form-input-shadow-focus);

    .tui-contextInvalid & {
      background: var(--form-input-bg-color-invalid-focus);
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--form-input-shadow-invalid-focus);
    }
  }

  &--disabled {
    color: var(--form-input-text-color-disabled);
    background: var(--form-input-bg-color-disabled);
    border-color: var(--form-input-border-color-disabled);

    &::placeholder {
      color: var(--form-input-text-color-disabled);
    }
  }
}
</style>
