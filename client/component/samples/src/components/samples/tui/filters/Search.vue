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
      <SearchFilter
        v-model="searchFilter"
        label="Filter items"
        :disabled="disabled"
        :placeholder="'Filtering'"
        :show-label="showLabel"
        :stacked="stacked"
        @submit="searchSubmit"
      />
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

      <FormRow label="Disabled">
        <RadioGroup v-model="disabled" :horizontal="true">
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
import SearchFilter from 'tui/components/filters/SearchFilter';

export default {
  components: {
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    SearchFilter,
  },

  data() {
    return {
      disabled: false,
      searchFilter: '',
      showLabel: true,
      stacked: false,
      codeTemplate: `<SearchFilter
  v-model="searchFilter"
  label="Filter items"
  :disabled="disabled"
  :placeholder="'Filtering'"
  :show-label="showLabel"
  :stacked="stacked"
  @submit="searchSubmit"
/>`,
      codeScript: `import SearchFilter from 'tui/components/filters/SearchFilter';

export default {
  components: {
    SearchFilter,
  }
}`,
    };
  },

  methods: {
    searchSubmit() {
      console.log('submitted');
    },
  },
};
</script>
