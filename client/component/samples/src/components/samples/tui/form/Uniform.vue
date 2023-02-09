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
  <Uniform
    v-slot="{ getSubmitting }"
    :initial-values="initialValues"
    :errors="errors"
    :validate="validate"
    @change="handleChange"
    @submit="submit"
  >
    <FormRow label="Title" required>
      <FormText
        name="title"
        :validations="v => [v.required()]"
        char-length="30"
      />
    </FormRow>

    <!-- or specify the input by hand -->

    <FormRow label="Length" required>
      <FormField
        v-slot="{ attrs, value, update, blur }"
        name="length"
        :validations="v => [v.required(), v.number()]"
      >
        <InputText v-bind="attrs" :value="value" @input="update" @blur="blur" />
      </FormField>
    </FormRow>

    <FormRow>
      <FormCheckbox name="isLizard" :validations="v => [v.required()]">
        You're a lizard, Barry!
      </FormCheckbox>
    </FormRow>

    <FormRow
      label="Colour"
      :is-stacked="true"
      :aria-describedby="$id('colour-default') + ' ' + $id('colour-details')"
    >
      <FormColor
        name="color"
        :validations="v => [v.required(), v.colorValueHex()]"
      />
      <FormRowDefaults :id="$id('colour-default')">{{
        initialValues.color
      }}</FormRowDefaults>
      <FormRowDetails :id="$id('colour-details')"
        >This field changes colour</FormRowDetails
      >
    </FormRow>

    <FormRow label="Age">
      <FormNumber name="age" />
    </FormRow>

    <FormRow label="Bread" required>
      <FormRadioGroup name="bread" :validations="v => [v.required()]">
        <Radio value="chorleywood">Chorleywood</Radio>
        <Radio value="ciabatta">Ciabatta</Radio>
        <Radio value="rye">Rye</Radio>
        <Radio value="sourdough">Sourdough</Radio>
      </FormRadioGroup>
    </FormRow>

    <FormRow v-slot="{ labelId }" label="Answers">
      <FieldGroup :aria-labelledby="labelId">
        <FieldArray v-slot="{ items, push, remove }" path="answers">
          <Repeater
            :rows="items"
            :min-rows="1"
            :delete-icon="true"
            :allow-deleting-first-items="true"
            @add="push('')"
            @remove="(item, i) => remove(i)"
          >
            <template v-slot="{ row, index }">
              <FormText
                :name="index"
                :validations="v => [v.required()]"
                aria-label="Answer text"
              />
            </template>
          </Repeater>
        </FieldArray>
      </FieldGroup>
    </FormRow>

    <SampleFormPart path="fullName" />

    <FormRow label="Pizza toppings" required>
      <FormCheckboxGroup name="toppings" :validations="v => [v.required()]">
        <Checkbox value="chicken">Chicken</Checkbox>
        <Checkbox value="jalapenos">Jalapeños</Checkbox>
        <Checkbox value="mushroom">Mushroom</Checkbox>
        <Checkbox value="ruined">Pineapple</Checkbox>
      </FormCheckboxGroup>
    </FormRow>

    <FormRow label="Pineapple" :is-stacked="true">
      <FormToggleSwitch
        name="pineapple"
        :toggle-first="true"
        aria-label="Does pineapple belong on pizza?"
        :aria-describedby="$id('pineapple-on-pizza')"
      />
      <FormRowDetails :id="$id('pineapple-on-pizza')"
        >Belongs on pizza?</FormRowDetails
      >
    </FormRow>

    <FormRow label="Pizza slices" required>
      <FormRange
        name="pizzaSlices"
        :value="null"
        :default-value="6"
        :show-labels="true"
        :min="1"
        :max="12"
        low-label="BIG"
        high-label="SMALL"
        :validations="v => [v.required()]"
      />
    </FormRow>

    <FormRow label="Date of delivery">
      <FormDateSelector
        name="dod"
        :validations="v => [v.dateMinLimit('2020-05-15')]"
      />
    </FormRow>

    <FormRowActionButtons :submitting="getSubmitting()" @cancel="cancel" />

    <h3 v-if="value">Current value</h3>
    <pre v-if="value">{{ value }}</pre>

    <h3 v-if="result">Result</h3>
    <pre v-if="result">{{ result }}</pre>
  </Uniform>
</template>

<script>
import {
  Uniform,
  FormField,
  FieldArray,
  FormRow,
  FormColor,
  FormText,
  FormNumber,
  FormRadioGroup,
  FormCheckbox,
  FormCheckboxGroup,
  FormToggleSwitch,
  FormRange,
  FormDateSelector,
} from 'tui/components/uniform';
import InputText from 'tui/components/form/InputText';
import Checkbox from 'tui/components/form/Checkbox';
import Radio from 'tui/components/form/Radio';
import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import FormRowDefaults from 'tui/components/form/FormRowDefaults';
import FieldGroup from 'tui/components/form/FieldGroup';
import SampleFormPart from 'samples/components/sample_parts/tui/form/FormPart';
import Repeater from 'tui/components/form/Repeater';
import theme from 'tui/theme';

export default {
  components: {
    Uniform,
    FormField,
    FieldArray,
    FormRow,
    FormText,
    FormColor,
    FormNumber,
    FormRadioGroup,
    FormRange,
    FormDateSelector,
    InputText,
    FormToggleSwitch,
    Radio,
    FormRowActionButtons,
    FormRowDetails,
    FormRowDefaults,
    FieldGroup,
    SampleFormPart,
    Repeater,
    FormCheckbox,
    FormCheckboxGroup,
    Checkbox,
  },

  data() {
    return {
      initialValues: {
        answers: ['first value', '', 'third value'],
        color: theme.getVar('color-primary'),
        pineapple: true,
      },
      errors: null,
      value: null,
      result: null,
    };
  },

  methods: {
    validate(values) {
      const errors = {};

      if (values.title && values.title.toLowerCase().includes('a')) {
        errors.title = 'Please do not use the letter "a"';
      }

      if (values.pineapple) {
        errors.pineapple = 'Incorrect';
      }

      return errors;
    },

    handleChange(values) {
      this.value = values;
      if (this.errors) {
        this.errors = null;
      }
    },

    submit(values) {
      if (values.title && values.title.includes('server')) {
        this.errors = { title: 'Title must not include "server"' };
        return;
      }
      if (this.errors) {
        this.errors = null;
      }
      this.result = values;
    },

    cancel() {
      //
    },
  },
};
</script>
