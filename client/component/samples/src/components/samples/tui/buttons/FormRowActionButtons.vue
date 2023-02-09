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
    A helper component for quickly adding submit / cancel buttons to a form.

    <SamplesExample>
      <form>
        <FormRowActionButtons
          @cancel="formCancel"
          @submit.prevent="formSubmit"
        />
      </form>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
// Components
import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    FormRowActionButtons,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      disabled: false,
      primaryStyle: false,
      smallStyle: false,
      screenReaderStyle: false,
      text: 'Button',
      transparentStyle: false,
      codeTemplate: `<FormRowActionButtons
  @cancel="formCancel"
  @submit.prevent="formSubmit"
/>`,
      codeScript: `import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';

export default {
  components: {
    FormRowActionButtons,
  }
}`,
    };
  },

  methods: {
    formCancel() {
      this.enableFormInputs();
    },

    formSubmit() {
      this.disableFormInputs();
    },
  },
};
</script>

<style lang="scss">
.tui-testButton {
  h4 {
    margin-top: var(--gap-6);
  }
}
</style>
