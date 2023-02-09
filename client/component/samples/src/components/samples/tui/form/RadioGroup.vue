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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div>
    A group of radio buttons

    <SamplesExample>
      <RadioGroup
        v-model="colour"
        :horizontal="horizontal"
        :disabled="disabled"
        :required="required"
        :aria-labelledby="ariaLabelledby"
      >
        <Radio value="red">
          Red
        </Radio>
        <Radio value="green">
          Green
        </Radio>
        <Radio value="blue">
          Blue
        </Radio>
        <Radio value="yellow">
          Yellow
        </Radio>
      </RadioGroup>

      <h4 class="formHeader">Form</h4>
      <Form>
        <FormRow label="Radio group">
          <RadioGroup
            v-model="food"
            :horizontal="horizontal"
            :disabled="disabled"
            :aria-labelledby="ariaLabelledby"
          >
            <Radio value="eggs">
              Eggs
            </Radio>
            <Radio value="ham">
              Ham
            </Radio>
            <Radio value="cheese">
              Cheese
            </Radio>
            <Radio value="onion">
              Onion
            </Radio>
          </RadioGroup>
        </FormRow>
      </Form>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Horizontal">
        <RadioGroup v-model="horizontal" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Disabled">
        <RadioGroup v-model="disabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Required">
        <RadioGroup v-model="required" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow v-slot="{ id }" label="ariaLabelledby">
        <InputText :id="id" v-model="ariaLabelledby" />
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    FormRow,
    InputText,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      colour: 'green',
      food: 'eggs',
      disabled: false,
      horizontal: false,
      required: false,
      ariaLabelledby: '',

      codeTemplate: `<RadioGroup
v-model="colour"
:horizontal="horizontal"
:disabled="disabled"
:required="required"
:aria-labelledby="ariaLabelledby"
>
  <Radio value="red">Red</Radio>
  <Radio value="green">Green</Radio>
  <Radio value="blue">Blue</Radio>
  <Radio value="yellow">Yellow</Radio>
</RadioGroup>`,
      codeScript: `import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';

export default {
  components: {
    Radio,
    RadioGroup,
  }
}`,
    };
  },
};
</script>
