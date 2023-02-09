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

  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performActivityActions">
    <a
      v-if="!activityIsDraft"
      :href="participationManagementUrl"
      :title="$str('manage_participation', 'mod_perform')"
      class="tui-performActivityActions__item"
    >
      <Users
        :alt="$str('manage_participation', 'mod_perform')"
        :title="$str('manage_participation', 'mod_perform')"
        size="200"
      />
    </a>
    <a
      v-if="activity.can_view_participation_reporting && !activityIsDraft"
      :href="participationReportingUrl"
      :title="$str('participation_reporting', 'mod_perform')"
      class="tui-performActivityActions__item"
    >
      <TasksIcon
        :alt="$str('participation_reporting', 'mod_perform')"
        :title="$str('participation_reporting', 'mod_perform')"
        size="200"
      />
    </a>

    <Dropdown
      v-if="activity.can_manage"
      position="bottom-right"
      class="tui-performActivityActions__item"
    >
      <template v-slot:trigger="{ toggle }">
        <MoreButton
          :no-padding="true"
          :aria-label="$str('activity_action_options', 'mod_perform')"
          @click="toggle"
        />
      </template>
      <DropdownItem
        v-if="activity.can_potentially_activate"
        @click="$refs.activateActivityModal.open()"
      >
        {{ $str('activity_action_activate', 'mod_perform') }}
      </DropdownItem>
      <DropdownItem v-if="activity.can_clone" @click="cloneActivity">
        {{ $str('activity_action_clone', 'mod_perform') }}
      </DropdownItem>
      <DropdownItem @click="canDeleteActivity">
        {{ $str('activity_action_delete', 'mod_perform') }}
      </DropdownItem>
    </Dropdown>

    <ActivateActivityModal
      v-if="activity.can_potentially_activate"
      ref="activateActivityModal"
      :activity="activity"
      @refetch="$emit('refetch')"
    />

    <ConfirmationModal
      :open="deleteModalOpen"
      :title="deleteConfirmationTitle"
      :confirm-button-text="$str('delete')"
      :loading="deleting"
      @confirm="deleteActivity"
      @cancel="closeDeleteModal"
    >
      <div v-for="(warning, i) in deleteModalWarnings" :key="i">
        <p>{{ warning.description }}</p>
        <ul>
          <li v-for="(warningItem, key) in warning.items" :key="key">
            <a v-if="warningItem.url" :href="warningItem.url" target="_blank">{{
              warningItem.item
            }}</a>
            <template v-else>{{ warningItem.item }}</template>
          </li>
        </ul>
      </div>

      <template v-if="activityIsDraft">
        <p>{{ $str('modal_delete_draft_message', 'mod_perform') }}</p>
      </template>
      <template v-else>
        <p>{{ $str('modal_delete_message', 'mod_perform') }}</p>
        <p>
          <strong>
            {{
              $str('modal_delete_message_data_recovery_warning', 'mod_perform')
            }}
          </strong>
        </p>
      </template>
      <p>{{ $str('modal_delete_confirmation_line', 'mod_perform') }}</p>
    </ConfirmationModal>

    <ActivityDeletionModal
      :title="modalTitle"
      :description="modalDescription"
      :activity-sections="modalData"
      :open="canNotDeleteModalOpen"
      @close="closeCanNotDeleteModal"
    />
  </div>
</template>

<script>
import ActivateActivityModal from 'mod_perform/components/manage_activity/ActivateActivityModal';
import Users from 'tui/components/icons/Users';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import MoreButton from 'tui/components/buttons/MoreIcon';
import ActivityDeletionModal from 'mod_perform/components/manage_activity/content/DeletionValidationModal';
import TasksIcon from 'tui/components/icons/Tasks';
import { notify } from 'tui/notifications';
import { ACTIVITY_STATUS_DRAFT } from 'mod_perform/constants';

// Queries
import activateCloneMutation from 'mod_perform/graphql/clone_activity';
import activateDeleteMutation from 'mod_perform/graphql/delete_activity';
import activityDeletionValidationQuery from 'mod_perform/graphql/activity_deletion_validation';

export default {
  components: {
    ActivateActivityModal,
    Users,
    ConfirmationModal,
    Dropdown,
    DropdownItem,
    MoreButton,
    ActivityDeletionModal,
    TasksIcon,
  },

  props: {
    activity: {
      required: true,
      type: Object,
    },
  },

  data() {
    return {
      deleteModalOpen: false,
      deleting: false,
      canNotDeleteModalOpen: false,
      modalTitle: null,
      modalDescription: null,
      modalData: [],
      deleteModalWarnings: [],
    };
  },

  computed: {
    /**
     * Is the activity in draft state.
     *
     * @return {boolean}
     */
    activityIsDraft() {
      return this.activity.state_details.name === ACTIVITY_STATUS_DRAFT;
    },

    /**
     * Activity state dependant delete confirmation title.
     *
     * @return {string}
     */
    deleteConfirmationTitle() {
      if (this.activityIsDraft) {
        return this.$str('modal_delete_draft_title', 'mod_perform');
      }

      return this.$str('modal_delete_title', 'mod_perform');
    },

    /**
     * Get the url to the participation management
     *
     * @return {string}
     */
    participationManagementUrl() {
      return this.$url(
        '/mod/perform/manage/participation/subject_instances.php',
        {
          activity_id: this.activity.id,
        }
      );
    },

    /**
     * Get the url to the participation tracking
     *
     * @return {string}
     */
    participationReportingUrl() {
      return this.$url('/mod/perform/reporting/participation/index.php', {
        activity_id: this.activity.id,
      });
    },
  },

  methods: {
    /**
     * Clones the activity.
     */
    async cloneActivity() {
      try {
        const clonedActivity = await this.$apollo.mutate({
          mutation: activateCloneMutation,
          variables: {
            input: {
              activity_id: this.activity.id,
            },
          },
        });

        this.$emit('activity-cloned', {
          clone: clonedActivity.data.mod_perform_clone_activity.activity,
          id: this.activity.id,
        });
      } catch (e) {
        this.showErrorNotification();
      }
    },

    /**
     * Close the modal for confirming the deletion of the activity.
     */
    closeDeleteModal() {
      this.deleteModalOpen = false;
      this.deleting = false;
    },

    /**
     * Deletes the activity.
     */
    async deleteActivity() {
      this.deleting = true;

      try {
        await this.$apollo.mutate({
          mutation: activateDeleteMutation,
          variables: {
            input: {
              activity_id: this.activity.id,
            },
          },
        });

        this.showDeleteSuccessNotification();
      } catch (e) {
        this.showErrorNotification();
      }

      this.$emit('refetch');
      this.closeDeleteModal();
    },

    /**
     * Check if activity has any element that is referenced by redisplay elements,
     * show can not delete modal if there is, otherwise show delete modal
     */
    async canDeleteActivity() {
      const {
        data: { validation_info: result },
      } = await this.$apollo.query({
        query: activityDeletionValidationQuery,
        variables: {
          input: { activity_id: this.activity.id },
        },
        fetchPolicy: 'no-cache',
      });

      if (result.can_delete) {
        this.deleteModalWarnings = result.warnings;
        this.showDeleteModal();
      } else {
        this.modalTitle = result.title;
        this.modalDescription = result.reason.description;
        this.modalData = result.reason.data;
        this.showCanNotDeleteModal();
      }
    },

    /**
     * Display the modal for confirming the deletion of the activity.
     */
    showDeleteModal() {
      this.deleteModalOpen = true;
    },

    showDeleteSuccessNotification() {
      let message = this.$str('toast_success_activity_deleted', 'mod_perform');

      if (this.activityIsDraft) {
        message = this.$str(
          'toast_success_draft_activity_deleted',
          'mod_perform'
        );
      }

      notify({
        message,
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

    /**
     * Display can not delete modal
     */
    showCanNotDeleteModal() {
      this.canNotDeleteModalOpen = true;
    },

    /**
     * Hide can not delete modal
     */
    closeCanNotDeleteModal() {
      this.canNotDeleteModalOpen = false;
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
      "activity_action_activate",
      "activity_action_clone",
      "activity_action_delete",
      "activity_action_options",
      "manage_participation",
      "modal_delete_confirmation_line",
      "modal_delete_draft_message",
      "modal_delete_draft_title",
      "modal_delete_message",
      "modal_delete_message_data_recovery_warning",
      "modal_delete_title",
      "participation_reporting",
      "toast_error_generic_update",
      "toast_success_activity_deleted",
      "toast_success_draft_activity_deleted"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performActivityActions {
  display: flex;
  justify-content: flex-start;

  &__item {
    margin-right: var(--gap-2);
    padding: 0 var(--gap-1);
  }
}

@media screen and (min-width: $tui-screen-xs) {
  .tui-performActivityActions {
    justify-content: flex-end;

    &__item {
      margin-right: 0;
    }
  }
}
</style>
