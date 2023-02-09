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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module totara_perform
-->

<template>
  <Component
    :is="autoSave ? 'div' : 'Card'"
    class="tui-performActivitySection"
    :no-border="autoSave"
    :class="[!autoSave && 'tui-performActivitySection__multiple']"
  >
    <div
      :class="{ 'tui-performActivitySection--editing': editMode && !autoSave }"
    >
      <Grid v-if="!autoSave && !editMode">
        <GridItem :units="isDraft ? 10 : 12">
          <h4 class="tui-performActivitySection__title">
            {{ savedSection.display_title }}
          </h4>
        </GridItem>
        <GridItem v-if="isDraft" :units="2">
          <div class="tui-performActivitySection__action-buttons">
            <EditIcon
              class="tui-performActivitySection__action-edit"
              :aria-label="$str('edit_section', 'mod_perform')"
              @click="enableEditing"
            />
            <Dropdown position="bottom-right">
              <template v-slot:trigger="{ toggle }">
                <MoreButton
                  :aria-label="$str('section_dropdown_menu', 'mod_perform')"
                  :no-padding="true"
                  @click="toggle"
                />
              </template>
              <DropdownItem :disabled="isAdding" @click="$emit('add_above')">
                {{ $str('section_action_add_above', 'mod_perform') }}
              </DropdownItem>
              <DropdownItem
                v-if="!lastSection"
                :disabled="isAdding"
                @click="$emit('add_below')"
              >
                {{ $str('section_action_add_below', 'mod_perform') }}
              </DropdownItem>
              <DropdownItem v-if="canDelete" @click="canDeleteSection">
                {{ $str('section_action_delete', 'mod_perform') }}
              </DropdownItem>
            </Dropdown>
          </div>
        </GridItem>
      </Grid>
      <InputText
        v-if="!autoSave && editMode"
        ref="titleInput"
        :value="title"
        :placeholder="$str('untitled_section', 'mod_perform')"
        :aria-label="$str('section_title', 'mod_perform')"
        :maxlength="TITLE_INPUT_MAX_LENGTH"
        @input="title = $event"
      />
      <div
        class="tui-performActivitySection__participant-groups"
        :class="{
          'tui-performActivitySection__participant-groups--editing': isEditing,
        }"
      >
        <Grid :stack-at="764">
          <GridItem
            :units="6"
            class="tui-performActivitySection__participant-group"
          >
            <h4 class="tui-performActivitySection__participant-heading">
              {{ $str('activity_participants_heading', 'mod_perform') }}
            </h4>
            <span
              v-if="(!autoSave && !editMode) || !isDraft"
              class="tui-performActivitySection__can-view-others-legend"
            >
              {{
                $str('activity_participant_view_other_responses', 'mod_perform')
              }}
            </span>

            <div
              v-if="hasAnsweringParticipants"
              class="tui-performActivitySection__participant-items"
            >
              <ActivitySectionRelationship
                v-for="participant in displayedAnsweringParticipantsSorted"
                :key="participant.core_relationship.id"
                :participant="participant"
                :editable="(autoSave || editMode) && isDraft"
                @participant-removed="removeDisplayedParticipant"
                @can-view-changed="updateParticipantData"
              />
            </div>

            <div v-else class="tui-performActivitySection__participant-info">
              {{
                $str(
                  isActive || (!autoSave && !editMode)
                    ? 'no_participants_added'
                    : 'need_to_add_participant',
                  'mod_perform'
                )
              }}
            </div>

            <ParticipantsPopover
              v-if="(autoSave || editMode) && isDraft"
              :relationships="relationships"
              :active-participants="displayedParticipants"
              @update-participants="updateDisplayedParticipants(false, $event)"
            />
          </GridItem>

          <GridItem
            :units="6"
            class="tui-performActivitySection__participant-group"
          >
            <h4 class="tui-performActivitySection__participant-heading">
              {{
                $str('activity_participants_view_only_heading', 'mod_perform')
              }}
            </h4>
            <div class="tui-performActivitySection__participant-items">
              <ActivitySectionRelationship
                v-for="participant in displayedViewOnlyParticipantsSorted"
                :key="participant.core_relationship.id"
                :participant="participant"
                is-view-only-participant
                :editable="(autoSave || editMode) && isDraft"
                @participant-removed="removeDisplayedParticipant"
              />
            </div>

            <div
              v-if="
                displayedViewOnlyParticipantsSorted.length === 0 &&
                  ((!autoSave && !editMode) || !isDraft)
              "
            >
              <span class="tui-performActivitySection__participant-info">
                {{ $str('no_participants_added', 'mod_perform') }}
              </span>
            </div>
            <ParticipantsPopover
              v-if="(autoSave || editMode) && isDraft"
              :relationships="relationships"
              is-view-only-participants
              :active-participants="displayedParticipants"
              @update-participants="updateDisplayedParticipants(true, $event)"
            />
          </GridItem>
        </Grid>
      </div>
    </div>
    <ButtonGroup
      v-if="!autoSave && editMode"
      class="tui-performActivitySection__saveButtons"
    >
      <Button
        :styleclass="{ primary: true, small: true }"
        :text="$str('activity_section_done', 'mod_perform')"
        :disabled="isSaving || !editMode || !hasChanges"
        @click="trySave"
      />
      <Button
        :text="$str('cancel', 'core')"
        :styleclass="{ small: true }"
        :disabled="isSaving || !editMode"
        @click="resetSectionChanges"
      />
    </ButtonGroup>
    <hr class="tui-performActivitySection__divider" />
    <div
      class="tui-performActivitySection__content"
      :class="{ 'tui-performActivitySection__content--autoSave': autoSave }"
    >
      <Grid :stack-at="700">
        <GridItem grows :units="9">
          <ActivitySectionElementSummary
            v-if="section.section_elements_summary"
            :elements-summary="section.section_elements_summary"
          />
        </GridItem>
        <GridItem grows :units="3">
          <div class="tui-performActivitySection__content-buttons">
            <Button
              :styleclass="{ small: true }"
              :text="
                isDraft
                  ? $str('edit_content_elements', 'mod_perform')
                  : $str('view_content_elements', 'mod_perform')
              "
              @click="openContentElementsView"
            />
          </div>
        </GridItem>
      </Grid>
    </div>

    <ConfirmationModal
      :open="deleteSectionModalOpen"
      :title="$str('modal_section_delete_title', 'mod_perform')"
      :confirm-button-text="$str('delete', 'core')"
      :loading="deleting"
      @confirm="deleteSection"
      @cancel="closeDeleteSectionModal"
    >
      <template>
        <p>{{ $str('modal_section_delete_message', 'mod_perform') }}</p>
      </template>
    </ConfirmationModal>

    <InformationModal
      :open="noAnsweringParticipantsModalOpen"
      :title="$str('can_not_edit_section_elements', 'mod_perform')"
      @close="noAnsweringParticipantsModalOpen = false"
    >
      {{ $str('section_participant_required', 'mod_perform') }}
    </InformationModal>

    <SectionDeletionModal
      :title="modalTitle"
      :description="modalDescription"
      :activity-sections="modalData"
      :open="canNotDeleteModalOpen"
      @close="closeCanNotDeleteModal"
    />
  </Component>
</template>

<script>
import ActivitySectionElementSummary from 'mod_perform/components/manage_activity/content/ActivitySectionElementSummary';
import ActivitySectionRelationship from 'mod_perform/components/manage_activity/content/ActivitySectionRelationship';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Card from 'tui/components/card/Card';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import DeleteSectionMutation from 'mod_perform/graphql/delete_section';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import EditIcon from 'tui/components/buttons/EditIcon';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import InformationModal from 'tui/components/modal/InformationModal';
import InputText from 'tui/components/form/InputText';
import MoreButton from 'tui/components/buttons/MoreIcon';
import ParticipantsPopover from 'mod_perform/components/manage_activity/content/ParticipantsPopover';
import SectionDeletionModal from 'mod_perform/components/manage_activity/content/DeletionValidationModal';

// Queries
import sectionDeletionValidationQuery from 'mod_perform/graphql/section_deletion_validation';
import UpdateSectionSettingsMutation from 'mod_perform/graphql/update_section_settings';

// Constants
import {
  ACTIVITY_STATUS_ACTIVE,
  ACTIVITY_STATUS_DRAFT,
} from 'mod_perform/constants';

/**
 * Reflects the maximum length of the field in the database.
 * @type {number}
 */
const TITLE_INPUT_MAX_LENGTH = 1024;

export default {
  components: {
    ActivitySectionElementSummary,
    ActivitySectionRelationship,
    Button,
    ButtonGroup,
    Card,
    ConfirmationModal,
    Dropdown,
    DropdownItem,
    EditIcon,
    Grid,
    GridItem,
    InformationModal,
    InputText,
    MoreButton,
    SectionDeletionModal,
    ParticipantsPopover,
  },

  props: {
    autoSave: {
      type: Boolean,
      default: false,
    },
    editMode: {
      type: Boolean,
      default: false,
    },
    section: {
      type: Object,
      required: true,
    },
    lastSection: {
      type: Boolean,
      required: true,
    },
    isAdding: {
      type: Boolean,
      required: true,
    },
    sortOrder: {
      type: Number,
      required: true,
    },
    relationships: {
      type: Array,
      required: true,
    },
    sectionCount: {
      type: Number,
      required: true,
    },
    activityName: {
      type: String,
      required: true,
    },
    multipleSectionsEnabled: {
      type: Boolean,
    },
    activityState: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      savedSection: this.section,
      displayedParticipants: this.getParticipantsFromSection(this.section),
      title: this.section.title,
      isSaving: false,
      TITLE_INPUT_MAX_LENGTH,
      deleteSectionModalOpen: false,
      deleting: false,
      canNotDeleteModalOpen: false,
      modalTitle: null,
      modalDescription: null,
      modalData: [],
      noAnsweringParticipantsModalOpen: false,
    };
  },

  computed: {
    /**
     * Are we editing details.
     */
    isEditing() {
      return !this.autoSave && this.editMode;
    },
    /**
     * Get saved participants.
     *
     * @return {Array}
     */
    savedParticipants() {
      return this.getParticipantsFromSection(this.savedSection);
    },

    /**
     * Has anything changed compared to last saved state?
     * Checks for difference between displayed & last saved participants arrays, and changes to the title.
     *
     * @return {Boolean}
     */
    hasChanges() {
      if (this.isNew) {
        return true;
      }

      if (this.title !== this.savedSection.title) {
        return true;
      }

      if (this.displayedParticipants.length !== this.savedParticipants.length) {
        return true;
      }

      return !this.displayedParticipants.every(value => {
        return this.savedParticipants.find(participant => {
          return (
            participant.core_relationship.id === value.core_relationship.id &&
            participant.can_view === value.can_view &&
            participant.can_answer === value.can_answer
          );
        });
      });
    },

    /**
     * Gets Sorted list of the displayed participants.
     *
     * @return {Array}
     */
    displayedAnsweringParticipantsSorted() {
      return this.displayedParticipantsSorted.filter(
        participant => participant.can_answer
      );
    },

    /**
     * Gets Sorted list of the displayed view-only participants.
     *
     * @return {Array}
     */
    displayedViewOnlyParticipantsSorted() {
      return this.displayedParticipantsSorted.filter(
        participant => !participant.can_answer
      );
    },

    /**
     * Gets Sorted list of the displayed answering participants.
     *
     * @return {Array}
     */
    displayedParticipantsSorted() {
      return this.displayedParticipants
        .slice()
        .sort(
          (a, b) =>
            a.core_relationship.sort_order - b.core_relationship.sort_order
        );
    },

    /**
     * Has this section just been created?
     * @return {Boolean}
     */
    isNew() {
      return (
        this.savedSection.raw_created_at === this.savedSection.raw_updated_at
      );
    },

    /**
     * We only allow deletion if there are multiple sections.
     *
     * @return {Boolean}
     */
    canDelete() {
      return this.sectionCount > 1;
    },

    /**
     * Has selected answering participants
     *
     * @return {Boolean}
     */
    hasAnsweringParticipants() {
      return this.displayedAnsweringParticipantsSorted.length > 0;
    },

    /**
     * Is the activity currently active
     *
     * @return {Boolean}
     */
    isActive() {
      return this.activityState.name === ACTIVITY_STATUS_ACTIVE;
    },

    /**
     * @return {Boolean}
     */
    isDraft() {
      return this.activityState.name === ACTIVITY_STATUS_DRAFT;
    },
  },

  watch: {
    // Focus on title input when activating edit mode.
    editMode(newValue, oldValue) {
      if (newValue && !oldValue) {
        this.$nextTick(() => {
          this.focusTitleInput();
        });
      }
    },

    section: function() {
      this.title = this.section.title;
      this.displayedParticipants = this.getParticipantsFromSection(
        this.section
      );
    },
  },

  mounted() {
    // Focus on title input when mounted in edit mode.
    if (this.editMode) {
      this.focusTitleInput();
    }
  },

  updated() {
    this.$emit('has-unsaved-changes');
  },

  methods: {
    /**
     * Gets section relationships.
     *
     * @return {Array}
     */
    getParticipantsFromSection(section) {
      if (section.section_relationships) {
        return section.section_relationships;
      }
      return [];
    },

    /**
     * Get section title.
     *
     * @return {string}
     */
    getTitle() {
      return this.section.title;
    },

    /**
     * Get section relationships.
     *
     * @return {Array}
     */
    getSectionRelationships() {
      return this.displayedParticipants.map(participant => {
        return {
          core_relationship_id: participant.core_relationship.id,
          can_view: participant.can_view,
          can_answer: participant.can_answer,
        };
      });
    },

    /**
     * Update section.
     */
    updateSection(update) {
      const newValue = Object.assign({}, this.savedSection, update);
      this.$emit('input', newValue);
    },

    /**
     * Update section title.
     */
    updateTitle(update) {
      this.title = update;
    },

    /**
     * Enable edit-mode on section.
     */
    enableEditing() {
      this.$emit('toggle-edit-mode', true);
    },

    /**
     * Disable edit-mode on section.
     */
    disableEditing() {
      this.$emit('toggle-edit-mode', false);
    },

    /**
     * Update the displayed participants.
     * @param {boolean} isViewOnly Are the checked participants "view-only"
     * @param {Array} checkedParticipants List of new participants
     */
    updateDisplayedParticipants(isViewOnly, checkedParticipants) {
      this.displayedParticipants = this.displayedParticipants.concat(
        checkedParticipants.map(participant => {
          return {
            can_answer: !isViewOnly,
            can_view: isViewOnly,
            core_relationship: participant,
          };
        })
      );
      if (this.autoSave) {
        this.trySave();
      }
    },

    /**
     * Update data of a participant.
     * @param {Object} participant
     */
    updateParticipantData(participant) {
      this.displayedParticipants = this.displayedParticipants.map(value => {
        return value.core_relationship.id === participant.core_relationship.id
          ? participant
          : value;
      });
      if (this.autoSave) {
        this.trySave();
      }
    },

    /**
     * Remove participant from displayed participant.
     * @param {Object} participant
     */
    removeDisplayedParticipant(participant) {
      this.displayedParticipants = this.displayedParticipants.filter(
        value => value.core_relationship.id !== participant.core_relationship.id
      );
      if (this.autoSave) {
        this.trySave();
      }
    },

    /**
     * Reset changes made in the section.
     */
    resetSectionChanges() {
      this.displayedParticipants = this.getParticipantsFromSection(
        this.savedSection
      );
      this.title = this.savedSection.title;
      this.disableEditing();
    },

    /**
     * Save section changes.
     */
    async trySave() {
      this.isSaving = true;

      try {
        const savedSection = await this.save();
        if (savedSection) {
          this.updateTitle(savedSection.section.title);
          this.updateSection(savedSection);
          this.$emit('mutation-success');
        } else {
          this.resetSectionChanges();
        }
      } catch (e) {
        this.displayedParticipants = this.getParticipantsFromSection(
          this.section
        );
        this.$emit('mutation-error', e);
      }
      this.isSaving = false;
      this.disableEditing();
    },

    /**
     * Mutation call to save changes.
     * @return {Object}
     */
    async save() {
      const { data: resultData } = await this.$apollo.mutate({
        mutation: UpdateSectionSettingsMutation,
        variables: {
          input: {
            section_id: this.section.id,
            title: this.title,
            relationships: this.getSectionRelationships(),
          },
        },
        refetchAll: false, // Don't refetch all the data again
      });

      const result = resultData.mod_perform_update_section_settings;

      this.savedSection = result.section;

      if (result.validation_info && !result.validation_info.can_delete) {
        this.modalTitle = result.validation_info.title;
        this.modalDescription = result.validation_info.reason.description;
        this.modalData = result.validation_info.reason.data;
        this.showCanNotDeleteModal();

        return null;
      }

      return result;
    },

    /**
     * Check if section has any element that is referenced by redisplay elements,
     * show can not delete modal if there is, otherwise show delete modal
     */
    async canDeleteSection() {
      const {
        data: { validation_info: result },
      } = await this.$apollo.query({
        query: sectionDeletionValidationQuery,
        variables: {
          input: { section_id: this.section.id },
        },
        fetchPolicy: 'no-cache',
      });

      if (result.can_delete) {
        this.showDeleteModal();
      } else {
        this.modalTitle = result.title;
        this.modalDescription = result.reason.description;
        this.modalData = result.reason.data;
        this.showCanNotDeleteModal();
      }
    },

    /**
     * Display the modal for confirming the deletion of the section.
     */
    showDeleteModal() {
      this.deleteSectionModalOpen = true;
    },

    /**
     * close the section delete modal
     */
    closeDeleteSectionModal() {
      this.deleteSectionModalOpen = false;
      this.deleting = false;
    },

    /**
     * delete a section
     */
    async deleteSection() {
      this.deleting = true;
      try {
        await this.$apollo.mutate({
          mutation: DeleteSectionMutation,
          variables: {
            input: {
              section_id: this.section.id,
            },
          },
        });
        this.$emit('delete-section');
        this.$emit('mutation-success');
      } catch (e) {
        this.$emit('mutation-error', e);
      }

      this.closeDeleteSectionModal();
    },

    /**
     * Focus on title input field
     */
    focusTitleInput() {
      this.$refs.titleInput.$el.focus();
    },

    /**
     * Show can not delete modal
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

    /**
     * Open content element view if requirements are met
     */
    openContentElementsView() {
      if (this.hasAnsweringParticipants || !this.isDraft) {
        window.location.href = this.$url(
          '/mod/perform/manage/activity/section.php',
          {
            section_id: this.section.id,
          }
        );
      } else {
        this.noAnsweringParticipantsModalOpen = true;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel",
      "delete"
    ],
    "mod_perform": [
      "activity_participant_view_other_responses",
      "activity_participants_heading",
      "activity_participants_view_only_heading",
      "activity_section_done",
      "can_not_edit_section_elements",
      "edit_content_elements",
      "edit_section",
      "modal_section_delete_message",
      "modal_section_delete_title",
      "need_to_add_participant",
      "no_participants_added",
      "section_action_add_above",
      "section_action_add_below",
      "section_action_delete",
      "section_dropdown_menu",
      "section_participant_required",
      "section_title",
      "unsaved_changes_warning",
      "untitled_section",
      "view_content_elements"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performActivitySection {
  &--editing {
    padding: var(--gap-4);
    border: solid var(--color-secondary) var(--border-width-normal);
  }

  &__title {
    margin: 0;
  }

  &__multiple {
    padding: var(--gap-4);
    & > * + * {
      margin-top: var(--gap-6);
    }
  }

  &.tui-card {
    display: block;
  }

  &__action-buttons {
    display: flex;
    justify-content: flex-end;
    .tui-iconBtn {
      min-width: auto;
    }
  }
  &__saveButtons {
    display: flex;
    justify-content: flex-end;
  }

  &__action-edit {
    min-width: auto;
  }

  &__divider {
    margin-top: var(--gap-8);
    margin-bottom: 0;
  }

  &__content {
    margin-top: var(--gap-4);

    &--autoSave {
      padding-bottom: var(--gap-4);
      border-bottom: var(--border-width-thin) solid var(--card-border-color);
    }
  }

  &__can-view-others-legend {
    display: block;
  }

  &__participant-heading {
    margin-top: var(--gap-2);
  }

  &__participant-items {
    & > * + * {
      margin-top: var(--gap-2);
    }
  }

  &__participant-groups {
    margin-top: var(--gap-2);
  }

  &__participant-group {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__participant-info {
    font-style: italic;
  }

  &__participant-heading {
    display: inline-block;
    margin-top: var(--gap-4);
    margin-bottom: 0;
    @include tui-font-heading-label();
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performActivitySection {
    &__can-view-others-legend {
      display: inline;
    }

    &__content-buttons {
      display: flex;
      justify-content: flex-end;
    }
  }
}
</style>
