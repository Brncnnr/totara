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
    A date selector component

    <SamplesExample>
      <Uniform v-slot="{ getSubmitting }" :errors="errors" @submit="submit">
        <FormRow v-slot="{ labelId }" label="Event date">
          <FieldGroup :aria-labelledby="labelId">
            <FormDateSelector
              v-modal="dateValue"
              name="date"
              :initial-timezone="'Pacific/Auckland'"
              :initial-current-date="currentDate"
              :initial-custom-date="customDate"
              :disabled="disabled"
              :has-timezone="timezoned"
              :type="isoType"
              :years-midrange="parseInt(yearsMidrange)"
              :years-before-midrange="parseInt(yearsBeforeMidrange)"
              :years-after-midrange="parseInt(yearsAfterMidrange)"
              :year-range-start="parseInt(yearRangeStart)"
              :year-range-end="parseInt(yearRangeEnd)"
              :validations="
                v => [
                  v.required(),
                  v.date(),
                  v.dateMinLimit(minLimit, minLimitErrorMsg),
                  v.dateMaxLimit(maxLimit, maxLimitErrorMsg),
                ]
              "
            />
          </FieldGroup>
        </FormRow>
        <FormRowActionButtons :submitting="getSubmitting()" />
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
      <FormRow label="Has timezone">
        <RadioGroup v-model="timezoned">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <hr />
      <FormRow label="Set to custom date">
        <RadioGroup v-model="customDate">
          <Radio :value="false">None</Radio>
          <Radio :value="'1994-02-04'">1994-02-04</Radio>
          <Radio :value="'2004-08-24'">2004-08-24</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Set to current date (Overwritten by Custom date)">
        <RadioGroup v-model="currentDate">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <hr />
      <FormRow label="Response type">
        <RadioGroup v-model="isoType">
          <Radio :value="'date'">ISO date</Radio>
          <Radio :value="'dateTime'">ISO date & time</Radio>
        </RadioGroup>
      </FormRow>

      <hr />
      <h4>Floating year range</h4>

      <FormRow v-slot="{ id, label }" :label="'Midrange year'">
        <InputNumber :id="id" v-model="yearsMidrange" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" :label="'Years before midrange'">
        <InputNumber
          :id="id"
          v-model="yearsBeforeMidrange"
          :placeholder="label"
        />
      </FormRow>

      <FormRow v-slot="{ id, label }" :label="'Years after midrange'">
        <InputNumber
          :id="id"
          v-model="yearsAfterMidrange"
          :placeholder="label"
        />
      </FormRow>

      <hr />
      <h4>Fixed year range (alternative to floating)</h4>

      <FormRow v-slot="{ id, label }" :label="'Year range start'">
        <InputNumber :id="id" v-model="yearRangeStart" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" :label="'Year range end'">
        <InputNumber :id="id" v-model="yearRangeEnd" :placeholder="label" />
      </FormRow>

      <hr />
      <FormRow label="Date cannot be after">
        <RadioGroup v-model="maxLimit">
          <Radio :value="false">None</Radio>
          <Radio :value="'1994-02-03'">1994-02-03</Radio>
          <Radio :value="'2004-08-23'">2004-08-23</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ id, label }" :label="'Over max limit Error Message'">
        <InputText :id="id" v-model="maxLimitErrorMsg" :placeholder="label" />
      </FormRow>

      <hr />
      <FormRow label="Date cannot be before">
        <RadioGroup v-model="minLimit">
          <Radio :value="false">None</Radio>
          <Radio :value="'1994-02-01'">1994-02-01</Radio>
          <Radio :value="'2004-08-21'">2004-08-21</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ id, label }" :label="'Under min limit Error Message'">
        <InputText :id="id" v-model="minLimitErrorMsg" :placeholder="label" />
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
import InputNumber from 'tui/components/form/InputNumber';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import FieldGroup from 'tui/components/form/FieldGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';

import { FormDateSelector, Uniform } from 'tui/components/uniform';

export default {
  components: {
    FormRow,
    InputNumber,
    InputText,
    FormRowActionButtons,
    FormDateSelector,
    Radio,
    RadioGroup,
    FieldGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,

    Uniform,
  },

  data() {
    return {
      currentDate: true,
      customDate: false,
      isoType: 'date',
      dateValue: {},
      disabled: false,
      errors: null,
      yearsMidrange: new Date().getFullYear(),
      yearsBeforeMidrange: 50,
      yearsAfterMidrange: 50,
      yearRangeStart: null,
      yearRangeEnd: null,
      maxLimit: false,
      maxLimitErrorMsg: '',
      minLimit: false,
      minLimitErrorMsg: '',
      selectedDate: {},
      timezoned: true,
      codeTemplate: `<Uniform
  v-slot="{ getSubmitting }"
  :errors="errors"
  @submit="submit"
>
  <FormRow label="Event date" v-slot="{ labelId }">
    <FieldGroup :aria-labelledby="labelId">
      <FormDateSelector
        name="date"
        :initial-current-date="true"
        :initial-custom-date="customDate"
        :has-timezone="true"
        :type="date"
        :validations="
          v => [
            v.required(),
            v.date(),
          ]
        "
      />
    </FieldGroup>
  </FormRow>
  ...
</Uniform>`,
      codeScript: `import {
  FormDateSelector,
  Uniform,
} from 'tui/components/uniform';

export default {
  components: {
    FormDateSelector,
    Uniform,
  },
}`,
    };
  },

  methods: {
    submit(values) {
      if (values.date) {
        this.selectedDate = values.date;
      }
    },
  },
};
</script>
