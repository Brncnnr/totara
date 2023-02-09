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
    A confirmation modal, to be applied when the user has triggered an action
    which is unchangeable or highly visible.

    <SamplesExample>
      <Button text="Show confirmation modal" @click="showModal" />

      <ConfirmationModal
        :open="modalOpen"
        :title="title"
        :confirm-button-text="
          useCustomConfirmText ? customConfirmText : undefined
        "
        :close-button="closeButton"
        :loading="loading"
        @confirm="modalConfirmed"
        @cancel="modalCancelled"
      >
        {{ message }}
      </ConfirmationModal>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow v-slot="{ id, label }" label="Title">
        <InputText :id="id" v-model="title" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Message">
        <InputText :id="id" v-model="message" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Use custom confirm button text">
        <Checkbox
          :id="id"
          v-model="useCustomConfirmText"
          :placeholder="label"
        />
      </FormRow>

      <FormRow
        v-if="useCustomConfirmText"
        v-slot="{ id, label }"
        label="Confirm button text"
      >
        <InputText :id="id" v-model="customConfirmText" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Has close button">
        <Checkbox :id="id" v-model="closeButton" :placeholder="label" />
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
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import Checkbox from 'tui/components/form/Checkbox';

export default {
  components: {
    Checkbox,
    Button,
    ConfirmationModal,
    FormRow,
    InputText,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      message: 'Are you sure you want to do this?',
      modalOpen: false,
      title: 'This is a simple confirmation modal.',
      customConfirmText: 'Delete',
      useCustomConfirmText: false,
      closeButton: false,
      loading: false,
      confirmButtonText: '',
      codeScript: `import ConfirmationModal from 'tui/components/modal/ConfirmationModal';

export default {
  components: {
    ConfirmationModal,
  },

  data() {
    return {
      loading: false,
    }
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },

    hideModal() {
      this.modalOpen = false;
      this.loading = false;
    },

    async modalConfirmed() {
      console.log('User confirmed the modal action');
      this.loading = true;
      setTimeout(() => this.hideModal(), 2000);
    },

    modalCancelled() {
      console.log('Cancelled');
      this.hideModal();
    },
  },
};
`,
    };
  },
  computed: {
    codeTemplate() {
      return `<ConfirmationModal
  :open="modalOpen"
  :title="title" ${
    this.useCustomConfirmText
      ? `
  :confirm-button-text="$str('confirm_activity_delete', 'mod_perform')"`
      : ''
  }
  :close-button="closeButton"
  :loading="loading"
  @confirm="modalConfirmed"
  @cancel="modalCancelled"
>
  {{ message }}
</ConfirmationModal>`;
    },
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },

    hideModal() {
      this.modalOpen = false;
      this.loading = false;
    },

    modalConfirmed() {
      console.log('User confirmed the modal action');
      this.loading = true;
      setTimeout(() => this.hideModal(), 2000);
    },

    modalCancelled() {
      console.log('User cancelled the modal action');
      this.hideModal();
    },
  },
};
</script>
