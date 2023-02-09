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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_approval
-->

<template>
  <Modal :dismissable="dismissable">
    <ModalContent
      :title="$str('unrecoverable_application_error_title', 'mod_approval')"
    >
      <p
        v-text="$str('unrecoverable_application_error_message', 'mod_approval')"
      />
      <template v-slot:buttons>
        <Button
          :styleclass="{ primary: true }"
          :text="$str('refresh')"
          @click="doReload"
        />
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';

export default {
  components: {
    Button,
    Modal,
    ModalContent,
  },
  props: {
    dismissable: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    doReload() {
      location.reload();
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "refresh"
  ],
  "mod_approval": [
    "unrecoverable_application_error_message",
    "unrecoverable_application_error_title"
  ]
}
</lang-strings>
