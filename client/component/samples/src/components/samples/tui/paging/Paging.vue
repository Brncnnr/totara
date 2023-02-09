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
    Paging component

    <SamplesExample>
      <Paging
        :page="parseInt(pageNumber, 10)"
        :items-per-page="parseInt(itemsPerPage, 10)"
        :total-items="parseInt(totalItems, 10)"
        @page-change="updatePage"
        @count-change="updatePageSize"
      />
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Page number">
        <InputNumber v-model="pageNumber" />
      </FormRow>
      <FormRow label="Total Items">
        <InputNumber v-model="totalItems" />
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
import Paging from 'tui/components/paging/Paging';

export default {
  components: {
    FormRow,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    Paging,
    InputNumber,
  },

  data() {
    return {
      pageNumber: 3,
      itemsPerPage: 10,
      totalItems: 100,

      codeTemplate: `<Paging
  :page="parseInt(pageNumber, 10)"
  :items-per-page="parseInt(itemsPerPage, 10)"
  :total-items="parseInt(totalItems, 10)"
  @page-change="updatePage"
  @count-change="updatePageSize"
/>`,
      codeScript: `import Paging from 'tui/components/paging/Paging';

export default {
  components: {
    Paging,
  },

  methods: {
    updatePage(page) {
      this.pageNumber = page;
    },

    updatePageSize(size) {
      this.itemsPerPage = size;
    },
  },
}`,
    };
  },

  methods: {
    updatePage(page) {
      this.pageNumber = page;
    },

    updatePageSize(size) {
      this.itemsPerPage = size;
    },
  },
};
</script>
