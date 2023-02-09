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
  <ConfirmationModal
    :open="deleteModalOpen"
    :title="$str('modal_participant_instance_delete_title', 'mod_perform')"
    :confirm-button-text="$str('delete')"
    :loading="deleting"
    @confirm="deleteParticipantInstance"
    @cancel="closeDeleteModal"
  >
    <p>
      {{ $str('modal_participant_instance_delete_message', 'mod_perform') }}
    </p>
    <p>
      {{
        $str(
          'modal_participant_instance_delete_confirmation_line',
          'mod_perform'
        )
      }}
    </p>
  </ConfirmationModal>
</template>

<script>
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import { notify } from 'tui/notifications';
import { redirectWithPost } from 'tui/dom/form';
// Mutation query
import DeleteParticipantInstanceMutation from 'mod_perform/graphql/manually_delete_participant_instance';

export default {
  components: {
    ConfirmationModal,
  },
  props: {
    deleteModalOpen: {
      type: Boolean,
    },
    participantInstanceId: {
      type: String,
    },
    reportType: {
      type: String,
    },
  },

  data() {
    return {
      deleting: false,
    };
  },

  methods: {
    /**
     * Close the modal for confirming the deletion of the participant instance.
     */
    closeDeleteModal() {
      this.deleting = false;
      this.$emit('modal-close');
    },

    /**
     * Deletes the participant instance.
     */
    async deleteParticipantInstance() {
      this.deleting = true;

      try {
        await this.$apollo.mutate({
          mutation: DeleteParticipantInstanceMutation,
          variables: {
            input: {
              participant_instance_id: this.participantInstanceId,
            },
          },
        });

        this.closeDeleteModal();
        redirectWithPost(window.location, {
          is_deleted: true,
          report_type: this.reportType,
        });
      } catch (e) {
        this.showErrorNotification();
      }
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
  },
};
</script>

<lang-strings>
{
"core": [
"delete"
],
"mod_perform": [
"modal_participant_instance_delete_title",
"modal_participant_instance_delete_message",
"modal_participant_instance_delete_confirmation_line",
"toast_error_generic_update"
]
}
</lang-strings>
