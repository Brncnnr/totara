<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
  @module mod_perform
-->
<template>
  <ModalPresenter :open="open" @request-close="closeModal">
    <Modal
      :aria-labelledby="$id('question-element-preview-report-modal')"
      class="tui-elementResponseReportingViewReportConfirmModal"
      size="normal"
    >
      <ModalContent
        :title="$str('view_report_confirm_modal_title', 'mod_perform')"
        :title-id="$id('question-element-preview-report-modal')"
      >
        <p>
          {{ $str('view_report_confirm_modal_body', 'mod_perform') }}
        </p>

        <template v-slot:buttons>
          <Button
            :disabled="buttonsDisabled"
            :styleclass="{
              primary: true,
            }"
            :text="$str('button_view', 'mod_perform')"
            @click="viewReport"
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
    viewReportHref: {
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
    viewReport() {
      this.buttonsDisabled = true;

      window.location = this.viewReportHref;
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
    "button_view",
    "view_report_confirm_modal_body",
    "view_report_confirm_modal_title"
  ]
}
</lang-strings>
