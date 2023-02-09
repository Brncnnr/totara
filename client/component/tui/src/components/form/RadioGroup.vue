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
    class="tui-radioGroup"
    :class="{
      'tui-radioGroup--horizontal': horizontal,
      'tui-radioGroup--inputSizedOptions': inputSizedOptions,
      'tui-radioGroup--noPaddingTop': noPaddingTop,
    }"
    role="radiogroup"
    :aria-labelledby="ariaLabelledby"
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
    ariaLabelledby: String,
    disabled: Boolean,
    horizontal: Boolean,
    inputSizedOptions: Boolean,
    name: {
      type: String,
      default() {
        return this.uid;
      },
    },
    noPaddingTop: Boolean,
    required: Boolean,
    value: [Array, Boolean, Number, Object, String],
  },

  methods: {
    provide({ props }) {
      return {
        props: {
          groupName: this.name,
          name: this.name,
          checked: props.value == this.value,
          disabled: this.disabled,
          required: this.required,
        },
        listeners: {
          select: this.$_handleSelect,
          blur: () => this.$emit('blur'),
        },
      };
    },

    $_handleSelect(value) {
      this.$emit('input', value);
    },
  },
};
</script>

<style lang="scss">
:root {
  --form-radio-group-padding: var(--gap-2);
  // note: should not be more than twice padding
  --form-radio-group-spacing-v: var(--gap-4);
  --form-radio-group-spacing-h: var(--gap-4);
}

.tui-radioGroup {
  display: flex;
  flex-direction: column;
  padding: var(--form-radio-group-padding) 0;

  @include tui-stack-vertical(var(--form-radio-group-spacing-v));

  &--inputSizedOptions {
    & > * {
      align-items: center;
      min-height: var(--form-input-height);
    }
  }

  &--noPaddingTop {
    padding-top: 0;
  }
}

@media screen and (min-width: $tui-screen-sm) {
  .tui-radioGroup--horizontal {
    flex-direction: row;
    flex-wrap: wrap;
    // prettier-ignore
    padding: calc(var(--form-radio-group-padding) - var(--form-radio-group-spacing-v) / 2) 0;

    & > * {
      margin: calc(var(--form-radio-group-spacing-v) / 2) 0;
      margin-right: var(--form-radio-group-spacing-h);
    }
  }
}
</style>
