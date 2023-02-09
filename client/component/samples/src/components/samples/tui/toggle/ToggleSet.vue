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
  <div class="tui-testButton">
    The ToggleSet component is used for switching between multiple states.

    <SamplesExample>
      <h3>Icon</h3>
      <ToggleSet
        v-model="selection"
        :disabled="disabled"
        aria-label="Icon set"
        @input="toggleChange"
      >
        <ToggleButton value="1" aria-label="left">
          <GridIcon />
        </ToggleButton>
        <ToggleButton value="2" aria-label="middle">
          <ListIcon />
        </ToggleButton>
        <ToggleButton value="3" aria-label="right">
          <SliderIcon />
        </ToggleButton>
      </ToggleSet>

      <h3>Text</h3>
      <ToggleSet
        v-model="selection"
        :disabled="disabled"
        aria-label="Icon set"
        @input="toggleChange"
      >
        <ToggleButton value="1" text="left" aria-label="left" />
        <ToggleButton value="2" text="middle" aria-label="middle" />
        <ToggleButton value="3" text="right" aria-label="right" />
      </ToggleSet>

      <h3>Icon With Text</h3>

      <ToggleSet
        v-model="selection"
        :disabled="disabled"
        aria-label="Icon & text set"
        @input="toggleChange"
      >
        <ToggleButton value="1" text="left" aria-label="left">
          <GridIcon />
        </ToggleButton>
        <ToggleButton value="2" text="middle" aria-label="middle">
          <ListIcon />
        </ToggleButton>
        <ToggleButton value="3" text="right" aria-label="right">
          <SliderIcon />
        </ToggleButton>
      </ToggleSet>

      <h3>Large With Text</h3>

      <ToggleSet
        v-model="selection"
        :disabled="disabled"
        aria-label="Large icon & text set"
        :large="true"
        @input="toggleChange"
      >
        <ToggleButton value="1" text="left" aria-label="left">
          <GridIcon />
        </ToggleButton>
        <ToggleButton value="2" text="middle" aria-label="middle">
          <ListIcon />
        </ToggleButton>
        <ToggleButton value="3" text="right" aria-label="right">
          <SliderIcon />
        </ToggleButton>
      </ToggleSet>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Disabled">
        <RadioGroup v-model="disabled" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Selected">
        <RadioGroup v-model="selection" :horizontal="true">
          <Radio value="1">Left</Radio>
          <Radio value="2">Middle</Radio>
          <Radio value="3">Right</Radio>
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
// Components
import ListIcon from 'tui/components/icons/List';
import GridIcon from 'tui/components/icons/Grid';
import SliderIcon from 'tui/components/icons/Slider';
import ToggleSet from 'tui/components/toggle/ToggleSet';
import ToggleButton from 'tui/components/toggle/ToggleButton';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    ListIcon,
    GridIcon,
    SliderIcon,
    ToggleSet,
    ToggleButton,
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      selection: '1',
      disabled: false,

      codeTemplate: `<ToggleSet
  v-model="selection"
  :disabled="disabled"
  aria-label="Icon & text set"
  @input="toggleChange"
>
  <ToggleButton value="1" text="left" aria-label="left">
    <GridIcon />
  </ToggleButton>
  <ToggleButton value="2" text="middle" aria-label="middle">
    <ListIcon />
  </ToggleButton>
  <ToggleButton value="3" text="right" aria-label="right">
    <SliderIcon />
  </ToggleButton>
</ToggleSet>`,
      codeScript: `import ToggleSet from 'tui/components/toggle/ToggleSet';
import ToggleButton from 'tui/components/toggle/ToggleButton';

export default {
  components: {
    ToggleSet,
    ToggleButton
  }
}

methods: {
  /**
   * Toggle state change
   *
   */
  toggleChange(value) {
    console.log('toggle state changed to ' + value);
  },
},`,
    };
  },

  methods: {
    /**
     * Toggle state change
     *
     */
    toggleChange(value) {
      console.log('toggle state changed to ' + value);
    },
  },
};
</script>
