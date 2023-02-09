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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencyDetailAssignment">
    <div class="tui-competencyDetailAssignment__bar">
      <Grid :stack-at="700">
        <GridItem :units="4">
          <!-- Competency assignment select list -->
          <SelectFilter
            v-model="selectedAssignment"
            class="tui-competencyDetailAssignment__bar-filter"
            :label="$str('assignment', 'totara_competency')"
            :name="'select_assignment'"
            :large="true"
            :options="activeAssignmentList"
            @input="selectedAssignmentChange"
          />
        </GridItem>
        <GridItem :units="4" :class="'tui-competencyDetailAssignment__level'">
          <div
            class="tui-competencyDetailAssignment__level-wrap"
            :class="
              'tui-competencyDetailAssignment__level-wrap-' +
                selectedAssignmentProficiencyState
            "
          >
            <p class="tui-competencyDetailAssignment__level-header">
              {{ $str('achievement_level', 'totara_competency') }}
            </p>
            <div class="tui-competencyDetailAssignment__level-text">
              {{ selectedAssignmentProficiency.name }}
            </div>
          </div>
        </GridItem>
        <GridItem :units="4" :class="'tui-competencyDetailAssignment__status'">
          <ProgressTrackerNavCircleAchievement
            :states="progressStates"
            :is-interactable="false"
            aria-role="none"
          />

          <span
            class="tui-competencyDetailAssignment__status-text"
            :class="{
              'tui-competencyDetailAssignment__status-text-complete':
                selectedAssignmentProficiencyState === 'complete',
            }"
          >
            {{
              $str(
                selectedAssignmentProficiency.proficient
                  ? 'proficient'
                  : 'not_proficient',
                'totara_competency'
              )
            }}
          </span>
        </GridItem>
      </Grid>
      <ConfirmationModal
        :title="$str('action_archive_user_modal_header', 'totara_competency')"
        :open="showArchiveConfirmation"
        @confirm="makeArchiveAssignmentMutation"
        @cancel="showArchiveConfirmation = false"
      >
        <p>
          {{
            $str('action_archive_user_assignment_modal', 'totara_competency')
          }}
        </p>
        <p>
          {{ $str('confirm_generic', 'totara_competency') }}
        </p>
      </ConfirmationModal>
    </div>
    <div class="tui-competencyDetailAssignment__actions">
      <ButtonIcon
        v-if="showCanArchiveButton"
        aria-label=""
        :styleclass="{ small: true }"
        :text="$str('action_archive_this', 'totara_competency')"
        @click="showArchiveConfirmDialog"
      >
        <ArchiveIcon />
      </ButtonIcon>
    </div>
  </div>
</template>

<script>
// Components
import ArchiveIcon from 'tui/components/icons/Archive';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import ProgressTrackerNavCircleAchievement from 'tui/components/progresstracker/ProgressTrackerNavCircleAchievement';
import SelectFilter from 'tui/components/filters/SelectFilter';
import { notify } from 'tui/notifications';
// GraphQL
import ArchiveUserAssignment from 'totara_competency/graphql/archive_user_assignment';
import CompetencyProfileDetailsQuery from 'totara_competency/graphql/profile_competency_details';
import { ASSIGNMENT_ACTIVE } from '../../js/constants';

export default {
  components: {
    ArchiveIcon,
    ButtonIcon,
    ConfirmationModal,
    Grid,
    GridItem,
    SelectFilter,
    ProgressTrackerNavCircleAchievement,
  },

  props: {
    activeAssignmentList: {
      required: true,
      type: Array,
    },
    selectedAssignmentProficiency: {
      type: Object,
    },
    value: {
      type: Number,
    },
    competencyId: {
      required: true,
      type: Number,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  data() {
    return {
      selectedAssignment: this.value,
      showArchiveConfirmation: false,
    };
  },

  computed: {
    /**
     * Return states for progress tracking marker
     *
     * @return {Array}
     */
    progressStates() {
      let states = [this.selectedAssignmentProficiencyState];

      if (this.selectedAssignmentProficiencyState !== 'complete') {
        states.push('target');
      }
      return states;
    },

    /**
     * Return proficient state (pending, complete, achieved)
     *
     * @return {String}
     */
    selectedAssignmentProficiencyState() {
      if (
        this.selectedAssignmentProficiency.id &&
        this.selectedAssignmentProficiency.proficient
      ) {
        return 'achieved';
      } else if (this.selectedAssignmentProficiency.id) {
        return 'complete';
      } else {
        return 'pending';
      }
    },
    showCanArchiveButton() {
      // Check whether we can archive this assignment
      let activeAssignment = this.activeAssignmentList.find(
        assignment => assignment.id === this.selectedAssignment
      );

      return activeAssignment && activeAssignment.can_archive;
    },
  },

  methods: {
    selectedAssignmentChange(e) {
      this.$emit('input', e);
    },
    showArchiveConfirmDialog() {
      this.showArchiveConfirmation = true;
    },
    async makeArchiveAssignmentMutation() {
      let { assignment_id } = this.activeAssignmentList.find(
        assignment => assignment.id === this.value
      );

      try {
        await this.$apollo.mutate({
          mutation: ArchiveUserAssignment,
          variables: {
            assignment_id: assignment_id,
          },
          refetchQueries: [
            {
              query: CompetencyProfileDetailsQuery,
              variables: {
                user_id: this.userId,
                competency_id: this.competencyId,
                status: ASSIGNMENT_ACTIVE,
              },
            },
          ],
          refetchAll: false,
        });

        notify({
          type: 'success',
          message: this.$str('event_assignment_archived', 'totara_competency'),
        });
        this.selectedAssignment = 0;
      } finally {
        this.showArchiveConfirmation = false;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "achievement_level",
      "action_archive_this",
      "action_archive_user_modal_header",
      "action_archive_user_assignment_modal",
      "assignment",
      "confirm_generic",
      "error_generic_mutation",
      "event_assignment_archived",
      "more_information",
      "not_proficient",
      "proficient"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyDetailAssignment {
  &__bar {
    padding: var(--gap-4);
    background: var(--color-neutral-3);

    &-filter.tui-selectFilter {
      .tui-select {
        max-width: initial;
      }
    }
  }

  &__actions {
    padding: var(--gap-4) var(--gap-2);
    text-align: right;
  }

  .tui-grid--stacked {
    & > * + * {
      justify-content: flex-start;
      padding-top: var(--gap-4);
    }
  }

  &__level {
    display: flex;
    align-items: center;

    &-header {
      display: flex;
      margin: 0;
      @include tui-font-body-small();
    }

    &-infoBtn {
      padding-left: var(--gap-1);
    }

    &-text {
      @include tui-font-heading-label-small();
    }

    &-wrap {
      @include tui-font-body-small();
      padding-left: var(--gap-2);
      border-style: solid;
      border-width: 0 0 0 var(--border-width-thick);

      .dir-rtl & {
        padding-right: var(--gap-2);
        border-width: 0 var(--border-width-thick) 0 0;
      }

      &-pending {
        border-color: var(--progresstracker-color-pending);
      }

      &-complete {
        border-color: var(--progresstracker-color-complete);
      }

      &-achieved {
        border-color: var(--progresstracker-color-achieved);
      }
    }
  }

  &__status {
    display: flex;
    align-items: center;
    justify-content: flex-end;

    &-text {
      margin-left: var(--gap-2);
      @include tui-font-heading-small();

      .dir-rtl & {
        margin: 0 var(--gap-2) 0 0;
      }

      &-complete {
        margin-left: var(--gap-1);

        .dir-rtl & {
          margin: 0 var(--gap-1) 0 0;
        }
      }
    }
  }
}
</style>
