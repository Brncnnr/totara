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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module mod_perform
-->
<template>
  <Modal :aria-labelledby="$id('select-relationship-title')">
    <ModalContent
      v-if="participantSections.length > 0"
      :title="$str('select_relationship_to_respond_as_title', 'mod_perform')"
      :title-id="$id('select-relationship-title')"
      :close-button="false"
    >
      <p :id="$id('select-relationship-explanation')">
        {{
          $str(
            'select_relationship_to_respond_as_explanation',
            'mod_perform',
            subjectUser.fullname
          )
        }}
      </p>

      <RadioGroup
        v-model="relationshipToRespondAs"
        required
        :aria-labelledby="$id('select-relationship-explanation')"
      >
        <Radio
          v-for="respondAsOption in respondAsOptions"
          :key="respondAsOption.participant_instance.core_relationship.id"
          :value="respondAsOption.participant_instance.core_relationship.id"
          name="relationshipToRespondAs"
        >
          {{
            $str('select_relationship_to_respond_as_option', 'mod_perform', {
              relationship_name:
                respondAsOption.participant_instance.core_relationship.name,
              progress_status: getStatusText(respondAsOption),
            })
          }}
        </Radio>
      </RadioGroup>

      <template v-slot:buttons>
        <Button
          :styleclass="{ primary: true }"
          :text="$str('continue', 'core')"
          :disabled="!relationshipToRespondAs || relationshipConfirmed"
          @click="confirmRelationshipSelection"
        />
        <CancelButton
          :disabled="relationshipConfirmed"
          @click="$emit('input', false)"
        />
      </template>
    </ModalContent>
  </Modal>
</template>
<script>
import Button from 'tui/components/buttons/Button';
import CancelButton from 'tui/components/buttons/Cancel';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';

export default {
  components: {
    Button,
    CancelButton,
    Modal,
    ModalContent,
    Radio,
    RadioGroup,
  },

  props: {
    /**
     * The id of the logged in user.
     */
    currentUserId: {
      required: true,
      type: Number,
    },
    participantSections: {
      type: Array,
      required: true,
    },
    isForSection: {
      type: Boolean,
      required: true,
    },
    subjectUser: {
      type: Object,
      required: true,
    },
    viewUrl: {
      type: String,
      required: true,
    },
    value: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      relationshipToRespondAs: null,
      relationshipConfirmed: false,
    };
  },

  computed: {
    respondAsOptions() {
      if (this.participantSections.length === 0) {
        return [];
      }

      return this.filterToCurrentUser(this.participantSections);
    },

    /**
     * This will find the first section for the current user which
     * matches the selected relationship
     */
    selectedParticipantSection() {
      return this.getFirstSectionToParticipate(
        this.participantSections,
        this.relationshipToRespondAs
      );
    },
  },

  mounted() {
    // Make sure the first option available is preselected
    this.relationshipToRespondAs =
      this.respondAsOptions.length > 0
        ? this.respondAsOptions[0].participant_instance.core_relationship.id
        : null;
  },

  methods: {
    /**
     * Get the first section, if relationship id is supplied it will get the first section
     * for the user with the given relationship
     *
     * @param {Array} participantSections
     * @param {Int} relationship_id optional relationship,
     * @return {Object|Null} returns a participant_section object
     */
    getFirstSectionToParticipate(participantSections, relationship_id) {
      relationship_id =
        typeof relationship_id !== 'undefined' ? relationship_id : null;

      let foundSection = null;

      participantSections.forEach(participantSection => {
        const instance = participantSection.participant_instance;
        const isForCurrentUser = instance.is_for_current_user;

        // Early exit if not for current user.
        // If responses are anonymous there won't even be a core_relationship
        // on the participant instance unless it's for the current user.
        if (!isForCurrentUser) {
          return;
        }

        const instanceRelationshipId = instance.core_relationship.id;

        if (
          foundSection === null &&
          (relationship_id === null ||
            relationship_id === instanceRelationshipId)
        ) {
          foundSection = participantSection;
        }
      });

      return foundSection;
    },

    /**
     * When the relationship selection is confirmed redirect to the
     * participant instance of the relationship selected
     */
    confirmRelationshipSelection() {
      this.relationshipConfirmed = true;
      if (!this.selectedParticipantSection) {
        throw 'Selected section not found';
      }
      window.location = this.$url(this.viewUrl, {
        participant_section_id: this.selectedParticipantSection.id,
      });
    },

    /**
     * Get the localized status text for a particular user activity.
     *
     * @param {Object} participantSection
     * @returns {string}
     */
    getStatusText(participantSection) {
      let progressStatus = this.isForSection
        ? participantSection.progress_status
        : participantSection.participant_instance.progress_status;

      switch (progressStatus) {
        case 'NOT_STARTED':
          return this.$str('user_activities_status_not_started', 'mod_perform');
        case 'IN_PROGRESS':
          return this.$str('user_activities_status_in_progress', 'mod_perform');
        case 'COMPLETE':
          return this.$str('user_activities_status_complete', 'mod_perform');
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'user_activities_status_not_applicable_for_relationship_selector',
            'mod_perform'
          );
        default:
          return '';
      }
    },

    /**
     * Filter participant instances to only ones that belong to the logged in user.
     * This also makes sure only one record per relationship is returned.
     *
     * @param {Object[]} participantSections
     * @return {Object[]}
     */
    filterToCurrentUser(participantSections) {
      let relationships = [];
      return participantSections.filter(ps => {
        const isForCurrentUser = ps.participant_instance.is_for_current_user;

        // Early exit if not for current user.
        // If responses are anonymous there won't even be a core_relationship
        // on the participant instance unless it's for the current user.
        if (!isForCurrentUser) {
          return false;
        }

        const relationship = ps.participant_instance.core_relationship.name;
        if (relationships.indexOf(relationship) === -1) {
          relationships.push(relationship);
          return true;
        }
        return false;
      });
    },
  },
};
</script>
<lang-strings>
  {
    "mod_perform": [
      "select_relationship_to_respond_as_explanation",
      "select_relationship_to_respond_as_option",
      "select_relationship_to_respond_as_title",
      "user_activities_status_complete",
      "user_activities_status_in_progress",
      "user_activities_status_not_applicable_for_relationship_selector",
      "user_activities_status_not_started"
    ],
    "core": [
      "continue"
    ]
  }
</lang-strings>
