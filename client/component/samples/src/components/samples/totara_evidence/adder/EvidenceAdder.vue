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
  <div class="tui-loader">
    <p>The evidence adder</p>
    <p>NOTE: this displays your evidence</p>

    <SamplesExample>
      <Button text="Select Evidence" @click="adderOpen" />

      <EvidenceAdder
        :show-loading-btn="showAddButtonSpinner"
        :open="showAdder"
        :existing-items="addedIds"
        @added="adderUpdate"
        @add-button-clicked="toggleLoading"
        @cancel="adderCancelled"
      />

      <h5>Selected Items:</h5>
      <div v-for="evidence in addedEvidences" :key="evidence.id">
        {{ evidence }}
      </div>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{
        sampleCode && sampleCode.template
      }}</template>
      <template v-slot:script>{{ sampleCode && sampleCode.script }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import EvidenceAdder from 'totara_evidence/components/adder/EvidenceAdder';

import Button from 'tui/components/buttons/Button';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    EvidenceAdder,
    Button,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      addedEvidences: [],
      addedIds: [],
      showAdder: false,
      showAddButtonSpinner: false,
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
      this.addedEvidences = selection.data;
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
  "totara_competency": [
    "add_competencies"
  ]
}
</lang-strings>

<sample-template>
  <Button
    text="Select Evidence"
    @click="adderOpen"
  />

  <EvidenceAdder
    :show-loading-btn="showAddButtonSpinner"
    :open="showAdder"
    :existing-items="addedIds"
    @added="adderUpdate"
    @add-button-clicked="toggleLoading"
    @cancel="adderCancelled"
  />

  <h5>Selected Items:</h5>
  <div v-for="evidence in addedEvidences" :key="evidence.id">
    {{ evidence }}
  </div>
</sample-template>

<sample-script>
import EvidenceAdder from 'totara_evidence/components/adder/EvidenceAdder';

export default {
  components: {
    EvidenceAdder,
  },

  data() {
    return {
      addedEvidences: [],
      addedIds: [],
      showAdder: false,
      showAddButtonSpinner: false,
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
      this.addedEvidences = selection.data;
      this.showAddButtonSpinner = false;
      this.showAdder = false;
    },

    toggleLoading() {
      this.showAddButtonSpinner = true;
    },
  },
}
</sample-script>
