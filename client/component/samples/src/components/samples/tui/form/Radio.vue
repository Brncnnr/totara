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
    A stand-alone radio buttons, to group radios together use the radio group
    component

    <SamplesExample>
      <Radio
        :name="name"
        :value="value"
        :disabled="disabled"
        :checked="checked"
      >
        {{ label }}
      </Radio>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{ id }" label="Label">
        <InputText :id="id" v-model="label" />
      </FormRow>

      <FormRow v-slot="{ id }" label="Name">
        <InputText :id="id" v-model="name" />
      </FormRow>

      <FormRow v-slot="{ id }" label="Value">
        <InputText :id="id" v-model="value" />
      </FormRow>

      <FormRow label="Checked">
        <RadioGroup v-model="checked" :horizontal="true">
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
      disabled: false,
      checked: false,
      label: 'Red',
      name: 'Red',
      value: 'red',

      codeTemplate: `<Radio name="Red" value="red">
  Red
</Radio>`,
      codeScript: `import Radio from 'tui/components/form/Radio';

export default {
  components: {
    Radio,
  }
}`,
    };
  },
};
</script>
