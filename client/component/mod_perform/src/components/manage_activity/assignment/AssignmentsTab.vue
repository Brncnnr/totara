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
  <div class="tui-performManageActivityAssignmentsForm">
    <div class="tui-performManageActivityAssignmentsForm__heading">
      <h3 class="tui-performManageActivityAssignmentsForm__heading-title">
        {{ $str('user_group_assignment_title', 'mod_perform') }}
      </h3>

      <!-- Drop down for adding groups -->
      <div class="tui-performManageActivityAssignmentsForm__heading-add">
        <Dropdown :separator="true" :position="dropdownPosition">
          <template v-slot:trigger="{ toggle, isOpen }">
            <Button
              :aria-expanded="isOpen ? 'true' : 'false'"
              :caret="true"
              :styleclass="{ primary: true }"
              :text="$str('user_group_assignment_add_group', 'mod_perform')"
              @click="toggle"
            />
          </template>
          <DropdownItem @click="openAdder('aud')">
            {{ $str('user_group_assignment_group_cohort', 'mod_perform') }}
          </DropdownItem>
          <DropdownItem
            v-if="track && track.can_assign_organisations"
            @click="openAdder('org')"
          >
            {{
              $str('user_group_assignment_group_organisation', 'mod_perform')
            }}
          </DropdownItem>
          <DropdownItem
            v-if="track && track.can_assign_positions"
            @click="openAdder('pos')"
          >
            {{ $str('user_group_assignment_group_position', 'mod_perform') }}
          </DropdownItem>
          <DropdownItem @click="openAdder('ind')">
            {{ $str('user_group_assignment_group_individual', 'mod_perform') }}
          </DropdownItem>
        </Dropdown>
      </div>
    </div>

    <AudienceAdder
      :context-id="activityContextId"
      :existing-items="addedIds.aud"
      :open="isOpen.aud"
      :show-loading-btn="showAdderLoadingBtn"
      @add-button-clicked="showAdderLoadingBtn = true"
      @added="
        selection =>
          updateSelectionFromAdder(selection, addedIds.aud, enums.aud)
      "
      @cancel="closeAdder('aud')"
    />

    <IndividualAdder
      :existing-items="addedIds.ind"
      :open="isOpen.ind"
      :show-loading-btn="showAdderLoadingBtn"
      @add-button-clicked="showAdderLoadingBtn = true"
      @added="
        selection =>
          updateSelectionFromAdder(selection, addedIds.ind, enums.ind)
      "
      @cancel="closeAdder('ind')"
    />

    <OrganisationAdder
      :existing-items="addedIds.org"
      :open="isOpen.org"
      :show-loading-btn="showAdderLoadingBtn"
      @add-button-clicked="showAdderLoadingBtn = true"
      @added="
        selection =>
          updateSelectionFromAdder(selection, addedIds.org, enums.org)
      "
      @cancel="closeAdder('org')"
    />

    <PositionAdder
      :existing-items="addedIds.pos"
      :open="isOpen.pos"
      :show-loading-btn="showAdderLoadingBtn"
      @add-button-clicked="showAdderLoadingBtn = true"
      @added="
        selection =>
          updateSelectionFromAdder(selection, addedIds.pos, enums.pos)
      "
      @cancel="closeAdder('pos')"
    />

    <div class="tui-performManageActivityAssignmentsForm__assigned">
      <!-- Initial loading display -->
      <AssignmentsGroup
        v-if="$apollo.queries.trackSettings.loading"
        :assignments="[]"
        title=""
        :updating="true"
      />

      <!-- No assignments display -->
      <div v-else-if="!assignments.length && !updatingAssignmentGroup">
        {{ $str('user_group_assignment_no_users', 'mod_perform') }}
      </div>

      <!-- Individuals display -->
      <AssignmentsIndividualGroup
        v-if="
          individualGroup.length > 0 || updatingAssignmentGroup === enums.ind
        "
        :assignments="individualGroup"
        :title="
          $str('user_group_assignment_group_individual_plural', 'mod_perform')
        "
        :updating="updatingAssignmentGroup === enums.ind"
        @remove="
          showRemoveConfirmationModal(
            $event.assignmentType,
            $event.groupId,
            $event.groupType
          )
        "
      />

      <!-- Audiences display -->
      <AssignmentsGroup
        v-if="audienceGroup.length > 0 || updatingAssignmentGroup === enums.aud"
        :assignments="audienceGroup"
        :title="
          $str('user_group_assignment_group_cohort_plural', 'mod_perform')
        "
        :updating="updatingAssignmentGroup === enums.aud"
        @remove="
          showRemoveConfirmationModal(
            $event.assignmentType,
            $event.groupId,
            $event.groupType
          )
        "
      />

      <!-- Organisation display -->
      <AssignmentsGroup
        v-if="
          organisationGroup.length > 0 || updatingAssignmentGroup === enums.org
        "
        :assignments="organisationGroup"
        :title="
          $str('user_group_assignment_group_organisation_plural', 'mod_perform')
        "
        :updating="updatingAssignmentGroup === enums.org"
        @remove="
          showRemoveConfirmationModal(
            $event.assignmentType,
            $event.groupId,
            $event.groupType
          )
        "
      />

      <!-- Position display -->
      <AssignmentsGroup
        v-if="positionGroup.length > 0 || updatingAssignmentGroup === enums.pos"
        :assignments="positionGroup"
        :title="
          $str('user_group_assignment_group_position_plural', 'mod_perform')
        "
        :updating="updatingAssignmentGroup === enums.pos"
        @remove="
          showRemoveConfirmationModal(
            $event.assignmentType,
            $event.groupId,
            $event.groupType
          )
        "
      />
    </div>

    <div class="tui-performManageActivityAssignmentsForm__settings">
      <ManageRelationshipParticipation
        :settings="activity.settings"
        :is-saving="relationshipParticipationIsSaving"
        @update="updateRelationshipParticipation"
      />

      <ParticipantSelection
        :activity="activity"
        :manual-relationship-options="manualRelationshipOptions"
        @update="updateManualRelationships"
      />
    </div>

    <ConfirmationModal
      :title="confirmationModalTitle"
      :confirm-button-text="
        $str('user_group_assignment_confirm_modal_remove', 'mod_perform')
      "
      :open="confirmationModalOpen"
      @confirm="removeAssignment"
      @cancel="hideRemoveConfirmationModal"
    >
      <p>
        {{ confirmationModalMessage }}
      </p>
    </ConfirmationModal>
  </div>
</template>

<script>
import AssignmentsGroup from 'mod_perform/components/manage_activity/assignment/AssignmentsGroup';
import AssignmentsIndividualGroup from 'mod_perform/components/manage_activity/assignment/AssignmentsIndividualGroup';
import AudienceAdder from 'tui/components/adder/AudienceAdder';
import Button from 'tui/components/buttons/Button';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import IndividualAdder from 'tui/components/adder/IndividualAdder';
import ManageRelationshipParticipation from 'mod_perform/components/manage_activity/assignment/ManageRelationshipParticipation';
import OrganisationAdder from 'tui/components/adder/OrganisationAdder';
import ParticipantSelection from 'mod_perform/components/manage_activity/assignment/ParticipantSelection';
import PositionAdder from 'tui/components/adder/PositionAdder';
import { ACTIVITY_STATUS_ACTIVE } from 'mod_perform/constants';
//GraphQL
import TrackSettingsQuery from 'mod_perform/graphql/default_track_settings';
import AddTrackAssignmentMutation from 'mod_perform/graphql/add_track_assignments';
import ManualRelationshipOptionsQuery from 'mod_perform/graphql/manual_relationship_selector_options';
import RemoveTrackAssignmentMutation from 'mod_perform/graphql/remove_track_assignments';
import SetManualParticipantSelectorRoles from 'mod_perform/graphql/set_manual_relationship_selector_roles';
import OverrideGlobalParticipationSettings from 'mod_perform/graphql/override_global_participation_settings';

export default {
  components: {
    AssignmentsGroup,
    AssignmentsIndividualGroup,
    AudienceAdder,
    Button,
    ConfirmationModal,
    Dropdown,
    DropdownItem,
    IndividualAdder,
    ManageRelationshipParticipation,
    OrganisationAdder,
    ParticipantSelection,
    PositionAdder,
  },

  props: {
    activity: {
      type: Object,
      required: true,
    },
    activityContextId: Number,
    /**
     * This property had been deprecated, it is no longer used.
     * Use activity.id instead.
     * @deprecated Since Totara 16
     */
    activityId: {
      type: Number,
      required: true,
    },
    activityState: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      // Added assignment Ids for each type
      addedIds: {
        aud: [],
        ind: [],
        org: [],
        pos: [],
      },
      adminEnum: 1,
      assignments: [],
      assignmentToRemove: null,
      confirmationModalOpen: false,
      // Type enums
      enums: {
        aud: 1,
        ind: 4,
        org: 2,
        pos: 3,
      },
      // Controls which adder is open
      isOpen: {
        aud: false,
        ind: false,
        org: false,
        pos: false,
      },
      manualRelationshipOptions: null,
      relationshipParticipationIsSaving: {
        override: false,
        assign: false,
        close: false,
      },
      track: null,
      trackSettings: null,
      // ID of group currently being updated
      updatingAssignmentGroup: false,
      showAdderLoadingBtn: false,
    };
  },

  computed: {
    /**
     * Get the position of the dropdown menu based upon the viewport size.
     */
    dropdownPosition() {
      return screen.width > 600 ? 'bottom-right' : 'bottom-left';
    },

    isActive() {
      return this.activityState == ACTIVITY_STATUS_ACTIVE;
    },

    confirmationModalTitle() {
      const confirmationModalTitleString = () => {
        if (this.assignmentToRemove === null) {
          return 'user_group_assignment_confirm_remove_title';
        }
        return this.assignmentToRemove.groupType === this.enums.ind
          ? 'user_individual_assignment_confirm_remove_title'
          : 'user_group_assignment_confirm_remove_title';
      };
      return this.$str(confirmationModalTitleString(), 'mod_perform');
    },

    confirmationModalMessage() {
      const confirmationModalMessageString = () => {
        if (this.assignmentToRemove === null) {
          return 'user_group_assignment_confirm_remove_draft';
        }

        if (this.assignmentToRemove.groupType === this.enums.ind) {
          return this.isActive
            ? 'user_individual_assignment_confirm_remove_active'
            : 'user_individual_assignment_confirm_remove_draft';
        }

        return this.isActive
          ? 'user_group_assignment_confirm_remove_active'
          : 'user_group_assignment_confirm_remove_draft';
      };
      return this.$str(confirmationModalMessageString(), 'mod_perform');
    },

    audienceGroup() {
      return this.assignments.filter(
        item => item.group.type === this.enums.aud
      );
    },

    individualGroup() {
      return this.assignments.filter(
        item => item.group.type === this.enums.ind
      );
    },

    organisationGroup() {
      return this.assignments.filter(
        item => item.group.type === this.enums.org
      );
    },

    positionGroup() {
      return this.assignments.filter(
        item => item.group.type === this.enums.pos
      );
    },
  },

  watch: {
    /**
     * Extracts the assignments from the associated track in this page.
     */
    track() {
      if (this.track) {
        this.assignments = this.track.assignments;
        this.addedIds.aud = this.assignments
          .filter(assignment => assignment.group.type === this.enums.aud)
          .map(assignment => assignment.group.id);

        this.addedIds.ind = this.assignments
          .filter(assignment => assignment.group.type === this.enums.ind)
          .map(assignment => assignment.group.id);

        this.addedIds.org = this.assignments
          .filter(assignment => assignment.group.type === this.enums.org)
          .map(assignment => assignment.group.id);

        this.addedIds.pos = this.assignments
          .filter(assignment => assignment.group.type === this.enums.pos)
          .map(assignment => assignment.group.id);
      }
      this.assignments;
    },

    trackSettings(newValue) {
      this.track = newValue.track;
    },
  },

  methods: {
    /**
     * Open the adder based on type
     *
     * @param {String} type (aud, ind, org, pos)
     */
    openAdder(type) {
      // Show the adder
      this.isOpen[type] = true;
      this.showAdderLoadingBtn = false;
    },

    /**
     * Close the adder based on type
     *
     * @param {String} type (aud, ind, org, pos)
     */
    closeAdder(type) {
      this.isOpen[type] = false;
    },

    /**
     * Saves the assigned cohorts in the repository.
     */
    updateSelectionFromAdder(selection, addedIds, adderType) {
      // Filter out previously added.
      const groups = selection.data
        .filter(item => addedIds.indexOf(item.id) == -1)
        .map(item => {
          return { id: item.id, type: adderType };
        });

      const selected = {
        track_id: this.track.id,
        type: this.adminEnum,
        groups: groups,
      };

      this.updateAssignmentsInRepository(
        AddTrackAssignmentMutation,
        'mod_perform_add_track_assignments',
        selected
      );

      this.$_postProcess(adderType, selection);
    },

    $_postProcess(adderType, selection) {
      // Get type key
      const type = Object.keys(this.enums).find(
        key => this.enums[key] === adderType
      );

      // Update type selection and close adder
      this.addedIds[type] = selection.ids;
      this.closeAdder(type);
    },

    /**
     * Removes the assigned user groupings from the repository.
     */
    removeAssignment() {
      if (!this.assignmentToRemove) {
        return;
      }

      const toBeRemoved = {
        track_id: this.track.id,
        type: this.assignmentToRemove.assignmentType,
        groups: [
          {
            id: this.assignmentToRemove.groupId,
            type: this.assignmentToRemove.groupType,
          },
        ],
      };

      this.hideRemoveConfirmationModal();

      this.updateAssignmentsInRepository(
        RemoveTrackAssignmentMutation,
        'mod_perform_remove_track_assignments',
        toBeRemoved
      );
    },

    /**
     * Convenience function to execute a graphql mutation.
     */
    async updateAssignmentsInRepository(mutation, mutationName, assignments) {
      const variables = {
        assignments: assignments,
      };

      if (assignments.groups[0]) {
        this.updatingAssignmentGroup = assignments.groups[0].type;
      }

      try {
        const { data: result } = await this.$apollo.mutate({
          mutation,
          variables,
          refetchAll: false, // Don't refetch all the data again
        });

        this.updatingAssignmentGroup = false;

        const savedTrack = result[mutationName];

        if (savedTrack) {
          this.track = savedTrack;
          this.$emit('mutation-success');
        } else {
          this.$emit('mutation-error');
        }
      } catch (e) {
        console.log('update track assignments error', e);
        this.$emit('mutation-error');
      }
    },

    /**
     * Saves the manual relationship selections to the backend.
     *
     * @param {Array}
     */
    async updateManualRelationships(relationships) {
      try {
        const { data: data } = await this.$apollo.mutate({
          mutation: SetManualParticipantSelectorRoles,
          variables: {
            input: {
              activity_id: this.activity.id,
              roles: relationships,
            },
          },
          refetchAll: true,
        });

        const result = data.mod_perform_set_manual_relationship_selector_roles;

        if (result && result.success) {
          this.$emit('mutation-success');
        } else {
          this.$emit('mutation-error');
        }
      } catch (e) {
        console.error('update manual relationship error', e);
        this.$emit('mutation-error');
      }
    },

    /**
     * Saves the relationship participation settings to the backend
     *
     * @param {Object} data current state of the toggles
     */
    async updateRelationshipParticipation(toggleState) {
      const settings = toggleState.settings;

      this.relationshipParticipationIsSaving = toggleState.disabled;
      try {
        const { data: data } = await this.$apollo.mutate({
          mutation: OverrideGlobalParticipationSettings,
          variables: {
            input: {
              activity_id: this.activity.id,
              override_global_participation_settings: settings.override,
              sync_participant_instance_creation: settings.autoAssign,
              sync_participant_instance_closure: settings.autoClose,
            },
          },
          refetchAll: false,
        });
        const result = data.mod_perform_override_global_participation_settings;

        if (result) {
          this.$emit('mutation-success');
        } else {
          this.$emit('mutation-error');
        }
        this.relationshipParticipationIsSaving = {
          override: false,
          assign: false,
          close: false,
        };
      } catch (e) {
        console.error('update relationship participation error', e);
        this.$emit('mutation-error');
        this.relationshipParticipationIsSaving = {
          override: false,
          assign: false,
          close: false,
        };
      }
    },

    /**
     * Shows the remove assignment confirmation dialog.
     */
    showRemoveConfirmationModal(assignmentType, groupId, groupType) {
      this.assignmentToRemove = {
        assignmentType: assignmentType,
        groupId: groupId,
        groupType: groupType,
      };
      this.confirmationModalOpen = true;
    },

    /**
     * Hides the remove assignment confirmation dialog.
     */
    hideRemoveConfirmationModal() {
      this.assignmentToRemove = null;
      this.confirmationModalOpen = false;
    },
  },

  apollo: {
    /**
     * Fetch the available manually relationship options
     *
     */
    manualRelationshipOptions: {
      query: ManualRelationshipOptionsQuery,
      variables() {
        return { activity_id: this.activity.id };
      },
      update: ({
        mod_perform_manual_relationship_selector_options: relationshipOptions,
      }) => {
        return relationshipOptions.map(relationship => {
          return {
            id: relationship.id,
            label: relationship.name,
          };
        });
      },
    },

    trackSettings: {
      query: TrackSettingsQuery,
      fetchPolicy: 'network-only', // Always refetch data on tab change
      variables() {
        return {
          activity_id: this.activity.id,
        };
      },
      update: data => {
        return {
          track: data.mod_perform_default_track,
        };
      },
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform" : [
      "user_group_assignment_add_group",
      "user_group_assignment_confirm_modal_remove",
      "user_group_assignment_confirm_remove_active",
      "user_group_assignment_confirm_remove_draft",
      "user_group_assignment_confirm_remove_title",
      "user_group_assignment_group_cohort",
      "user_group_assignment_group_cohort_plural",
      "user_group_assignment_group_individual",
      "user_group_assignment_group_individual_plural",
      "user_group_assignment_group_organisation",
      "user_group_assignment_group_organisation_plural",
      "user_group_assignment_group_position",
      "user_group_assignment_group_position_plural",
      "user_group_assignment_no_users",
      "user_group_assignment_title",
      "user_individual_assignment_confirm_remove_active",
      "user_individual_assignment_confirm_remove_draft",
      "user_individual_assignment_confirm_remove_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageActivityAssignmentsForm {
  &__heading {
    display: flex;
    flex-direction: column;
    margin-top: calc(var(--gap-1) * -1);

    &-title {
      margin: 0;
      @include tui-font-heading-small();
    }

    &-add {
      margin-top: var(--gap-2);
      margin-left: auto;
    }
  }

  &__settings {
    & > * {
      margin-top: var(--gap-12);
    }
  }

  &__assigned {
    margin-top: var(--gap-8);

    & > * + * {
      margin-top: var(--gap-4);
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performManageActivityAssignmentsForm {
    &__heading {
      flex-direction: row;
      align-items: center;

      &-add {
        margin-top: 0;
      }
    }
  }
}
</style>
