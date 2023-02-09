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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <SamplesExample>
      <Form>
        <FormRow v-slot="{ id, label }" label="InputCurrency">
          <InputCurrency
            :id="id"
            v-model="inputCurrency"
            :disabled="disabled"
            :placeholder="label"
            :currency="currencyProp"
            :aria-label="
              $str('input_label_in_currency', 'totara_core', {
                label: label,
                currency: currencyProp,
              })
            "
          />
          <FormRowDetails>{{ displayValue }}</FormRowDetails>
        </FormRow>
      </Form>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Disabled">
        <RadioGroup v-model="disabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Currency code">
        <RadioGroup v-model="currency" :horizontal="true">
          <Radio value="JPY">JPY</Radio>
          <Radio value="USD">USD</Radio>
          <Radio value="UYW">UYW</Radio>
        </RadioGroup>
        <FormRowDetails
          >Alphabetic code defined by
          <a href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank"
            >ISO 4217</a
          >
        </FormRowDetails>
      </FormRow>
      <FormRow label="Currency custom symbol">
        <RadioGroup v-model="customSymbol" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Currency symbol side">
        <RadioGroup v-model="side" :horizontal="true" :disabled="!customSymbol">
          <Radio value="auto">Auto</Radio>
          <Radio value="start">Start</Radio>
          <Radio value="end">End</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Number of fractional digits">
        <RadioGroup
          v-model="fractions"
          :horizontal="true"
          :disabled="!customSymbol"
        >
          <Radio value="auto">Auto</Radio>
          <Radio :value="0">0</Radio>
          <Radio :value="2">2</Radio>
          <Radio :value="4">4</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

import InputCurrency from 'tui/components/form/InputCurrency';
import { CurrencyFormat } from 'tui/currency';

export default {
  components: {
    Form,
    FormRow,
    FormRowDetails,
    Radio,
    RadioGroup,
    SamplesExample,
    SamplesPropCtl,
    InputCurrency,
  },

  data() {
    return {
      inputCurrency: 0,
      currency: 'USD',
      customSymbol: false,
      side: 'auto',
      fractions: 'auto',
      disabled: false,
    };
  },

  computed: {
    currencyProp() {
      if (this.customSymbol) {
        const value = {
          currency: this.currency,
          symbol: '\ud83d\udcb8',
        };
        if (this.side !== 'auto') {
          value.side = this.side;
        }
        if (this.fractions !== 'auto') {
          value.fractions = this.fractions;
        }
        return value;
      } else {
        return this.currency;
      }
    },

    displayValue() {
      const formatter = new CurrencyFormat(this.currencyProp);
      return formatter.format(this.inputCurrency);
    },
  },

  watch: {
    currency() {
      this.updateValue();
    },
    fractions() {
      this.updateValue();
    },
  },

  created() {
    this.updateValue();
  },

  methods: {
    updateValue() {
      const formatter = new CurrencyFormat(this.currencyProp);
      this.inputCurrency = 123456789 / Math.pow(10, formatter.fractions);
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "input_label_in_currency"
  ]
}
</lang-strings>
