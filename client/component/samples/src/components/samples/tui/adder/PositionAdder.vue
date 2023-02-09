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
      <Button :text="$str('add_position', 'totara_core')" @click="adderOpen" />

      <PositionAdder
        :open="showAdder"
        :existing-items="addedIds"
        :show-loading-btn="showAddButtonSpinner"
        @added="adderUpdate"
        @add-button-clicked="toggleLoading"
        @cancel="adderCancelled"
      />

      <h5>Selected Items:</h5>
      <div v-for="item in addedPositionItems" :key="item.id">
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
import PositionAdder from 'tui/components/adder/PositionAdder';

import Button from 'tui/components/buttons/Button';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    PositionAdder,
    Button,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      addedPositionItems: [],
      addedIds: [],
      showAdder: false,
      showAddButtonSpinner: false,
      codeTemplate: `<Button :text="$str('add_audiences', 'totara_core')" @click="adderOpen" />

<PositionAdder
  :open="showAdder"
  :existing-items="addedIds"
  @added="adderUpdate"
  @cancel="adderCancelled"
/>

<h5>Selected Items:</h5>
<div v-for="item in addedPositionItems" :key="item.id">
  {{ item }}
</div>
`,
      codeScript: `import PositionAdder from 'tui/components/adder/PositionAdder';

export default {
  components: {
    PositionAdder,
  },

  data() {
    return {
      addedPositionItems: [],
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
      this.addedPositionItems = selection.data;
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
      this.addedPositionItems = selection.data;
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
    "add_position"
  ]
}
</lang-strings>
