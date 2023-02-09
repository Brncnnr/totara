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
  <div>
    collapsible group toggle, for expanding or collasping multiple regions at
    once.

    <SamplesExample>
      <CollapsibleGroupToggle
        v-model="expanded"
        :transparent="transparent"
        :align-end="alignEnd"
      />

      <Collapsible v-model="expanded.first" label="ABC">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Nisi est sit amet
        facilisis magna etiam tempor orci eu. Imperdiet sed euismod nisi porta.
        Odio ut enim blandit volutpat maecenas volutpat blandit aliquam etiam.
        Justo eget magna fermentum iaculis eu non diam phasellus. Aenean et
        tortor at risus. Velit aliquet sagittis id consectetur purus ut. In hac
        habitasse platea dictumst quisque sagittis. Morbi tempus iaculis urna id
        volutpat. Lacinia at quis risus sed vulputate odio ut enim blandit.
        Velit dignissim sodales ut eu sem. Eu mi bibendum neque egestas congue
        quisque egestas. Dapibus ultrices in iaculis nunc sed augue lacus
        viverra. Vel turpis nunc eget lorem. Mollis nunc sed id semper risus in.
        Donec ac odio tempor orci. Id semper risus in hendrerit gravida rutrum
        quisque. Aenean euismod elementum nisi quis eleifend quam.
      </Collapsible>

      <Collapsible v-model="expanded.second" label="DEF">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Duis ut diam quam
        nulla porttitor massa id neque. Cursus risus at ultrices mi tempus
        imperdiet nulla.
      </Collapsible>

      <Collapsible v-model="expanded.third" label="GHI">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
        tempor incididunt ut labore et dolore magna aliqua. Duis ut diam quam
        nulla porttitor massa id neque. Cursus risus at ultrices mi tempus
        imperdiet nulla.
      </Collapsible>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="First expanded">
        <RadioGroup v-model="expanded.first" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Second expanded">
        <RadioGroup v-model="expanded.second" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Third expanded">
        <RadioGroup v-model="expanded.third" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Button transparent">
        <RadioGroup v-model="transparent" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
      <FormRow label="Align to end">
        <RadioGroup v-model="alignEnd" :horizontal="true">
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
import Collapsible from 'tui/components/collapsible/Collapsible';
import CollapsibleGroupToggle from 'tui/components/collapsible/CollapsibleGroupToggle';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Collapsible,
    CollapsibleGroupToggle,
    FormRow,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      expanded: {
        first: false,
        second: true,
        third: false,
      },
      label: 'Heading',
      transparent: true,
      alignEnd: true,

      codeTemplate: `<CollapsibleGroupToggle
  v-model="expanded"
  :transparent="transparent"
  :alignEnd="true"
/>
<Collapsible v-model="expanded.first" label="ABC">...</Collapsible>
<Collapsible v-model="expanded.second" label="DEF">...</Collapsible>
<Collapsible v-model="expanded.third" label="GHI">...</Collapsible>`,
      codeScript: `import CollapsibleGroupToggle from 'tui/components/collapsible/CollapsibleGroupToggle';

export default {
  components: {
    CollapsibleGroupToggle,
  },

  data() {
    return {
      expanded: {
        first: false,
        second: true,
        third: false,
      },
    },
  },
}`,
    };
  },
};
</script>
