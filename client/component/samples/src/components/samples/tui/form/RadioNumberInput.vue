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
    A radio number input component

    <SamplesExample>
      <Uniform
        v-slot="{ getSubmitting, reset }"
        :errors="errors"
        @submit="submit"
      >
        <FormRow label="Event date">
          <FormRadioGroup
            name="groupValue"
            :input-sized-options="true"
            :validations="v => [v.required()]"
          >
            <Radio value="current" :disabled="disabled">
              Current date
            </Radio>

            <FormRadioWithInput
              v-slot="{
                disabledRadio,
                nameLabel,
                setAccessibleLabel,
                update,
                value,
              }"
              :disabled="disabled"
              :name="['customNumber', 'numberValue']"
              :text="relativeBeforeText"
              value="numberValue"
            >
              <RadioNumberInput
                :disabled="disabledRadio"
                :name="nameLabel"
                :value="value"
                @input="update($event)"
                @accessible-change="
                  a => setAccessibleLabel($str('days_before', 'totara_core', a))
                "
              />
            </FormRadioWithInput>
          </FormRadioGroup>
        </FormRow>
        <FormRowActionButtons :submitting="getSubmitting()" @cancel="reset" />
      </Uniform>

      <h4>Submitted value:</h4>
      <div v-if="selectedDate">
        {{ selectedDate }}
      </div>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Disabled">
        <RadioGroup v-model="disabled">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <hr />

      <FormRow v-slot="{ id, label }" :label="'Before option'">
        <InputText :id="id" v-model="relativeBeforeText" :placeholder="label" />
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
import RadioNumberInput from 'tui/components/form/RadioNumberInput';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';

import {
  FormRadioGroup,
  FormRadioWithInput,
  Uniform,
} from 'tui/components/uniform';

export default {
  components: {
    FormRadioGroup,
    FormRadioWithInput,
    FormRow,
    FormRowActionButtons,
    InputText,
    Radio,
    RadioNumberInput,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,

    Uniform,
  },

  data() {
    return {
      disabled: false,
      errors: null,
      relativeBeforeText: 'Before Date:',
      selectedDate: {},
      codeTemplate: `<Uniform v-slot="{ getSubmitting }" :errors="errors" @submit="submit">
  <FormRow label="Event date">
    <FormRadioGroup
      name="date"
      :input-sized-options="true"
      :validations="v => [v.required()]"
    >
      <FormRadioWithInput
        v-slot="{
          disabledRadio,
          nameLabel,
          setAccessibleLabel,
          update,
          value,
        }"
        :disabled="disabled"
        :name="['customNumber', 'numberValue']"
        :text="relativeBeforeText"
        value="numberValue"
      >
        <RadioNumberInput
          :disabled="disabledRadio"
          :name="nameLabel"
          :value="value"
          @input="update($event)"
          @accessible-change="
            a =>
              setAccessibleLabel($str('days_before', 'totara_core', a))
          "
        />
      </FormRadioWithInput>
    </FormRadioGroup>
  </FormRow>
  <FormRowActionButtons :submitting="getSubmitting()" />
</Uniform>`,
      codeScript: `import RadioNumberInput from 'tui/components/form/RadioNumberInput';
import {
  FormRadioGroup,
  FormRadioWithInput,
  FormRow,
  Uniform,
} from 'totara_core/components/uniform';

export default {
  components: {
    FormRadioGroup,
    FormRadioWithInput,
    FormRow,
    RadioNumberInput,
    Uniform,
  },
}

methods: {
  submit(values) {
    let customNumber = false;

    if (values.customNumber) {
      customNumber = values.customNumber[values.groupValue];
    }
    this.selectedDate = customNumber || values.groupValue;
  },
  },
}`,
    };
  },

  methods: {
    submit(values) {
      let customNumber = false;

      if (values.customNumber) {
        customNumber = values.customNumber[values.groupValue];
      }
      this.selectedDate = customNumber || values.groupValue;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "days_before"
    ]
  }
</lang-strings>
