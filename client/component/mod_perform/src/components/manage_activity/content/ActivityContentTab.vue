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
  @module mod_perform
-->

<template>
  <div class="tui-performManageActivityContent">
    <WorkflowSettings :activity="value" @change="updateActivity($event)" />

    <h3 class="tui-performManageActivityContent__heading">
      {{ $str('activity_content_tab_heading', 'mod_perform') }}
    </h3>

    <ActivityMultipleSectionToggle
      v-if="isDraft"
      :activity="value"
      @change="updateMultiSection($event)"
    />
    <div class="tui-performManageActivityContent__items">
      <ActivitySection
        v-for="(sectionState, i) in sectionStates"
        ref="activitySection"
        :key="sectionState.section.id"
        :auto-save="autoSave"
        :edit-mode="sectionState.editMode"
        :section="sectionState.section"
        :last-section="i === sectionStates.length - 1"
        :is-adding="isAdding"
        :sort-order="sectionState.sortOrder"
        :section-count="sectionStates.length"
        :relationships="relationships"
        :activity-name="value.name"
        :multiple-sections-enabled="value.settings.multisection"
        :activity-state="activityState"
        @input="updateSection($event, i)"
        @toggle-edit-mode="toggleSectionStateEditMode($event, i)"
        @mutation-success="$emit('mutation-success')"
        @mutation-error="$emit('mutation-error')"
        @add_above="addSectionAbove(i)"
        @add_below="addSectionBelow(i)"
        @delete-section="deleteSection(i)"
        @has-unsaved-changes="detectChanges"
      />
    </div>

    <ButtonIcon
      v-if="value.settings.multisection && isDraft"
      :disabled="isAdding"
      :aria-label="$str('add_section', 'mod_perform')"
      :text="$str('add_section', 'mod_perform')"
      @click="addSection(null)"
    >
      <AddIcon size="200" />
    </ButtonIcon>
  </div>
</template>

<script>
import ActivityMultipleSectionToggle from 'mod_perform/components/manage_activity/content/ActivityMultipleSectionToggle';
import ActivitySection from 'mod_perform/components/manage_activity/content/ActivitySection';
import AddIcon from 'tui/components/icons/Add';
import AddSectionMutation from 'mod_perform/graphql/add_section';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import RelationshipsQuery from 'mod_perform/graphql/relationships';
import WorkflowSettings from 'mod_perform/components/manage_activity/content/WorkflowSettings';
import { ACTIVITY_STATUS_DRAFT } from 'mod_perform/constants';

export default {
  components: {
    ActivityMultipleSectionToggle,
    ActivitySection,
    AddIcon,
    ButtonIcon,
    WorkflowSettings,
  },

  props: {
    value: {
      type: Object,
      required: true,
    },
    activityHasUnsavedChanges: Boolean,
  },

  data() {
    return {
      sectionStates: this.createSectionStates(this.value),
      relationships: [],
      isAdding: false,
    };
  },

  computed: {
    /**
     * Checks if section should auto-save on changes.
     *
     * @return {boolean}
     */
    autoSave() {
      return !this.value.settings.multisection;
    },

    /**
     * Are there any sections that have been edited without saving?
     */
    hasUnsavedChanges() {
      return (
        !this.autoSave &&
        this.sectionStates.some(section => section.editMode) &&
        this.$refs.activitySection.some(section => section.hasChanges)
      );
    },

    activityState() {
      return this.value.state_details;
    },

    /**
     * @return {Boolean}
     */
    isDraft() {
      return this.activityState.name === ACTIVITY_STATUS_DRAFT;
    },
  },

  mounted() {
    // Confirm navigation away if user is currently editing.
    window.addEventListener('beforeunload', this.unloadHandler);
  },

  beforeDestroy() {
    // Modal will no longer exist so remove the navigation warning.
    window.removeEventListener('beforeunload', this.unloadHandler);
  },

  methods: {
    /**
     * Updates an activity section.
     * @param {Object} updatedSection Section data
     * @param {int} sectionIndex Section index
     */
    updateSection(updatedSection, sectionIndex) {
      const sectionsCopy = this.value.sections.slice();
      sectionsCopy[sectionIndex] = updatedSection;

      const valueCopy = Object.assign({}, this.value, {
        sections: sectionsCopy,
      });

      this.updateActivity(valueCopy);
      this.updateSectionState(updatedSection, sectionIndex);
    },

    /**
     * Changes edit mode for a section state.
     * @param {Boolean} editMode Section data
     * @param {int} i Section index
     */
    toggleSectionStateEditMode(editMode, i) {
      this.sectionStates[i].editMode = editMode;
    },

    /**
     * Update a section state.
     * @param {Object} update Section data
     * @param {int} i Section index
     */
    updateSectionState(update, i) {
      this.sectionStates[i].section = update;
    },

    /**
     * Create section states from sections.
     * @param {Object} activity
     *
     * @return {Array}
     */
    createSectionStates(activity) {
      return activity.sections && activity.sections.length > 0
        ? activity.sections.map(section => {
            return this.createSectionState(section, false);
          })
        : [];
    },

    /**
     * Create section state for one section.
     * @param {Object} section
     * @param {Boolean} editMode
     *
     * @return {Object}
     */
    createSectionState(section, editMode) {
      return {
        editMode: editMode,
        sortOrder: section.sort_order,
        section: section,
      };
    },

    /**
     * Update activity multisection.
     * @param {Object} update
     */
    updateMultiSection(update) {
      this.sectionStates = this.createSectionStates(update);
      this.updateActivity(update);
      this.toggleSectionStateEditMode(update.settings.multisection, 0);
    },

    /**
     * Update activity.
     * @param {Object} update
     */
    updateActivity(update) {
      const newValue = Object.assign({}, this.value, update);
      this.$emit('input', newValue);
    },

    /**
     * Save changes.
     */
    async trySave() {
      try {
        const updatedActivity = this.value;
        this.updateActivity(updatedActivity);
        this.$emit('mutation-success');
      } catch (e) {
        this.$emit('mutation-error', e);
      }
    },

    /**
     * Adds a section above the given one.
     * @param {Int} sectionIndex
     */
    addSectionAbove(sectionIndex) {
      this.addSection(sectionIndex);
    },

    /**
     * Adds a section below the given one
     * @param {Int} sectionIndex Section index to add after
     */
    addSectionBelow(sectionIndex) {
      this.addSection(sectionIndex + 1);
    },

    /**
     * Add a new section at the end of the list
     * @return {Promise<void>}
     */
    async addSection(sectionIndex) {
      this.isAdding = true;
      const sectionAddBefore =
        sectionIndex !== null
          ? this.sectionStates[sectionIndex].sortOrder
          : null;

      try {
        const {
          data: {
            mod_perform_add_section: { section },
          },
        } = await this.$apollo.mutate({
          mutation: AddSectionMutation,
          variables: {
            input: {
              activity_id: this.value.id,
              add_before: sectionAddBefore,
            },
          },
          refetchAll: true,
        });

        if (sectionIndex !== null) {
          this.insertSectionAt(section, sectionIndex);
        } else {
          this.insertSectionAtEnd(section);
        }

        this.scrollToSection(section);
      } catch (e) {
        this.$emit('mutation-error', e);
      }

      this.isAdding = false;
    },

    /**
     * Go through all sections coming after the just added one and
     * increase the sortOrder
     * @param {Object} section
     * @param {Int} sectionIndex
     */
    insertSectionAt(section, sectionIndex) {
      // Go through all sections coming after the just added one and
      // increase the sortOrder
      this.sectionStates.forEach((sectionState, index) => {
        if (sectionState.sortOrder >= section.sort_order) {
          this.sectionStates[index].sortOrder++;
        }
      });

      // Add newly created section at the right spot
      this.sectionStates.splice(
        sectionIndex,
        0,
        this.createSectionState(section, true)
      );
    },

    /**
     * Insert a new section at the end of the list
     * @param {Object} section
     */
    insertSectionAtEnd(section) {
      this.sectionStates.push(this.createSectionState(section, true));
    },

    /**
     * Scroll to the section added at the index
     * @param section
     */
    scrollToSection(section) {
      this.$nextTick(() => {
        this.$refs.activitySection.forEach((sectionElement, index) => {
          if (sectionElement.sortOrder === section.sort_order) {
            this.$refs.activitySection[index].$el.scrollIntoView();
          }
        });
      });
    },

    /**
     * Displays a warning message if the user tries to navigate away without saving.
     * @param {Event} e
     * @returns {String|void}
     */
    unloadHandler(e) {
      if (!this.activityHasUnsavedChanges) {
        return;
      }

      // For older browsers that still show custom message.
      const discardUnsavedChanges = this.$str(
        'unsaved_changes_warning',
        'mod_perform'
      );
      e.preventDefault();
      e.returnValue = discardUnsavedChanges;
      return discardUnsavedChanges;
    },

    /**
     * delete section
     */
    deleteSection(sectionIndex) {
      this.sectionStates.splice(sectionIndex, 1);

      let updatedSections = this.sectionStates.map(sectionState => {
        return sectionState.section;
      });
      this.updateActivity({ sections: updatedSections });
    },

    detectChanges() {
      this.$emit('unsaved-changes', this.hasUnsavedChanges);
    },
  },

  apollo: {
    relationships: {
      query: RelationshipsQuery,
      variables() {
        return { activity_id: this.value.id };
      },
      update: data => data.mod_perform_relationships,
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_content_tab_heading",
      "add_section",
      "unsaved_changes_warning"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageActivityContent {
  & > * + * {
    margin: var(--gap-8) 0 0;
  }

  &__items {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__heading {
    @include tui-font-heading-small();
  }
}
</style>
