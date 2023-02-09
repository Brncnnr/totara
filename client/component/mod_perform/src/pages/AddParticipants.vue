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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performAddParticipants">
    <a :href="$url(goBackLink)">
      {{ $str('back_to_manage_participation', 'mod_perform') }}
    </a>

    <div class="tui-performAddParticipants__content">
      <PageHeading
        :title="$str('add_participants_page_title', 'mod_perform')"
      />

      <Loader :loading="$apollo.loading" />

      <ActivityParticipants
        v-if="subjectInstance"
        ref="form"
        :subject-instance="subjectInstance"
        :relationships="relationships"
        :require-input="false"
        :is-saving="isSaving"
        :validate="validate"
        @submit="showConfirmModal"
      >
        <template v-if="relationships.length === 0" v-slot:meta>
          <p>
            {{ $str('manual_participant_add_no_relationships', 'mod_perform') }}
          </p>
        </template>
        <template v-slot:buttons>
          <ActionLink
            :href="goBackLink"
            :text="$str('button_cancel', 'mod_perform')"
          />
        </template>
      </ActivityParticipants>
    </div>

    <ConfirmationModal
      :open="openConfirmationModal"
      :title="$str('manual_participant_add_confirmation_title', 'mod_perform')"
      :confirm-button-text="$str('button_create', 'mod_perform')"
      :loading="isSaving"
      @confirm="save"
      @cancel="closeConfirmModal"
    >
      <p>{{ confirmationMessage }}</p>
    </ConfirmationModal>
  </div>
</template>

<script>
// components
import ActionLink from 'tui/components/links/ActionLink';
import ActivityParticipants from 'mod_perform/components/user_activities/participant_selector/ActivityParticipants';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Loader from 'tui/components/loading/Loader';
import PageHeading from 'tui/components/layouts/PageHeading';
// util
import {
  RELATIONSHIP_SUBJECT,
  RELATIONSHIP_PERFORM_EXTERNAL,
} from 'mod_perform/constants';
import { notify } from 'tui/notifications';
import { redirectWithPost } from 'tui/dom/form';
// graphQL
import AddParticipantsMutation from 'mod_perform/graphql/add_participants';
import subjectInstanceForParticipationQuery from 'mod_perform/graphql/subject_instance_for_participation';

export default {
  components: {
    ActivityParticipants,
    ActionLink,
    ConfirmationModal,
    Loader,
    PageHeading,
  },

  props: {
    subjectInstanceId: {
      required: true,
      type: Number,
    },
    goBackLink: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      subjectInstance: null,
      relationships: [],
      selectedUsers: [],
      openConfirmationModal: false,
      isSaving: false,
    };
  },

  apollo: {
    subjectInstance: {
      query: subjectInstanceForParticipationQuery,
      variables() {
        return {
          subject_instance_id: this.subjectInstanceId,
        };
      },
      update({ mod_perform_subject_instance_for_participant: data }) {
        this.initialiseRelationships(data);
        return data;
      },
    },
  },

  computed: {
    /**
     * Show grammatically correct message based upon how many users were selected.
     *
     * @return {String}
     */
    confirmationMessage() {
      if (this.selectedUsers.length === 1) {
        return this.$str(
          'manual_participant_add_confirmation_message_singular',
          'mod_perform'
        );
      }

      return this.$str(
        'manual_participant_add_confirmation_message',
        'mod_perform',
        this.selectedUsers.length
      );
    },
  },

  methods: {
    /**
     * Confirm adding participants.
     * Queues participant users to be saved.
     *
     * @param {Object} data Form data
     */
    showConfirmModal(data) {
      this.selectedUsers = this.getParticipantsToSave(data);
      this.openConfirmationModal = true;
    },

    /**
     * Cancel adding participants.
     */
    closeConfirmModal() {
      this.selectedUsers = [];
      this.openConfirmationModal = false;
    },

    /**
     * Get and set the unique relationships defined in the sections for the activity.
     *
     * @param {Object} subjectInstance
     */
    initialiseRelationships(subjectInstance) {
      const relationships = {};
      subjectInstance.activity.sections
        .map(section =>
          section.section_relationships.map(
            sectionRelationship => sectionRelationship.core_relationship
          )
        )
        .forEach(sectionRelationships => {
          sectionRelationships.forEach(relationship => {
            if (
              relationship.idnumber !== RELATIONSHIP_SUBJECT &&
              relationship.idnumber !== RELATIONSHIP_PERFORM_EXTERNAL // For now we don't support external participants.
            ) {
              relationships[relationship.id] = relationship;
            }
          });
        });
      this.relationships = Object.values(relationships);
    },

    /**
     * Check the form upon saving and show a validation error if no users were selected.
     *
     * @param {Object} data Form data
     * @return {Object} Form errors
     */
    validate(data) {
      if (Object.values(data).some(users => users.length > 0)) {
        return {};
      }

      return {
        error: this.$str(
          'manual_participant_add_require_at_least_one',
          'mod_perform'
        ),
      };
    },

    /**
     * Send off the users to add to the server!
     */
    async save() {
      this.isSaving = true;
      try {
        const { data: result } = await this.$apollo.mutate({
          mutation: AddParticipantsMutation,
          variables: {
            input: {
              subject_instance_ids: [this.subjectInstanceId],
              participants: this.selectedUsers,
            },
          },
          refetchAll: false, // Don't refetch all the data again
        });
        this.$refs.form.hasChanges = false;
        redirectWithPost(this.goBackLink, {
          participant_instance_created_count:
            result.mod_perform_add_participants.participant_instances.length,
        });
      } catch (e) {
        notify({
          message: this.$str('toast_error_generic_update', 'mod_perform'),
          type: 'error',
        });
        this.isSaving = false;
        this.closeConfirmModal();
      }
    },

    /**
     * Arrange the users in sets of relationship and user for the graphQL mutation.
     *
     * @param {Array} data Form data
     * @return {Array} User data to save
     */
    getParticipantsToSave(data) {
      let toSave = [];
      data.forEach(relationship => {
        relationship.users.forEach(user => {
          toSave.push({
            core_relationship_id: relationship.relationship_id,
            participant_id: user.user_id,
          });
        });
      });
      return toSave;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "add_participants_page_title",
      "back_to_manage_participation",
      "button_cancel",
      "button_create",
      "manual_participant_add_confirmation_message",
      "manual_participant_add_confirmation_message_singular",
      "manual_participant_add_confirmation_title",
      "manual_participant_add_no_relationships",
      "manual_participant_add_require_at_least_one",
      "toast_error_generic_update"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performAddParticipants {
  & > * + * {
    margin-top: var(--gap-2);
  }

  &__content {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }
}
</style>
