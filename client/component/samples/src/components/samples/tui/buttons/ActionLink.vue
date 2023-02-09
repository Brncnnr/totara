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
  <div class="tui-testActionLink">
    The action link component is used when styling a link to suggest additional
    actions other than just navigating will be triggered by clicking this.

    <SamplesExample>
      <ActionLink
        href="/"
        :text="text"
        :disabled="disabled"
        :styleclass="{
          primary: primaryStyle,
          small: smallStyle,
          srOnly: screenReaderStyle,
        }"
      />

      <h4>Form</h4>
      <Form :class="'tui-testForm'">
        <FormRow label="Link">
          <ActionLink
            href="/"
            :text="text"
            :disabled="disabled"
            :styleclass="{
              primary: primaryStyle,
              small: smallStyle,
              srOnly: screenReaderStyle,
            }"
          />
        </FormRow>
      </Form>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{ id, label }" label="Text">
        <InputText
          :id="id"
          v-model="text"
          :disabled="disabled"
          :placeholder="label"
        />
      </FormRow>

      <FormRow v-slot="{ labelId }" label="Primary">
        <RadioGroup v-model="primaryStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ labelId }" label="Small">
        <RadioGroup v-model="smallStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ labelId }" label="Disabled">
        <RadioGroup v-model="disabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ labelId }" label="Screen readers only">
        <RadioGroup v-model="screenReaderStyle" :horizontal="true">
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
import ActionLink from 'tui/components/links/ActionLink';
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
    ActionLink,
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
      disabled: false,
      primaryStyle: false,
      smallStyle: false,
      screenReaderStyle: false,
      text: 'Action Link',
      codeTemplate: `<ActionLink
  href="/"
  text="Action Link"
  :disabled="disabled"
  :styleclass="{
    primary: primaryStyle,
    small: smallStyle,
    srOnly: screenReaderStyle,
  }"
/>`,
      codeScript: `import ActionLink from 'tui/components/links/ActionLink';

export default {
  components: {
    ActionLink,
  }
}`,
    };
  },
};
</script>

<style lang="scss">
.tui-testActionLink {
  h4 {
    margin-top: var(--gap-6);
  }
}
</style>
