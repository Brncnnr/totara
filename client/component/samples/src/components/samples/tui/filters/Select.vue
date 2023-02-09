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
    <SamplesExample>
      <SelectFilter
        v-model="selection"
        label="Colour"
        :show-label="showLabel"
        :options="[
          {
            id: '',
            label: 'Please select',
          },
          {
            id: 'orange',
            label: 'Orange',
          },
          {
            id: 'darkRed',
            label: 'Dark red',
          },
          {
            id: 'gold',
            label: 'Gold',
          },
        ]"
        :stacked="stacked"
      />

      <h3>Selection</h3>
      {{ selection }}
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Stacked">
        <RadioGroup v-model="stacked" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Show label">
        <RadioGroup v-model="showLabel" :horizontal="true">
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
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';

// Components
import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    SelectFilter,
  },

  data() {
    return {
      selection: '',
      showLabel: true,
      stacked: false,
      codeTemplate: `<SelectFilter
  v-model="selection"
  label="Colour"
  :options="[
    {
      id: '',
      label: 'Please select',
    },
    {
      id: 'orange',
      label: 'Orange',
    },
    {
      id: 'darkRed',
      label: 'Dark red',
    },
    {
      id: 'gold',
      label: 'Gold',
    },
  ]"
  :show-label="true"
  :stacked="false"
/>`,
      codeScript: `import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    SelectFilter,
  }
}`,
    };
  },
};
</script>
