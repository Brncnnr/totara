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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div>
    The overflow detector component is used to detect which items fit in a
    container, so the ones that don't fit can be shown in a dropdown menu or
    similar.
    <SamplesExample>
      <div class="tui-sampleOverflow">
        <p><Button text="Randomize order" @click="randomize" /></p>
        <p>Main container ({{ visible }} visible):</p>
        <p>
          <OverflowDetector v-slot="{ measuring }" @change="overflowChanged">
            <div class="tui-sampleOverflow__items">
              <Button
                v-for="(text, i) in items"
                v-show="measuring || i < visible"
                :key="i"
                class="tui-sampleOverflow__item"
                :text="text"
              />
            </div>
          </OverflowDetector>
        </p>
        <p>Overflowing: {{ overflowing ? 'Yes' : 'No' }}</p>
        <p>Overflowed items:</p>
        <p>
          <Button
            v-for="(text, i) in items.slice(visible)"
            :key="i"
            :text="text"
          />
        </p>
      </div>
    </SamplesExample>
    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
      <template v-slot:style>{{ codeStyle }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import OverflowDetector from 'tui/components/util/OverflowDetector';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    Button,
    OverflowDetector,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      items: [
        'Photon',
        'Gluon',
        'W/Z boson',
        'Higgs boson',
        'Electron',
        'Positron',
        'Muon',
        'Tau',
        'Neutrino',
      ],
      visible: Infinity,
      overflowing: false,
      codeTemplate: `<OverflowDetector v-slot="{ measuring }" @change="overflowChanged">
  <div class="tui-sampleOverflow__items">
    <Button
      v-for="(text, i) in items"
      v-show="measuring || i < visible"
      :key="i"
      class="tui-sampleOverflow__item"
      :text="text"
    />
  </div>
</OverflowDetector>`,
      codeScript: `import OverflowDetector from 'tui/components/util/OverflowDetector';

export default {
  components: {
    OverflowDetector,
  },

  data() {
    return {
      visible: Infinity,
    }
  },

  methods: {
    overflowChanged({ visible }) {
      this.visible = visible;
    },
  },
}`,
      codeStyle: `.tui-sampleOverflow {
  &__items {
    display: flex;
  }

  &__item {
    flex-shrink: 0;
  }
}`,
    };
  },

  methods: {
    overflowChanged({ visible, overflowing }) {
      this.visible = visible;
      this.overflowing = overflowing;
    },

    randomize() {
      this.items = this.items.slice().sort(() => Math.random() - 0.5);
    },
  },
};
</script>

<style lang="scss">
.tui-sampleOverflow {
  &__items {
    display: flex;
  }

  &__item {
    flex-shrink: 0;
  }
}
</style>
