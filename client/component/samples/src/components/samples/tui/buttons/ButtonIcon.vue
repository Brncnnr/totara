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
    The button icon component is used when triggering an on page action.

    <SamplesExample>
      <ButtonIcon
        :aria-label="'Add'"
        :disabled="disabled"
        :caret="caret"
        :loading="loading"
        :styleclass="{
          alert: alertStyle,
          circle: circleStyle,
          primary: primaryStyle,
          small: smallStyle,
          textFirst: textFirstStyle,
          transparent: transparentStyle,
          stealth: stealthStyle,
          toolbar: toolbarStyle,
          selected: selectedStyle,
          xsmall: xsmallStyle,
          transparentNoPadding: paddingStyle,
        }"
        :text="text"
      >
        <AddIcon />
      </ButtonIcon>

      <h4>Form</h4>
      <Form>
        <FormRow label="Button">
          <ButtonIcon
            :aria-label="'Add'"
            :disabled="disabled"
            :caret="caret"
            :loading="loading"
            :styleclass="{
              alert: alertStyle,
              circle: circleStyle,
              primary: primaryStyle,
              small: smallStyle,
              textFirst: textFirstStyle,
              transparent: transparentStyle,
              stealth: stealthStyle,
              toolbar: toolbarStyle,
              selected: selectedStyle,
              xsmall: xsmallStyle,
              transparentNoPadding: paddingStyle,
            }"
            :text="text"
          >
            <AddIcon />
          </ButtonIcon>
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

      <FormRow label="Alert">
        <RadioGroup v-model="alertStyle" :horizontal="true">
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

      <FormRow label="Extra small">
        <RadioGroup v-model="xsmallStyle" :horizontal="true">
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

      <FormRow label="Circle">
        <RadioGroup v-model="circleStyle" :horizontal="true">
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

      <FormRow label="Stealth">
        <RadioGroup v-model="stealthStyle" :horizontal="true">
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

      <FormRow label="Text first">
        <RadioGroup v-model="textFirstStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Toolbar">
        <RadioGroup v-model="toolbarStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Selected (for toolbar)">
        <RadioGroup v-model="selectedStyle" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Transparent No padding">
        <RadioGroup v-model="paddingStyle" :horizontal="true">
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
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
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
    AddIcon,
    ButtonIcon,
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
      circleStyle: false,
      disabled: false,
      inputButtonStyle: false,
      alertStyle: false,
      primaryStyle: false,
      textFirstStyle: false,
      smallStyle: false,
      xsmallStyle: false,
      text: '',
      transparentStyle: false,
      paddingStyle: false,
      stealthStyle: false,
      toolbarStyle: false,
      selectedStyle: false,
      caret: false,
      loading: false,
      codeTemplate: `<ButtonIcon
  :aria-label="Add"
  :disabled="disabled"
  :loading="loading"
  :styleclass="{
    alert: alertStyle,
    circle: circleStyle,
    primary: primaryStyle,
    small: smallStyle,
    textFirst: textFirstStyle,
    transparent: transparentStyle,
  }"
  :text="text"
  >
    <AddIcon size="200" />
</ButtonIcon>`,
      codeScript: `import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    ButtonIcon,
  }
}`,
    };
  },
};
</script>
