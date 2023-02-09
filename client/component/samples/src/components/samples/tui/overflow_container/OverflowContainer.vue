<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module samples
-->

<template>
  <div>
    OverflowContainer component
    <SamplesExample>
      <OverflowContainer :items="displayItems" :total="displayItems.length">
        <template v-slot:default="{ item }">
          <Card :clickable="true" :has-hover-shadow="true" v-html="item.text">
            {{ item.text }}
          </Card>
        </template>
      </OverflowContainer>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Number of items">
        <InputNumber v-model="count" />
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import FormRow from 'tui/components/form/FormRow';
import InputNumber from 'tui/components/form/InputNumber';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import OverflowContainer from 'tui/components/overflow_container/OverflowContainer';
import Card from 'tui/components/card/Card';

export default {
  components: {
    FormRow,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    InputNumber,
    OverflowContainer,
    Card,
  },

  data() {
    return {
      items: [
        { text: 'one' },
        { text: 'two' },
        { text: 'three<br/>a<br/>b<br/>c<br/>d<br/>e<br/>f<br/>g<br/>' },
        { text: 'four' },
        { text: 'five' },
        { text: 'six' },
        { text: 'seven' },
        { text: 'eight' },
        { text: 'nine' },
        { text: 'ten' },
        { text: 'eleven' },
        { text: 'twelve' },
        { text: 'thirteen' },
      ],

      count: 13,

      codeTemplate: `<OverflowContainer :items="displayItems" :total="displayItems.length">
  <template v-slot:default="{ item }">
    <Card :clickable="true" :has-hover-shadow="true" v-html="item.text">
      {{ item.text }}
    </Card>
  </template>
</OverflowContainer>
`,
      codeScript: `import OverflowContainer from 'tui/components/overflow_container/OverflowContainer';
import Card from 'tui/components/card/Card';

export default {
  components: {
    OverflowContainer,
    Card,
  },
},
`,
    };
  },

  computed: {
    displayItems() {
      return this.items.slice(0, this.count);
    },
  },

  methods: {},
};
</script>
