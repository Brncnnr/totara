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
    A modal trigger for calling a stand-alone modal component.

    <SamplesExample>
      <Button text="Show modal" @click="showModal" />
      <ModalPresenter :open="modalOpen" @request-close="modalResponse">
        <ExampleModal message="Hello" />
      </ModalPresenter>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ExampleModal from 'samples/components/sample_parts/tui/modal/ExampleModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    Button,
    ExampleModal,
    ModalPresenter,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      message: 'Are you sure you want to do this?',
      modalOpen: false,
      name: '',
      title: 'This is a simple confirmation modal.',
      codeTemplate: `<ModalPresenter :open="modalOpen" @request-close="modalResponse">
  <ExampleModal/>
</ModalPresenter>`,
      codeScript: `import ModalPresenter from 'tui/components/modal/ModalPresenter';
import CustomModal from '../CustomModal';

export default {
  components: {
    ConfirmationModal,
    CustomModal,
  }
}

methods: {
  showModal() {
    this.modalOpen = true;
  },

  modalResponse(e) {
    console.log(e.result);
    this.modalOpen = false;
  },
},`,
    };
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },

    modalResponse(e) {
      console.log(e.result);
      this.modalOpen = false;
    },
  },
};
</script>
