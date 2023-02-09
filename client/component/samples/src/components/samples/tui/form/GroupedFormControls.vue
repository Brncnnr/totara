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

  @author Steve Barnett <steve.barnett@totaralearning.com>
  @module samples
-->

<template>
  <div>
    <p>
      The easiest way to keep our forms accessible is to use
      <a href="./?component=totara_core%2Fform%2FUniform">Uniform</a>. That
      comes with accessibility baked in 🍰. Here's how to make groups of form
      controls accessible if you're not using Uniform. Two key things to
      remember are:
    </p>
    <ul>
      <li>every form control needs an accessible name;</li>
      <li>every group of form controls needs an accessible name;</li>
    </ul>

    <p>
      Having an accessible name for a single form control means having a
      programmatic link between the text (that serves as the name) and the form
      text. We use <code>FormRow</code> and <code>:aria-labelledby</code> (when
      there's a visible name) and <code>:aria-label</code> (when there's no
      visible name) on container of the group (a <code>RadioGroup</code>,
      <code>CheckboxGroup</code>).
    </p>

    <h4>Radios</h4>
    <h5>Radios with a visible group label</h5>
    <p>
      Use a <code>FormRow</code> with <code>v-slot="{ labelId }"</code> and an
      <code>aria-labelledby="labelId"</code> on the <code>RadioGroup</code> so
      that the group of controls has an accessible name.
    </p>

    <FormRow v-slot="{ labelId }" label="Favourite colour">
      <RadioGroup :aria-labelledby="labelId">
        <Radio value="red">
          Red
        </Radio>
        <Radio value="green">
          Green
        </Radio>
        <Radio value="blue">
          Blue
        </Radio>
      </RadioGroup>
    </FormRow>

    <h5>Radios without a visible group label</h5>

    <p>
      Have an <code>aria-label</code> on the <code>RadioGroup</code> so that the
      group of controls has an accessible name.
    </p>

    <FormRow>
      <RadioGroup :aria-label="'Favourite colour'">
        <Radio value="red">
          Red
        </Radio>
        <Radio value="green">
          Green
        </Radio>
        <Radio value="blue">
          Blue
        </Radio>
      </RadioGroup>
    </FormRow>

    <hr />

    <h4>Checkboxes</h4>
    <h5>Checkboxes with a visible group label</h5>

    <p>
      Use a <code>FormRow</code> with <code>v-slot="{ labelId }"</code> and an
      <code>aria-labelledby="labelId"</code> on the
      <code>CheckboxGroup</code> so that the group of controls has an accessible
      name.
    </p>

    <FormRow v-slot="{ labelId }" label="Favourite drinks">
      <CheckboxGroup :aria-labelledby="labelId">
        <Checkbox>
          Tea
        </Checkbox>
        <Checkbox>
          Coffee
        </Checkbox>
        <Checkbox>
          Water
        </Checkbox>
      </CheckboxGroup>
    </FormRow>

    <h5>Checkboxes without a visible group label</h5>

    <p>
      Have an <code>aria-label</code> on the <code>CheckboxGroup</code> so that
      the group of controls has an accessible name.
    </p>

    <FormRow>
      <CheckboxGroup :aria-label="'Favourite drinks'">
        <Checkbox>
          Tea
        </Checkbox>
        <Checkbox>
          Coffee
        </Checkbox>
        <Checkbox>
          Water
        </Checkbox>
      </CheckboxGroup>
    </FormRow>

    <hr />

    <h4>Date Selector</h4>

    <p>
      Use a <code>FormRow</code> with <code>v-slot="{ labelId }"</code> and an
      <code>aria-labelledby="labelId"</code> on the <code>DateSelector</code> so
      that the group of controls has an accessible name.
    </p>

    <FormRow v-slot="{ labelId }" label="Event date">
      <DateSelector :aria-labelledby="labelId" />
    </FormRow>

    <hr />

    <h4>Minimum and maximum numbers</h4>

    <FormRow v-slot="{ labelId }" label="Set some limits">
      <FieldGroup :aria-labelledby="labelId">
        <div>
          <InputNumber :id="'min'" aria-label="Minimum" />
          <br />
          <InputNumber :id="'max'" aria-label="Maximum" />
        </div>
      </FieldGroup>
    </FormRow>
  </div>
</template>

<script>
import FormRow from 'tui/components/form/FormRow';
import InputNumber from 'tui/components/form/InputNumber';
import Checkbox from 'tui/components/form/Checkbox';
import CheckboxGroup from 'tui/components/form/CheckboxGroup';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import FieldGroup from 'tui/components/form/FieldGroup';
import DateSelector from 'tui/components/form/DateSelector';

export default {
  components: {
    FormRow,
    InputNumber,
    DateSelector,
    Checkbox,
    CheckboxGroup,
    Radio,
    RadioGroup,
    FieldGroup,
  },
};
</script>
