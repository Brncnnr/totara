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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module totara_perform
-->

<template>
  <ModalPresenter :open="modalOpen" @request-close="modalClose">
    <Modal size="normal" :aria-labelledby="$id('report_open_close')">
      <ModalContent
        :title="
          $str(
            isOpen
              ? 'subject_instance_closed_title'
              : 'subject_instance_reopen_title',
            'mod_perform'
          )
        "
        :title-id="$id('report_open_close')"
      >
        <template v-if="!isOpen">
          <p>
            {{ $str('subject_instance_reopen_message_line1', 'mod_perform') }}
          </p>

          <p>
            {{ $str('subject_instance_reopen_message_line2', 'mod_perform') }}
          </p>

          <p>
            {{ $str('subject_instance_reopen_message_line3', 'mod_perform') }}
          </p>
        </template>
        <template v-else>
          <p>
            {{ $str('subject_instance_closed_message_line1', 'mod_perform') }}
          </p>

          <p>
            {{ $str('subject_instance_closed_message_line2', 'mod_perform') }}
          </p>
        </template>
        <template v-slot:buttons>
          <ButtonGroup>
            <Button
              :styleclass="{ primary: true }"
              :text="
                $str(isOpen ? 'button_close' : 'button_reopen', 'mod_perform')
              "
              @click="changeAvailability()"
            />
            <Button
              :text="$str('button_cancel', 'mod_perform')"
              @click="modalClose()"
            />
          </ButtonGroup>
        </template>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>
<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import ChangeSubjectInstanceMutation from 'mod_perform/graphql/manually_change_subject_instance';
import { notify } from 'tui/notifications';
import { redirectWithPost } from 'tui/dom/form';
import {
  INSTANCE_AVAILABILITY_STATUS_OPEN,
  INSTANCE_AVAILABILITY_STATUS_CLOSED,
} from 'mod_perform/constants';

export default {
  components: {
    Button,
    ButtonGroup,
    Modal,
    ModalContent,
    ModalPresenter,
  },
  props: {
    modalOpen: {
      type: Boolean,
    },
    subjectInstanceId: {
      type: String,
    },
    isOpen: {
      type: Boolean,
    },
    reportType: {
      type: String,
    },
  },
  methods: {
    modalClose() {
      this.$emit('modal-close');
    },
    /**
     * Show generic save/update error toast.
     */
    showErrorNotification() {
      notify({
        message: this.$str('toast_error_generic_update', 'mod_perform'),
        type: 'error',
      });
    },
    /**
     * Change availability status
     */
    async changeAvailability() {
      try {
        let status = this.isOpen
          ? INSTANCE_AVAILABILITY_STATUS_CLOSED
          : INSTANCE_AVAILABILITY_STATUS_OPEN;
        await this.$apollo.mutate({
          mutation: ChangeSubjectInstanceMutation,
          variables: {
            input: {
              subject_instance_id: this.subjectInstanceId,
              availability: status,
            },
          },
        });
        this.$emit('modal-close');
        redirectWithPost(window.location, {
          is_open: !this.isOpen,
          report_type: this.reportType,
        });
      } catch (e) {
        this.showErrorNotification();
      }
    },
  },
};
</script>
<lang-strings>
  {
  "mod_perform": [
    "button_cancel",
    "button_close",
    "button_reopen",
    "subject_instance_closed_message_line1",
    "subject_instance_closed_message_line2",
    "subject_instance_closed_title",
    "subject_instance_reopen_title",
    "subject_instance_reopen_message_line1",
    "subject_instance_reopen_message_line2",
    "subject_instance_reopen_message_line3",
    "toast_error_generic_update"
  ]
  }
</lang-strings>
