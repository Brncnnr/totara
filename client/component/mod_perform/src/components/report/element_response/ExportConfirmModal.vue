<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module mod_perform
-->

<template>
  <ModalPresenter :open="open" @request-close="closeModal">
    <Modal
      :aria-labelledby="$id('question-element-preview-modal')"
      class="tui-elementResponseReportingExportConfirmModal"
      size="normal"
    >
      <ModalContent
        :title="$str('export_confirm_modal_title', 'mod_perform')"
        :title-id="$id('element-response-reporting-export-confirm-modal')"
      >
        <p>
          {{ $str('export_confirm_modal_type', 'mod_perform', exportType) }}
        </p>

        <p>
          {{ $str('export_confirm_modal_body', 'mod_perform') }}
        </p>

        <template v-slot:buttons>
          <Button
            :disabled="buttonsDisabled"
            :styleclass="{
              primary: true,
            }"
            :text="$str('export', 'mod_perform')"
            @click="doExport"
          />
          <Button
            :disabled="buttonsDisabled"
            :text="$str('button_cancel', 'mod_perform')"
            @click="closeModal"
          />
        </template>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';

export default {
  components: {
    Button,
    Modal,
    ModalContent,
    ModalPresenter,
  },
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    exportHref: {
      type: String,
      required: true,
    },
    exportType: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      buttonsDisabled: false,
    };
  },
  methods: {
    doExport() {
      this.buttonsDisabled = true;

      window.location = this.exportHref;
      this.closeModal();

      this.buttonsDisabled = false;
    },
    closeModal() {
      this.$emit('request-close');
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "button_cancel",
    "export",
    "export_confirm_modal_body",
    "export_confirm_modal_title",
    "export_confirm_modal_type"
  ]
}
</lang-strings>
