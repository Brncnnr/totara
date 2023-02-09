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
      This toggle switch is used to immediately update parts of the UI. It's an
      on/off switch for a state, and can be used as part of a form to reveal
      more related form elements, or used outside of a form to reveal non-form
      elements.
    </p>

    <p>
      On <code>click</code> we change only the button's
      (<code>aria-pressed</code>) state, not its label.
    </p>

    <SamplesExample>
      <ToggleSwitch
        id="toggle"
        v-model="isToggled"
        :disabled="disabled"
        :text="text"
        :toggle-first="toggleFirst"
      >
        <template v-slot:icon>
          <InfoIconButton :aria-label="'Help for the toggle switch'">
            Some content here...
          </InfoIconButton>
        </template>
      </ToggleSwitch>

      <br /><br />
      <ToggleSwitch
        id="toggle"
        v-model="isToggled"
        :disabled="disabled"
        :text="text"
        :toggle-first="toggleFirst"
      />

      <br /><br />

      <ToggleSwitch
        id="toggle"
        v-model="isToggled"
        :text="text"
        :disabled="disabled"
        :toggle-first="toggleFirst"
        :aria-label="ariaLabel"
      />
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="toggleFirst">
        <RadioGroup v-model="toggleFirst" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="disabled">
        <RadioGroup v-model="disabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="ariaLabel">
        <InputText v-model="ariaLabel" />
      </FormRow>
    </SamplesPropCtl>
  </div>
</template>

<script>
// Components
import FormRow from 'tui/components/form/FormRow';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';

export default {
  components: {
    FormRow,
    InfoIconButton,
    InputText,
    Radio,
    RadioGroup,
    SamplesExample,
    SamplesPropCtl,
    ToggleSwitch,
  },

  data() {
    return {
      text: 'Toggle something in the UI',
      toggleFirst: true,
      ariaLabel: 'The toggle button text',
      isToggled: false,
      disabled: false,
    };
  },
};
</script>
