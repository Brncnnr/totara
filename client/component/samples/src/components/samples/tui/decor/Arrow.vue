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
  <div class="tui-testArrow">
    The arrow component is a visual component used with overlay or expanded
    content.

    <SamplesExample>
      <div class="tui-testArrow--box">
        <Arrow :size="size" :side="side" :distance="distance" />
      </div>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{}" label="Arrow side">
        <RadioGroup v-model="side" :horizontal="true">
          <Radio value="top">Top</Radio>
          <Radio value="right">Right</Radio>
          <Radio value="bottom">Bottom</Radio>
          <Radio value="left">Left</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{}" label="Arrow size">
        <RadioGroup v-model="size" :horizontal="true">
          <Radio value="normal">Normal</Radio>
          <Radio value="large">Large</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{}" label="Distance (offset)">
        <RadioGroup v-model="distance" :horizontal="true">
          <Radio value="16">16</Radio>
          <Radio value="32">32</Radio>
          <Radio value="64">64</Radio>
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
import Arrow from 'tui/components/decor/Arrow';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Arrow,
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      distance: '32',
      side: 'top',
      size: 'normal',
      codeTemplate: `<Arrow :size="size" :side="side" :distance="distance" />`,
      codeScript: `import Arrow from 'tui/components/decor/Arrow';

export default {
  components: {
    Arrow,
  }
}`,
    };
  },
};
</script>

<style lang="scss">
.tui-testArrow {
  &--box {
    position: relative;
    width: 50%;
    height: 100px;
    background: var(--color-neutral-1);
    border: 1px solid var(--color-neutral-5);
  }
}
</style>
