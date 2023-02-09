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
    class="tui-checkboxGroup"
    :class="{ 'tui-checkboxGroup--horizontal': horizontal }"
    role="group"
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
    name: {
      type: String,
      default() {
        return this.uid;
      },
    },
    value: [Array, Object],
    /**
     * Format of value - if false, it is an array of values.
     * If true, it is an object map of value -> boolean.
     */
    useObject: Boolean,
  },

  methods: {
    provide({ props }) {
      return {
        props: {
          name: this.name,
          checked: this.useObject
            ? this.value && this.value[props.value]
            : Array.isArray(this.value) && this.value.includes(props.value),
          disabled: this.disabled,
        },
        listeners: {
          change: checked => {
            let newValue;
            if (this.useObject) {
              newValue = Object.assign({}, this.value);
              newValue[props.value] = checked;
            } else {
              newValue = Array.isArray(this.value)
                ? this.value.filter(x => x !== props.value)
                : [];
              if (checked) {
                newValue.push(props.value);
              }
            }
            this.$emit('input', newValue);
          },
          blur: e => {
            // We don't want to trigger the blur event if the user is just tabbing between different options in this CheckboxGroup
            if (!this.$el.contains(e.relatedTarget)) {
              this.$emit('blur', e);
            }
          },
        },
      };
    },
  },
};
</script>

<style lang="scss">
:root {
  --form-checkbox-group-padding: var(--gap-2);
  --form-checkbox-group-spacing-v: var(--gap-4);
  --form-checkbox-group-spacing-h: var(--gap-4);
}

.tui-checkboxGroup {
  display: flex;
  flex-direction: column;
  padding: var(--form-checkbox-group-padding) 0;

  @include tui-stack-vertical(var(--form-checkbox-group-spacing-v));
}

@media screen and (min-width: $tui-screen-sm) {
  .tui-checkboxGroup--horizontal {
    flex-direction: row;
    flex-wrap: wrap;
    // stylelint-disable length-zero-no-unit
    // prettier-ignore
    margin-top: calc(0px - var(--form-checkbox-group-spacing-v) + var(--form-checkbox-group-padding));
    padding-top: 0;

    & > * {
      margin-top: var(--form-checkbox-group-spacing-v);
      margin-right: var(--form-checkbox-group-spacing-h);
    }
  }
}
</style>
