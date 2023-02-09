<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Steve Barnett <stve.barnett@totaralearning.com>
  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-formInputCurrency"
    :class="[
      charLength && 'tui-formInputCurrency--charLength-' + charLength,
      charLength && 'tui-input--customSize',
    ]"
  >
    <span
      v-if="currencySymbolSide == 'start'"
      class="tui-formInputCurrency__symbol"
    >
      {{ currencySymbol }}
    </span>
    <Input
      v-bind="inputProps"
      :type="inputType"
      :step="currencyInputStep"
      char-length="full"
      v-on="$listeners"
    />
    <span
      v-if="currencySymbolSide == 'end'"
      class="tui-formInputCurrency__symbol"
    >
      {{ currencySymbol }}
    </span>
  </div>
</template>

<script>
// Components
import Input from 'tui/components/form/Input';
import { charLengthProp } from './form_common';
import { CurrencyFormat } from 'tui/currency';
import { pick } from 'tui/util';

const passthroughProps = [
  'ariaDescribedby',
  'ariaInvalid',
  'ariaLabel',
  'ariaLabelledby',
  'autocomplete',
  'autofocus',
  'dir',
  'disabled',
  'id',
  'max',
  'min',
  'name',
  'pattern',
  'placeholder',
  'readonly',
  'required',
  'styleclass',
  'value',
];

export default {
  components: {
    Input,
  },
  inheritAttrs: false,

  props: Object.assign(
    {
      currency: {
        type: [String, Object],
        required: true,
        validator(value) {
          try {
            const formatter = new CurrencyFormat(value);
            // never allow the number formatter. just use InputNumber!
            return formatter.type !== 'number';
          } catch (e) {
            return false;
          }
        },
      },
      charLength: charLengthProp,
      step: Number,
      inputType: {
        type: String,
        default: 'number',
      },
    },
    passthroughProps.reduce((acc, prop) => {
      acc[prop] = {};
      return acc;
    }, {})
  ),

  computed: {
    inputProps() {
      return pick(this.$props, passthroughProps);
    },

    currencyLayout() {
      return new CurrencyFormat(this.currency);
    },

    currencySymbolSide() {
      return this.currencyLayout.side;
    },

    currencySymbol() {
      return this.currencyLayout.symbol;
    },

    currencyInputStep() {
      if (typeof this.step !== 'undefined') {
        return this.step;
      }
      return this.currencyLayout.step;
    },
  },
};
</script>

<style lang="scss">
.tui-formInputCurrency {
  display: flex;
  flex-grow: 1;
  align-items: center;
  width: 100%;
  min-width: 0;

  @include tui-char-length-classes();

  &__symbol {
    @include tui-font-heading-label;

    &:first-child {
      margin-right: var(--gap-1);
    }

    &:last-child {
      margin-left: var(--gap-1);
    }
  }
}
</style>
