<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module samples
-->

<template>
  <div>
    UI component
    <SamplesExample>
      <Button
        :text="$str('add_organisation', 'totara_core')"
        @click="adderOpen"
      />

      <OrganisationAdder
        :open="showAdder"
        :existing-items="addedIds"
        :show-loading-btn="showAddButtonSpinner"
        @added="adderUpdate"
        @add-button-clicked="toggleLoading"
        @cancel="adderCancelled"
      />

      <h5>Selected Items:</h5>
      <div v-for="item in addedOrganisationItems" :key="item.id">
        {{ item }}
      </div>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import OrganisationAdder from 'tui/components/adder/OrganisationAdder';

import Button from 'tui/components/buttons/Button';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    OrganisationAdder,
    Button,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      addedOrganisationItems: [],
      addedIds: [],
      showAdder: false,
      showAddButtonSpinner: false,
      codeTemplate: `<Button :text="$str('add_audiences', 'totara_core')" @click="adderOpen" />

<OrganisationAdder
  :open="showAdder"
  :existing-items="addedIds"
  @added="adderUpdate"
  @cancel="adderCancelled"
/>

<h5>Selected Items:</h5>
<div v-for="item in addedOrganisationItems" :key="item.id">
  {{ item }}
</div>
`,
      codeScript: `import OrganisationAdder from 'tui/components/adder/OrganisationAdder';

export default {
  components: {
    OrganisationAdder,
  },

  data() {
    return {
      addedOrganisationItems: [],
      addedIds: [],
      showAdder: false,
    }
  },

  methods: {
    adderOpen() {
      this.showAdder = true;
    },

    adderCancelled() {
      this.showAdder = false;
    },

    adderUpdate(selection) {
      this.addedIds = selection.ids;
      this.addedOrganisationItems = selection.data;
      this.showAdder = false;
    },
  },
}`,
    };
  },

  methods: {
    adderOpen() {
      this.showAdder = true;
    },

    adderCancelled() {
      this.showAdder = false;
    },

    adderUpdate(selection) {
      this.addedIds = selection.ids;
      this.addedOrganisationItems = selection.data;
      this.showAddButtonSpinner = false;
      this.showAdder = false;
    },

    toggleLoading() {
      this.showAddButtonSpinner = true;
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "add_organisation"
  ]
}
</lang-strings>
