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
    A radio date range selector component

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
              :name="['relativeDate', 'beforeDate']"
              :disabled="disabled"
              :text="relativeBeforeText"
              value="beforeDate"
            >
              <RadioDateRange
                :disabled="disabledRadio"
                :name="nameLabel"
                :value="value"
                @input="update($event)"
                @accessible-change="
                  a =>
                    setAccessibleLabel(
                      $str('date_range_before_date', 'totara_core', {
                        range: a.range,
                        value: a.value,
                      })
                    )
                "
              />
            </FormRadioWithInput>

            <FormRadioWithInput
              v-slot="{
                disabledRadio,
                nameLabel,
                setAccessibleLabel,
                update,
                value,
              }"
              :name="['relativeDate', 'afterDate']"
              :disabled="disabled"
              :text="relativeAfterText"
              value="afterDate"
            >
              <RadioDateRange
                :disabled="disabledRadio"
                :name="nameLabel"
                :value="value"
                @input="update($event)"
                @accessible-change="
                  a =>
                    setAccessibleLabel(
                      $str('date_range_after_date', 'totara_core', {
                        range: a.range,
                        value: a.value,
                      })
                    )
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

      <FormRow v-slot="{ id, label }" :label="'After option'">
        <InputText :id="id" v-model="relativeAfterText" :placeholder="label" />
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
import RadioDateRange from 'tui/components/form/RadioDateRangeInput';
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
    RadioDateRange,
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
      relativeAfterText: 'After Date:',
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
        :name="['relativeDate', 'beforeDate']"
        :disabled="disabled"
        :text="relativeBeforeText"
        value="beforeDate"
      >

        <RadioDateRange
          :disabled="disabled"
          :name="nameLabel"
          :value="value"
          @input="update($event)"
          @accessible-change="
            a =>
              setAccessibleLabel(
                $str('date_range_before_date', 'totara_core', {
                  range: a.range,
                  value: a.value,
                })
              )
          "
        />

      </FormRadioWithInput>

      <FormRadioWithInput
        v-slot="{
          disabledRadio,
          nameLabel,
          setAccessibleLabel,
          update,
          value,
        }"
        :name="['relativeDate', 'afterDate']"
        :disabled="disabled"
        :text="relativeAfterText"
        value="afterDate"
      >

        <RadioDateRange
          :disabled="disabled"
          :name="nameLabel"
          :value="value"
          @input="update($event)"
          @accessible-change="
            a =>
              setAccessibleLabel(
                $str('date_range_after_date', 'totara_core', {
                  range: a.range,
                  value: a.value,
                })
              )
          "
        />
      </FormRadioWithInput>
    </FormRadioGroup>
  </FormRow>
  <FormRowActionButtons :submitting="getSubmitting()" />
</Uniform>`,
      codeScript: `import RadioDateRange from 'tui/components/form/RadioDateRange';
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
    RadioDateRange,
    Uniform,
  },
}

methods: {
  submit(values) {
    let relativeDate = false;

    if (values.relativeDate) {
      relativeDate = values.relativeDate[values.groupValue];
    }
    this.selectedDate = relativeDate || values.groupValue;
  },
}`,
    };
  },

  methods: {
    submit(values) {
      let relativeDate = false;

      if (values.relativeDate) {
        relativeDate = values.relativeDate[values.groupValue];
      }
      this.selectedDate = relativeDate || values.groupValue;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "date_range_after_date",
      "date_range_before_date"
    ]
  }
</lang-strings>
