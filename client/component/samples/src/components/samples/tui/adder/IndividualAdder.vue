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
  <div class="tui-samplesIndividualsAdder">
    An adder component for selecting individuals

    <SamplesExample>
      <Button
        :text="$str('add_individuals', 'totara_core')"
        @click="adderOpen"
      />

      <IndividualAdder
        :existing-items="addedIds"
        :open="showAdder"
        :show-loading-btn="showAddButtonSpinner"
        @added="adderUpdate"
        @add-button-clicked="toggleLoading"
        @cancel="adderCancelled"
      />

      <div class="tui-samplesIndividualsAdder__selected">
        <h5>Selected Items:</h5>
        <div v-for="individuals in addedIndividuals" :key="individuals.id">
          {{ individuals }}
        </div>
      </div>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>
        {{ sampleCode && sampleCode.template }}
      </template>
      <template v-slot:script>{{ sampleCode && sampleCode.script }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import IndividualAdder from 'tui/components/adder/IndividualAdder';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    Button,
    IndividualAdder,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      addedIds: [],
      addedIndividuals: [],
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
      this.addedIndividuals = selection.data;
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
    "add_individuals"
  ]
}
</lang-strings>

<style lang="scss">
.tui-samplesIndividualsAdder {
  &__selected {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }
}
</style>

<sample-template>
  <Button text="Add individuals" @click="adderOpen" />

  <IndividualAdder
    :existing-items="addedIds"
    :open="showAdder"
    :show-loading-btn="showAddButtonSpinner"
    @added="adderUpdate"
    @add-button-clicked="toggleLoading"
    @cancel="adderCancelled"
  />
</sample-template>

<sample-script>
import IndividualAdder from 'tui/components/adder/IndividualAdder';

export default {
  components: {
    IndividualAdder,
  },

  data() {
    return {
      addedIds: [],
      addedIndividuals: [],
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
      this.addedIndividuals = selection.data;
      this.showAdder = false;
    },
  },
}
</sample-script>
