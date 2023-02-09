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
    A date selector component for start/end dates

    <SamplesExample>
      <Uniform
        v-slot="{ getSubmitting }"
        :errors="errors"
        :validate="validate"
        @submit="submit"
      >
        <FormRow v-slot="{ labelId }" label="Start date">
          <FieldGroup :aria-labelledby="labelId">
            <FormDateSelector
              v-modal="startValue"
              name="startDate"
              :initial-current-date="true"
              type="date"
              :validations="v => [v.required()]"
            />
          </FieldGroup>
        </FormRow>

        <FormRow v-slot="{ labelId }" label="End date">
          <FieldGroup :aria-labelledby="labelId">
            <FormDateSelector
              v-modal="endValue"
              name="endDate"
              :initial-current-date="true"
              type="date"
              :validations="v => [v.required()]"
            />
          </FieldGroup>
        </FormRow>
        <FormRowActionButtons :submitting="getSubmitting()" />
      </Uniform>

      <h4>Submitted value:</h4>
      <div v-if="selectedStartDate">
        <h5>Start date</h5>
        {{ selectedStartDate }}
      </div>
      <div v-if="selectedEndDate">
        <h5>End date</h5>
        {{ selectedEndDate }}
      </div>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import FormRow from 'tui/components/form/FormRow';
import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';
import FieldGroup from 'tui/components/form/FieldGroup';
import { FormDateSelector, Uniform } from 'tui/components/uniform';
// Utils
import { isIsoAfter } from 'tui/date';

export default {
  components: {
    FormRow,
    FormRowActionButtons,
    FormDateSelector,
    FieldGroup,
    SamplesCode,
    SamplesExample,
    Uniform,
  },

  data() {
    return {
      errors: null,
      selectedStartDate: {
        iso: '',
      },
      selectedEndDate: {
        iso: '',
      },
      startValue: '',
      endValue: '',
      codeTemplate: `<Uniform
  v-slot="{ getSubmitting }"
  :errors="errors"
  :validate="validate"
  @submit="submit"
>
  <FormRow label="Start date" v-slot="{ labelId }">
    <FieldGroup :aria-labelledby="labelId">
      <FormDateSelector
        v-modal="startValue"
        name="startDate"
        :initial-current-date="true"
        type="date"
        :validations="v => [v.required()]"
      />
    </FieldGroup>
  </FormRow>

  <FormRow label="End date" v-slot="{ labelId }">
    <FieldGroup :aria-labelledby="labelId">
      <FormDateSelector
        v-modal="endValue"
        name="endDate"
        :initial-current-date="true"
        type="date"
        :validations="v => [v.required()]"
      />
    </FieldGroup>
  </FormRow>
  <FormRowActionButtons :submitting="getSubmitting()" />
</Uniform>`,
      codeScript: `import {
  FormDateSelector,
  Uniform,
} from 'tui/components/uniform';
import FieldGroup from 'tui/components/form/FieldGroup';
// Utils
import { isIsoAfter } from 'tui/date';

export default {
  components: {
    FormDateSelector,
    FormRow,
    FieldGroup,
    Uniform,
  },
},

data() {
  return {
    errors: null,
    startValue: '',
    endValue: '',
  },
},

methods: {
  submit(values) {
    if (values.startDate) {
      this.selectedStartDate = values.startDate;
    }
    if (values.endDate) {
      this.selectedEndDate = values.endDate;
    }
  },

  validate(values) {
    const errors = {};

    if (!isIsoAfter(values.endDate.iso, values.startDate.iso)) {
      errors.endDate = 'End date cannot be before start date';
    }
    return errors;
  },
}`,
    };
  },

  methods: {
    submit(values) {
      if (values.startDate) {
        this.selectedStartDate = values.startDate;
      }
      if (values.endDate) {
        this.selectedEndDate = values.endDate;
      }
    },

    validate(values) {
      const errors = {};

      if (values.endDate && values.startDate) {
        if (!isIsoAfter(values.endDate.iso, values.startDate.iso)) {
          errors.endDate = 'End date cannot be before start date';
        }
      }
      return errors;
    },
  },
};
</script>
