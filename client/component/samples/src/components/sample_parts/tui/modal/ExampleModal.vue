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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module samples
-->

<template>
  <Modal size="normal" :aria-labelledby="$id('title')">
    <ModalContent
      :title="name ? 'Hello ' + name : 'What is your name?'"
      :title-id="$id('title')"
      :close-button="true"
    >
      <p>Content</p>
      <p><InputText v-model="name" placeholder="John Smith" /></p>
      <p><InputText v-model="name" placeholder="John Smith" /></p>

      <div>{{ message }}</div>

      <template v-slot:buttons>
        <OkCancelGroup @ok="confirm" @cancel="close" />
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import InputText from 'tui/components/form/InputText';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import OkCancelGroup from 'tui/components/buttons/OkCancelGroup';

export default {
  components: {
    InputText,
    Modal,
    ModalContent,
    OkCancelGroup,
  },

  props: {
    message: String,
  },

  data: function() {
    return {
      name: '',
    };
  },

  methods: {
    close() {
      this.$emit('request-close');
    },

    confirm() {
      this.$emit('request-close', { result: { name: this.name } });
    },
  },
};
</script>
