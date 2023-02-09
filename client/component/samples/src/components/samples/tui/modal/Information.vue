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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module samples
-->

<template>
  <div>
    An information modal, which forces the user to read information that is not
    actionable.

    <SamplesExample>
      <Button text="Show information modal" @click="showModal" />

      <InformationModal :open="modalOpen" :title="title" @close="modalClosed">
        {{ message }}
      </InformationModal>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{ id, label }" label="Title">
        <InputText :id="id" v-model="title" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Message">
        <InputText :id="id" v-model="message" :placeholder="label" />
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import FormRow from 'tui/components/form/FormRow';
import InformationModal from 'tui/components/modal/InformationModal';
import InputText from 'tui/components/form/InputText';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Button,
    FormRow,
    InformationModal,
    InputText,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      message:
        'You can not do this action yet. You must complete more things before you can.',
      modalOpen: false,
      title: 'This is a simple information modal.',
      codeScript: `import InformationModal from 'tui/components/modal/InformationModal';

export default {
  components: {
    InformationModal,
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },

    hideModal() {
      this.modalOpen = false;
    },

    modalClosed() {
      console.log('Closed');
      this.hideModal();
    },
  },
};
`,
    };
  },
  computed: {
    codeTemplate() {
      return `<InformationModal
  :open="modalOpen"
  :title="title"
  @close="modalClosed"
>
  {{ message }}
</InformationModal>`;
    },
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },

    hideModal() {
      this.modalOpen = false;
    },

    modalClosed() {
      console.log('User closed the modal');
      this.hideModal();
    },
  },
};
</script>
