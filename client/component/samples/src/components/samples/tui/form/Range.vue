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
  @module samples
-->

<template>
  <div class="tui-testRange">
    <div class="tui-testRange__description">
      Stand-alone range input type

      <ul>
        Make sure the range input has an accessible name doing one of the
        following:
        <li>
          setting the <span class="tui-testRange__code">label</span> prop of the
          <span class="tui-testRange__code">FormRow</span>
        </li>
        <li>
          setting
          <span class="tui-testRange__code"
            >ariaLabelledby="the-id-of-visible-bit-of-text-that-can-act-as-a-label-for-this"</span
          >
          prop of the <span class="tui-testRange__code">Range</span>
        </li>
        <li>
          setting
          <span class="tui-testRange__code"
            >ariaLabel="The name of this range input"</span
          >
          prop of the <span class="tui-testRange__code">Range</span>
        </li>
      </ul>
    </div>

    <SamplesExample class="tui-testRange__example">
      <FormRow>
        <Range
          name="rangeExample"
          :value="selectedValue"
          :default-value="defaultValue"
          :show-labels="showLabels"
          :min="minimumValue"
          :max="maximumValue"
          :low-label="lowLabel"
          :high-label="highLabel"
          @change="changed"
        />
      </FormRow>
      <FormRow>
        <div class="tui-testRange__selectedValue">
          {{ `Selected: ${selectedValue || ''}` }}
        </div>
      </FormRow>
    </SamplesExample>

    <SamplesExample class="tui-testRange__example">
      <Form>
        <FormRow label="Form example">
          <Range
            name="rangeExample"
            :value="selectedValue"
            :default-value="defaultValue"
            :show-labels="showLabels"
            :min="minimumValue"
            :max="maximumValue"
            :low-label="lowLabel"
            :high-label="highLabel"
            @change="changed"
          />
        </FormRow>
      </Form>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Low end value">
        <InputNumber v-model="minimumValue" />
      </FormRow>

      <FormRow label="High end value">
        <InputNumber v-model="maximumValue" />
      </FormRow>

      <FormRow label="Default value">
        <InputText v-model="defaultValue" />
      </FormRow>

      <FormRow label="Low end label">
        <InputText v-model="lowLabel" />
      </FormRow>

      <FormRow label="High end label">
        <InputText v-model="highLabel" />
      </FormRow>

      <FormRow label="Show labels">
        <RadioGroup v-model="showLabels" :horizontal="true">
          <Radio name="showLabels" :value="true">True</Radio>
          <Radio name="showLabels" :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputNumber from 'tui/components/form/InputNumber';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import Range from 'tui/components/form/Range';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Form,
    FormRow,
    InputNumber,
    InputText,
    Radio,
    RadioGroup,
    Range,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      selectedValue: null,
      ariaLabelledby: '',
      minimumValue: 0,
      maximumValue: 10,
      defaultValue: 5,
      lowLabel: 'Some very long piece of text as an example for a low value',
      highLabel:
        'This is an even longer piece of text as an example for a high value that should wrap when too long',
      showLabels: true,

      codeTemplate: `<Range
  value="5"
  :show-labels="true"
  :min="0"
  :max="10"
  low-label="LOW"
  high-label="HIGH"
  @change="changed"
/>`,
      codeScript: `import Range from 'tui/components/form/Range';

export default {
  components: {
    Range,
  }
}`,
    };
  },

  methods: {
    changed(value) {
      this.selectedValue = value;
    },
  },
};
</script>

<style lang="scss">
.tui-testRange {
  &__code {
    display: inline-block;
    margin: 0 2px -1px;
    padding: 0 3px;
    text-decoration: inherit;
    vertical-align: baseline;
    background-color: #f4f5f7;
    background-clip: padding-box;
    border: 1px solid #dfe1e6;
    border-radius: 3px;
  }
  &__description {
    ul {
      margin: 10px 0 0;
    }
    li {
      margin-left: 20px;
    }
  }
  &__selectedValue {
    width: 100%;
    text-align: center;
  }
}
</style>
