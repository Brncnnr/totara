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
  <div>
    File card

    <SamplesExample>
      <FileCard
        :file-size="fileSize"
        :filename="fileName"
        :download-url="downloadable ? downloadUrl : null"
      />
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="File size">
        <InputNumber v-model="fileSize" />
      </FormRow>
      <FormRow label="File name">
        <InputText v-model="fileName" />
      </FormRow>
      <FormRow label="Downloadable">
        <RadioGroup v-model="downloadable" :horizontal="true">
          <Radio :value="true">Yes</Radio>
          <Radio :value="false">No</Radio>
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
import FormRow from 'tui/components/form/FormRow';
import InputNumber from 'tui/components/form/InputNumber';
import InputText from 'tui/components/form/InputText';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import FileCard from 'tui/components/file/FileCard';

export default {
  components: {
    FormRow,
    InputNumber,
    InputText,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
    FileCard,
  },

  data() {
    return {
      fileSize: 123546,
      fileName: 'monkey123.jpg',
      downloadable: false,
      downloadUrl: 'http://www.google.com',
      codeTemplate: `<FileCard
  :file-size="fileSize"
  :filename="fileName"
  :download-url="downloadable ? downloadUrl : null"
/>`,
      codeScript: `import FileCard from 'tui/components/file/FileCard';

export default {
  components: {
    FileCard,
  }
}`,
    };
  },
};
</script>
