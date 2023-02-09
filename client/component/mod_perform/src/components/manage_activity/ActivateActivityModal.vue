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
  @module mod_perform
-->

<template>
  <span>
    <slot name="trigger" :open="open" :loading="loading" />

    <ConfirmationModal
      :open="confirmActivateModalOpen"
      :title="$str('modal_activate_title', 'mod_perform')"
      :confirm-button-text="$str('activity_action_activate', 'mod_perform')"
      :loading="loading"
      @confirm="activateActivity"
      @cancel="closeConfirmActivationModal"
    >
      <p>
        {{ $str('modal_activate_message', 'mod_perform') }}
      </p>
      <p
        v-html="
          $str(
            'modal_activate_message_users',
            'mod_perform',
            usersToAssignCount
          )
        "
      />
      <p>
        {{ $str('modal_activate_message_question', 'mod_perform') }}
      </p>
    </ConfirmationModal>

    <InformationModal
      :open="draftNotReadyModalOpen"
      :title="$str('modal_can_not_activate_title', 'mod_perform')"
      @close="closeDraftNotReadyModal"
    >
      <p>{{ $str('modal_can_not_activate_message', 'mod_perform') }}</p>
      <ul>
        <li>{{ $str('activation_criteria_assignments', 'mod_perform') }}</li>
        <li>{{ $str('activation_criteria_elements', 'mod_perform') }}</li>
        <li>{{ $str('activation_criteria_relationships', 'mod_perform') }}</li>
        <li>{{ $str('activation_criteria_schedule', 'mod_perform') }}</li>
      </ul>
    </InformationModal>
  </span>
</template>

<script>
import activateActivityMutation from 'mod_perform/graphql/activate_activity';
import activityUsersToAssignCountQuery from 'mod_perform/graphql/activity_users_to_assign_count';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import InformationModal from 'tui/components/modal/InformationModal';
import { notify } from 'tui/notifications';

export default {
  components: {
    ConfirmationModal,
    InformationModal,
  },

  props: {
    activity: {
      required: true,
      type: Object,
    },
    triggerOpen: Boolean,
  },

  data() {
    return {
      confirmActivateModalOpen: false,
      draftNotReadyModalOpen: false,
      usersToAssignCount: 0,
      loading: false,
    };
  },

  watch: {
    triggerOpen() {
      if (this.triggerOpen) {
        this.open();
      }
    },

    loading() {
      this.$emit('update-loading', this.loading);
    },
  },

  methods: {
    /**
     * Show a modal based upon whether or not the activity can be activated.
     * Queries the server to check if the activity can be activated and how many users would be activated.
     */
    async open() {
      this.loading = true;
      try {
        const { data: result } = await this.$apollo.query({
          query: activityUsersToAssignCountQuery,
          variables: {
            activity_id: this.activity.id,
          },
          fetchPolicy: 'no-cache',
        });
        this.usersToAssignCount =
          result.mod_perform_activity_users_to_assign_count;

        if (this.usersToAssignCount != null) {
          this.openConfirmActivationModal();
        } else {
          this.openDraftNotReadyModal();
        }
      } catch (e) {
        this.showErrorNotification();
        this.$emit('refetch');
      }
      this.loading = false;
    },

    /**
     * Open the modal for confirming the activation of the activity.
     */
    openConfirmActivationModal() {
      this.confirmActivateModalOpen = true;
    },

    /**
     * Close the modal for confirming the activation of the activity.
     */
    closeConfirmActivationModal() {
      this.confirmActivateModalOpen = false;
      this.$emit('close-activate-modal', false);
    },

    /**
     * Open the info modal showing what steps are required for activating the activity.
     */
    openDraftNotReadyModal() {
      this.draftNotReadyModalOpen = true;
    },

    /**
     * Close the info modal showing what steps are required for activating the activity.
     */
    closeDraftNotReadyModal() {
      this.draftNotReadyModalOpen = false;
      this.$emit('close-activate-modal', false);
    },

    /**
     * Activate an activity
     */
    async activateActivity() {
      this.loading = true;
      try {
        await this.$apollo.mutate({
          mutation: activateActivityMutation,
          variables: {
            input: {
              activity_id: this.activity.id,
            },
          },
        });
        this.showSuccessNotification();
      } catch (e) {
        this.showErrorNotification();
      }
      this.$emit('refetch');
      this.$emit('unsaved-changes', false);
      this.closeConfirmActivationModal();
      this.loading = false;
    },

    /**
     * Show toast confirming activation of activity.
     */
    showSuccessNotification() {
      notify({
        message: this.$str(
          'toast_success_activity_activated',
          'mod_perform',
          this.activity.name
        ),
        type: 'success',
      });
    },

    /**
     * Show generic save/update error toast.
     */
    showErrorNotification() {
      notify({
        message: this.$str(
          'toast_error_generic_update',
          'mod_perform',
          this.activity.name
        ),
        type: 'error',
      });
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activation_criteria_assignments",
      "activation_criteria_elements",
      "activation_criteria_relationships",
      "activation_criteria_schedule",
      "activity_action_activate",
      "button_close",
      "modal_activate_message",
      "modal_activate_message_question",
      "modal_activate_message_users",
      "modal_activate_title",
      "modal_can_not_activate_message",
      "modal_can_not_activate_title",
      "participation_reporting",
      "toast_error_generic_update",
      "toast_success_activity_activated"
    ]
  }
</lang-strings>
