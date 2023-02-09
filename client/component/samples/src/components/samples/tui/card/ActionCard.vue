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
  <div class="tui-loader">
    A visual UI component

    <SamplesExample>
      <ActionCard :has-shadow="true" :stacked="stacked">
        <template v-slot:card-body>
          <span class="tui-samples--highlight">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do
            eiusmod tempor incididunt ut labore et dolore magna aliqua.
          </span>
          <span class="tui-samples--highlight">
            Cursus euismod quis viverra nibh cras.
          </span>
        </template>
        <template v-slot:card-action>
          <Button text="Cursus euismod quis viverra" />
        </template>
      </ActionCard>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Stacked">
        <RadioGroup v-model="stacked" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
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
import ActionCard from 'tui/components/card/ActionCard';
import Button from 'tui/components/buttons/Button';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    ActionCard,
    Button,
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      stacked: false,
      codeTemplate: `<ActionCard>
  <template v-slot:card-body>
    body content
  </template>
  <template v-slot:card-action>
    action content
  </template>
</ActionCard>`,
      codeScript: `import ActionCard from 'tui/components/card/ActionCard';

export default {
  components: {
    ActionCard,
  }
}`,
    };
  },
};
</script>
