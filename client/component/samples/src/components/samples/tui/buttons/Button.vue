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
  <div class="tui-testButton">
    The button component is used either when triggering an on page action or
    within a form. For navigation use a link or Action link.

    <SamplesExample>
      <Button
        :text="text"
        :disabled="disabled"
        :caret="caret"
        :loading="loading"
        :styleclass="{
          primary: primaryStyle,
          small: smallStyle,
          srOnly: screenReaderStyle,
          transparent: transparentStyle,
          reveal: revealStyle,
          stealth: stealthStyle,
        }"
      />

      <h4>Form</h4>
      <Form>
        <FormRow label="Button">
          <Button
            :text="text"
            :disabled="disabled"
            :caret="caret"
            :loading="loading"
            :styleclass="{
              primary: primaryStyle,
              small: smallStyle,
              srOnly: screenReaderStyle,
              transparent: transparentStyle,
              reveal: revealStyle,
              stealth: stealthStyle,
            }"
          />
        </FormRow>
      </Form>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{ id, label }" label="Text">
        <InputText :id="id" v-model="text" :placeholder="label" />
      </FormRow>

      <FormRow label="Primary">
        <RadioGroup v-model="primaryStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Small">
        <RadioGroup v-model="smallStyle" :horizontal="true">
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

      <FormRow label="Caret (for dropdown)">
        <RadioGroup v-model="caret" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Loading">
        <RadioGroup v-model="loading" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Screen readers only">
        <RadioGroup v-model="screenReaderStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Transparent">
        <RadioGroup v-model="transparentStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Reveal">
        <RadioGroup v-model="revealStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Stealth">
        <RadioGroup v-model="stealthStyle" :horizontal="true">
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
// Components
import Button from 'tui/components/buttons/Button';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Button,
    Form,
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
      caret: false,
      disabled: false,
      loading: false,
      primaryStyle: false,
      smallStyle: false,
      screenReaderStyle: false,
      text: 'Button',
      transparentStyle: false,
      revealStyle: false,
      stealthStyle: false,
      codeTemplate: `<Button
  :text="text"
  :disabled="disabled"
  :caret="caret"
  :loading="loading"
  :styleclass="{
    primary: primaryStyle,
    small: smallStyle,
    srOnly: screenReaderStyle,
    transparent: transparentStyle,
    reveal: revealStyle,
  }"
/>`,
      codeScript: `import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Button,
  }
}`,
    };
  },
};
</script>

<style lang="scss">
.tui-testButton {
  h4 {
    margin-top: var(--gap-6);
  }
}
</style>
