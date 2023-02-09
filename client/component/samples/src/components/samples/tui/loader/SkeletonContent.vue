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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div class="tui-sampleSkeletonContent">
    Placeholder skeleton content, used for loading displays. If using the
    skeleton content without a loading overlay an aria-live region must be used
    to announce loading state to screen readers

    <SamplesExample>
      <h4>With loading overlay</h4>
      <Loader :loading="true" :fullpage="false">
        <div class="tui-sampleSkeletonContent__wrap">
          <!-- image -->
          <div class="tui-sampleSkeletonContent__img">
            <SkeletonContent :has-overlay="true" />
          </div>

          <div class="tui-sampleSkeletonContent__content">
            <!-- title -->
            <h3 class="tui-sampleSkeletonContent__title">
              <SkeletonContent char-length="25" :has-overlay="true" />
            </h3>

            <!-- body -->
            <div class="tui-sampleSkeletonContent__body">
              <SkeletonContent
                :char-length="width"
                :has-overlay="true"
                :lines="lines"
              />
            </div>
          </div>
        </div>
      </Loader>

      <h4>Without loading overlay</h4>

      <div class="tui-sampleSkeletonContent__wrap">
        <!-- image -->
        <div class="tui-sampleSkeletonContent__img">
          <SkeletonContent />
        </div>

        <div class="tui-sampleSkeletonContent__content">
          <!-- title -->
          <h3 class="tui-sampleSkeletonContent__title">
            <SkeletonContent char-length="25" />
          </h3>

          <!-- body -->
          <div class="tui-sampleSkeletonContent__body">
            <SkeletonContent :char-length="width" :lines="lines" />
          </div>
        </div>
      </div>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Body content lines">
        <RadioGroup v-model="lines" :horizontal="true">
          <Radio :value="1">1</Radio>
          <Radio :value="2">2</Radio>
          <Radio :value="3">3</Radio>
          <Radio :value="5">5</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Body content character width">
        <RadioGroup v-model="width" :horizontal="true">
          <Radio :value="5">5</Radio>
          <Radio :value="10">10</Radio>
          <Radio :value="15">15</Radio>
          <Radio :value="20">20</Radio>
          <Radio :value="25">25</Radio>
          <Radio :value="30">30</Radio>
          <Radio :value="50">50</Radio>
          <Radio :value="75">75</Radio>
          <Radio value="full">full</Radio>
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
import Loader from 'tui/components/loading/Loader';
import SkeletonContent from 'tui/components/loading/SkeletonContent';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Loader,
    SkeletonContent,
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      lines: 1,
      width: 20,

      codeTemplate: `<SkeletonContent :char-length="25" :has-overlay="true" :lines="2" />`,
      codeScript: `import SkeletonContent from 'tui/components/loading/SkeletonContent';

export default {
  components: {
    SkeletonContent,
  }
}`,
    };
  },
};
</script>

<style lang="scss">
.tui-sampleSkeletonContent {
  &__wrap {
    display: flex;

    & > * + * {
      margin-left: var(--gap-4);
    }
  }

  &__img {
    width: 180px;
    height: 99px;
    background: var(--color-neutral-3);
  }

  &__content {
    flex-grow: 1;
  }

  &__title {
    margin-top: 0;
  }
}
</style>
