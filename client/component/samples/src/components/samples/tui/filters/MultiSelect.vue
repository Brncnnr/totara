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
      <div>
        <MultiSelect
          v-model="selection"
          :options="options"
          :title="'temp title'"
          :visible-item-limit="limitItems"
        />
        <br />
        {{ selection }}
      </div>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow
        label="Limit of visible items (extra items added to collapsible area)"
      >
        <RadioGroup v-model="limitItems" :horizontal="true">
          <Radio :value="null">No limit</Radio>
          <Radio :value="1">1</Radio>
          <Radio :value="3">3</Radio>
          <Radio :value="4">4</Radio>
          <Radio :value="5">5</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>
  </div>
</template>

<script>
import FormRow from 'tui/components/form/FormRow';
import MultiSelect from 'tui/components/filters/MultiSelectFilter';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    FormRow,
    MultiSelect,
    Radio,
    RadioGroup,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      limitItems: null,
      options: [
        {
          id: 'course',
          label: 'Include courses',
        },
        {
          id: 'program',
          label: 'Include programs',
        },
        {
          id: 'user',
          label: 'Include users',
        },
        {
          id: 'video',
          label: 'Include Videos',
        },
        {
          id: 'conference',
          label: 'Include conferences',
        },
      ],
      selection: ['program'],
    };
  },

  methods: {},
};
</script>
